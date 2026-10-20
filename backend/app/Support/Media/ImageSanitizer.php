<?php

namespace App\Support\Media;

use RuntimeException;

/**
 * Re-encodes an uploaded image as a bounded JPEG with GD. Decoding and re-encoding drops EXIF/GPS metadata and any
 * payload hidden in the original file, and bounds the stored size.
 */
final class ImageSanitizer
{
    /**
     * @return string JPEG bytes
     *
     * @throws RuntimeException when the bytes are not a decodable image
     */
    public function toJpeg(string $bytes, int $maxEdge, int $quality = 85): string
    {
        $source = @imagecreatefromstring($bytes);

        if ($source === false) {
            throw new RuntimeException('The file is not a readable image.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1.0, $maxEdge / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        // Flatten transparency onto white: JPEG has no alpha channel.
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, $quality);
        $jpeg = (string) ob_get_clean();

        if ($jpeg === '') {
            throw new RuntimeException('The image could not be encoded.');
        }

        return $jpeg;
    }
}
