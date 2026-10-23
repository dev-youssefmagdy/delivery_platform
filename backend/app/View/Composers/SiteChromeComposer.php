<?php

namespace App\View\Composers;

use App\Services\Website\SiteChrome;
use Illuminate\Contracts\View\View;

/**
 * Gives the public website layout its header / footer data once per render.
 */
final class SiteChromeComposer
{
    public function __construct(private readonly SiteChrome $chrome) {}

    public function compose(View $view): void
    {
        if (! array_key_exists('chrome', $view->getData()) && ! (bool) ($view->getData()['minimal'] ?? false)) {
            $view->with('chrome', $this->chrome->build());
        }
    }
}
