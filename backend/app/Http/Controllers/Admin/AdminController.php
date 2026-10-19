<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Base for admin controllers: thin actions (authorize → DTO → one service call → view / AdminFormResponse).
 * Authorization happens in route `can:` middleware (policies) and FormRequest::authorize().
 */
abstract class AdminController extends Controller
{
    /**
     * The acting admin (null only in the local guest-access scaffolding before P1.1).
     */
    protected function actor(): ?Authenticatable
    {
        return auth()->user();
    }

    protected function formResponse(?string $message = null): AdminFormResponse
    {
        return AdminFormResponse::make($message);
    }
}
