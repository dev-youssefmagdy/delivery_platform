<?php

namespace App\Services\Content;

use App\DTOs\Content\MediaUploadData;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\ContentMedia;
use App\Repositories\Contracts\Content\ContentMediaRepositoryInterface;
use App\Services\Audit\AuditLogger;
use GdImage;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Media library on the public disk. Uploads are decoded and re-encoded (so EXIF/GPS data and anything appended to the
 * file is dropped), limited in dimensions and pixels, and resized into the configured widths.
 */
final class ContentMediaService
{
    private const TYPES = [IMAGETYPE_JPEG => ['image/jpeg', 'jpg'], IMAGETYPE_PNG => ['image/png', 'png'], IMAGETYPE_WEBP => ['image/webp', 'webp']];

    public function __construct(
        private readonly ContentMediaRepositoryInterface $media,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
        private readonly ContentCache $cache,
    ) {}

    public function store(MediaUploadData $data, Admin $actor): ContentMedia
    {
        $binary = (string) file_get_contents($data->file->getRealPath());
        $info = @getimagesizefromstring($binary);

        if ($info === false || ! isset(self::TYPES[$info[2]])) {
            throw BusinessRuleViolation::make('media_invalid_image', 'file');
        }

        [$width, $height] = [$info[0], $info[1]];
        $limits = (array) config('content.media');

        if (min($width, $height) < (int) $limits['min_dimension'] || max($width, $height) > (int) $limits['max_dimension'] || $width * $height > (int) $limits['max_pixels']) {
            throw BusinessRuleViolation::make('media_dimensions_invalid', 'file', ['min' => $limits['min_dimension'], 'max' => $limits['max_dimension']]);
        }

        [$mime, $extension] = self::TYPES[$info[2]];
        $source = @imagecreatefromstring($binary);

        if (! $source instanceof GdImage) {
            throw BusinessRuleViolation::make('media_invalid_image', 'file');
        }

        $source = $this->oriented($source, $binary, $info[2]);
        $width = imagesx($source);
        $height = imagesy($source);
        $uuid = (string) Str::uuid();
        $directory = trim((string) $limits['directory'], '/').'/'.now()->format('Y/m');
        $disk = Storage::disk((string) $limits['disk']);
        $written = [];

        try {
            $path = "{$directory}/{$uuid}.{$extension}";
            $disk->put($path, $this->encode($source, $extension), 'public');
            $written[] = $path;
            $variants = [];

            foreach ((array) $limits['variants'] as $variantWidth) {
                if ((int) $variantWidth >= $width) {
                    continue;
                }

                $scaled = imagescale($source, (int) $variantWidth, -1, IMG_BICUBIC);

                if ($scaled instanceof GdImage) {
                    $variantPath = "{$directory}/{$uuid}-{$variantWidth}.{$extension}";
                    $disk->put($variantPath, $this->encode($scaled, $extension), 'public');
                    $written[] = $variantPath;
                    $variants[(string) $variantWidth] = $variantPath;
                }
            }

            $item = $this->db->transaction(function () use ($data, $actor, $uuid, $limits, $path, $mime, $width, $height, $variants, $disk): ContentMedia {
                /** @var ContentMedia $item */
                $item = $this->media->create([
                    'uuid' => $uuid,
                    'disk' => (string) $limits['disk'],
                    'path' => $path,
                    'original_name' => Str::limit(preg_replace('/[^\p{L}\p{N}._ -]+/u', '', $data->file->getClientOriginalName()) ?: 'image', 150, ''),
                    'mime' => $mime,
                    'size_bytes' => (int) $disk->size($path),
                    'width' => $width,
                    'height' => $height,
                    'alt' => $data->altTranslations(),
                    'variants' => $variants,
                    'uploaded_by_admin_id' => $actor->getKey(),
                ]);
                $this->audit->record(AuditAction::Created, $item, $actor, after: $item->auditSnapshot());

                return $item;
            });
        } catch (Throwable $e) {
            $disk->delete($written);

            throw $e;
        }

        return $item;
    }

    /**
     * @param array<string, string> $alt
     */
    public function updateAlt(ContentMedia $media, array $alt, Admin $actor): ContentMedia
    {
        return $this->db->transaction(function () use ($media, $alt, $actor): ContentMedia {
            $before = $media->auditSnapshot();
            $this->media->update($media, ['alt' => $alt]);
            $this->audit->recordChanges(AuditAction::Updated, $media, $actor, $before, $media->auditSnapshot());
            $this->cache->bump();

            return $media;
        });
    }

    public function delete(ContentMedia $media, Admin $actor, ?string $reason = null): void
    {
        $this->db->transaction(function () use ($media, $actor, $reason): void {
            $locked = $this->media->lockById($media->getKey());

            if (! $locked instanceof ContentMedia) {
                return;
            }

            if ($this->media->referenceCount($locked) > 0) {
                throw BusinessRuleViolation::make('media_in_use');
            }

            $before = $locked->auditSnapshot();
            $paths = array_values(array_merge([$locked->path], (array) $locked->variants));
            $this->media->delete($locked);
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
            Storage::disk($locked->disk)->delete($paths);
        });
    }

    private function oriented(GdImage $image, string $binary, int $type): GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($binary));
        $rotation = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($rotation === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $rotation, 0);

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    private function encode(GdImage $image, string $extension): string
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $quality = (int) config('content.media.quality', 82);

        ob_start();

        match ($extension) {
            'png' => imagepng($image, null, 6),
            'webp' => imagewebp($image, null, $quality),
            default => imagejpeg($image, null, $quality),
        };

        return (string) ob_get_clean();
    }
}
