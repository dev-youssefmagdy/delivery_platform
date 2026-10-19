<?php

namespace Tests\Concerns;

use App\Models\Admin;
use Illuminate\Testing\TestResponse;

/**
 * Drives the real login endpoints (not actingAs) and keeps the session cookie between requests, which the
 * test client does not do by itself.
 */
trait SignsInAdmins
{
    /**
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    protected function submitLogin(string $email, string $password = 'Str0ng-Password!', bool $remember = false): TestResponse
    {
        return $this->postJson(route('admin.login.store'), ['email' => $email, 'password' => $password, 'remember' => $remember]);
    }

    protected function keepSession(): static
    {
        // JSON requests only carry cookies when credentials are enabled.
        return $this->withCredentials()->withCookie((string) config('session.cookie'), session()->getId());
    }

    protected function adminWithPassword(array $attributes = []): Admin
    {
        return Admin::factory()->create($attributes);
    }
}
