<?php

namespace App\Support\Website;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Server-side QR codes for the app download page (no new dependency: bacon/bacon-qr-code is already installed). Output is a
 * self-contained SVG string for inline use; it contains only the encoded URL as drawn squares (no scripts, no external refs).
 */
final class QrCode
{
    public function svg(string $text, int $size = 168, int $margin = 2): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, $margin), new SvgImageBackEnd));
        $svg = $writer->writeString($text);

        // Drop the XML prolog so the SVG can sit inside HTML.
        return trim((string) preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg));
    }
}
