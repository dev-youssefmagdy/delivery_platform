<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\Website\SitemapService;
use Illuminate\Http\Response;

/**
 * sitemap.xml (dynamic, from the published CMS content) and robots.txt (private areas are disallowed, the sitemap announced).
 */
final class SeoController extends Controller
{
    public function sitemap(SitemapService $sitemap): Response
    {
        return response($sitemap->xml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=300']);
    }

    public function robots(): Response
    {
        $admin = '/'.config('admin.path');
        $lines = [
            'User-agent: *',
            'Disallow: '.$admin,
            'Disallow: /api/',
            'Disallow: /t/',
            'Disallow: /receipts/',
            'Allow: /',
            '',
            'Sitemap: '.rtrim(url('/'), '/').'/sitemap.xml',
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}
