<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\Http\AdminFormResponse;
use Illuminate\Auth\AuthenticationException;

/**
 * Base for admin controllers: thin actions (authorize → DTO → one service call → view / AdminFormResponse).
 * Authorization happens in route `can:` middleware (policies) and FormRequest::authorize().
 */
abstract class AdminController extends Controller
{
    /**
     * The acting admin for write actions (audit actor).
     */
    protected function actor(): Admin
    {
        $admin = auth('admin')->user();

        return $admin instanceof Admin ? $admin : throw new AuthenticationException('Unauthenticated.', ['admin'], route('admin.login'));
    }

    protected function formResponse(?string $message = null): AdminFormResponse
    {
        return AdminFormResponse::make($message);
    }
}
