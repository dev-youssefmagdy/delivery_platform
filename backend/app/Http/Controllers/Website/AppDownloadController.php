<?php

namespace App\Http\Controllers\Website;

use App\Support\Website\QrCode;
use Illuminate\Contracts\View\View;

/**
 * App download page: the store links an operator published (CMS app links) with a QR code for each, drawn on the server. With no
 * published link the page says so honestly instead of showing dead buttons.
 */
final class AppDownloadController extends SiteController
{
    public function show(QrCode $qr): View
    {
        $links = array_map(static fn (array $link): array => $link + ['qr' => $qr->svg($link['url'])], $this->content->appLinks());
        $meta = $this->meta->make('apps', [], (string) __('site.apps.title'), (string) __('site.apps.description'));

        return $this->page('website.pages.apps', ['links' => $links], $meta);
    }
}
