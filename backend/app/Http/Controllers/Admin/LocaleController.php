<?php

namespace App\Http\Controllers\Admin;

use App\Http\Middleware\SetLocale;
use App\Http\Requests\Admin\UpdateLocaleRequest;
use App\Models\Admin;
use App\Services\Admin\AdminProfileService;
use App\Support\Http\AdminFormResponse;

/**
 * Topbar ar/en switcher. Stored in the session (guests) and persisted on the signed-in admin record (read first by SetLocale).
 */
final class LocaleController extends AdminController
{
    public function __construct(private readonly AdminProfileService $profile) {}

    public function update(UpdateLocaleRequest $request): AdminFormResponse
    {
        $request->session()->put(SetLocale::SESSION_KEY, $request->locale()->value);

        $admin = auth('admin')->user();

        if ($admin instanceof Admin) {
            $this->profile->updateLocale($admin, $request->locale());
        }

        return $this->formResponse()->reload();
    }
}
