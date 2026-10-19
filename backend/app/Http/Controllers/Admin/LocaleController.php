<?php

namespace App\Http\Controllers\Admin;

use App\Http\Middleware\SetLocale;
use App\Http\Requests\Admin\UpdateLocaleRequest;
use App\Support\Http\AdminFormResponse;

/**
 * Topbar ar/en switcher. Stored in the session (read by SetLocale); P1.1 also persists it on the admin.
 */
final class LocaleController extends AdminController
{
    public function update(UpdateLocaleRequest $request): AdminFormResponse
    {
        $request->session()->put(SetLocale::SESSION_KEY, $request->locale()->value);

        return $this->formResponse()->reload();
    }
}
