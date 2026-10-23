<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\AdminController;
use App\Services\System\IntegrationHealthService;
use Illuminate\Contracts\View\View;

/**
 * Integration health page (system.health): infrastructure, queues, adapters, webhook failures. Read only.
 */
final class SystemHealthController extends AdminController
{
    public function __invoke(IntegrationHealthService $health): View
    {
        $items = $health->report();

        return view('admin.system.health.index', ['items' => $items, 'overall' => $health->overall($items)]);
    }
}
