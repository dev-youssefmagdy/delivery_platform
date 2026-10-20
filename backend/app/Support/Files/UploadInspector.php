<?php

namespace App\Support\Files;

use App\Exceptions\BusinessRuleViolation;
use App\Support\Media\ImageSanitizer;
use finfo;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Validates uploads of private documents and photos beyond what a FormRequest can: the size, the CLIENT extension
 * against the SNIFFED MIME type (never the browser-supplied one), a real decode of images with a pixel cap
 * (decompression-bomb guard), re-encoding of images to JPEG (drops EXIF/GPS and anything appended to the file) and a
 * heuristic refusal of PDFs with active content. Errors are `BusinessRuleViolation`s on the `file` field.
 */
final class UploadInspector
{
    private const PDF_ACTIVE_CONTENT = ['/JavaScript', '/JS', '/Launch', '/EmbeddedFile', '/RichMedia', '/SubmitForm', '/ImportData'];

    public function __construct(private readonly ImageSanitizer $images) {}

    /**
     * @param list<string> $allowedMimes MIME types the document type accepts
     *
     * @throws BusinessRuleViolation
     */
    public function inspectDocument(UploadedFile $file, array $allowedMimes, int $maxKilobytes): InspectedUpload
    {
        $mime = $this->sniff($file, $maxKilobytes);
        $extension = strtolower($file->getClientOriginalExtension());
        $map = (array) config('provider.documents.mime_extensions', []);

        if (! in_array($mime, $allowedMimes, true) || ! isset($map[$mime]) || ! in_array($extension, (array) $map[$mime], true)) {
            throw BusinessRuleViolation::make('document_type_not_allowed', 'file');
        }

        $bytes = $this->read($file);

        if ($mime === 'application/pdf') {
            return $this->inspectPdf($bytes, $file);
        }

        return $this->reencode($bytes, $file, (int) config('provider.documents.image_max_edge', 2400), (int) config('provider.documents.image_min_edge', 200));
    }

    /**
     * @throws BusinessRuleViolation
     */
    public function inspectPhoto(UploadedFile $file, int $maxKilobytes, int $maxEdge): InspectedUpload
    {
        $mime = $this->sniff($file, $maxKilobytes);
        $extension = strtolower($file->getClientOriginalExtension());
        $map = (array) config('provider.documents.mime_extensions', []);

        if (! str_starts_with($mime, 'image/') || ! isset($map[$mime]) || ! in_array($extension, (array) $map[$mime], true)) {
            throw BusinessRuleViolation::make('document_type_not_allowed', 'file');
        }

        return $this->reencode($this->read($file), $file, $maxEdge, (int) config('provider.documents.image_min_edge', 200));
    }

    private function sniff(UploadedFile $file, int $maxKilobytes): string
    {
        if (! $file->isValid() || $file->getRealPath() === false) {
            throw BusinessRuleViolation::make('document_upload_failed', 'file');
        }

        $limit = min($maxKilobytes, (int) config('provider.documents.max_kilobytes', 10240)) * 1024;
        $size = $file->getSize();

        if ($size === false || $size < 1) {
            throw BusinessRuleViolation::make('document_unreadable', 'file');
        }

        if ($size > $limit) {
            throw BusinessRuleViolation::make('document_too_large', 'file', ['kilobytes' => intdiv($limit, 1024)]);
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file->getRealPath());

        return is_string($mime) ? $mime : 'application/octet-stream';
    }

    private function read(UploadedFile $file): string
    {
        $bytes = file_get_contents((string) $file->getRealPath());

        return is_string($bytes) && $bytes !== '' ? $bytes : throw BusinessRuleViolation::make('document_unreadable', 'file');
    }

    private function inspectPdf(string $bytes, UploadedFile $file): InspectedUpload
    {
        if (! str_starts_with($bytes, '%PDF-')) {
            throw BusinessRuleViolation::make('document_unreadable', 'file');
        }

        foreach (self::PDF_ACTIVE_CONTENT as $marker) {
            if (str_contains($bytes, $marker)) {
                throw BusinessRuleViolation::make('document_pdf_active_content', 'file');
            }
        }

        return new InspectedUpload($bytes, 'application/pdf', 'pdf', $this->safeName($file));
    }

    private function reencode(string $bytes, UploadedFile $file, int $maxEdge, int $minEdge): InspectedUpload
    {
        $info = @getimagesizefromstring($bytes);

        if ($info === false || $info[0] < 1 || $info[1] < 1) {
            throw BusinessRuleViolation::make('document_unreadable', 'file');
        }

        if ((int) config('provider.documents.image_max_pixels', 40_000_000) < $info[0] * $info[1]) {
            throw BusinessRuleViolation::make('document_image_too_large', 'file');
        }

        if (min($info[0], $info[1]) < $minEdge) {
            throw BusinessRuleViolation::make('document_image_too_small', 'file', ['pixels' => $minEdge]);
        }

        try {
            $jpeg = $this->images->toJpeg($bytes, $maxEdge);
        } catch (RuntimeException) {
            throw BusinessRuleViolation::make('document_unreadable', 'file');
        }

        return new InspectedUpload($jpeg, 'image/jpeg', 'jpg', $this->safeName($file));
    }

    private function safeName(UploadedFile $file): string
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $clean = (string) preg_replace('/[^\p{L}\p{N}\s._-]+/u', '', $name);

        return mb_substr(trim($clean), 0, 100);
    }
}
