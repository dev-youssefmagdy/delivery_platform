<?php

namespace Tests\Concerns;

use App\Enums\Admin\AdminPermission;
use App\Models\Admin;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Admin test helpers shared by every admin module test (reference: Admin Module Contract, Plan §5.6).
 * Admins are real `Admin` records on the `admin` guard holding the requested permissions directly (Spatie);
 * guests are redirected to the admin login page.
 */
trait InteractsWithAdmin
{
    /**
     * @param list<AdminPermission|string> $permissions
     * @param array<string, mixed> $attributes
     */
    protected function actingAsAdmin(array $permissions = [], array $attributes = []): Admin
    {
        $admin = Admin::factory()->create($attributes);

        foreach ($permissions as $permission) {
            $name = $permission instanceof AdminPermission ? $permission->value : $permission;
            Permission::findOrCreate($name, 'admin');
            $admin->givePermissionTo($name);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /**
     * Guests are sent to the admin login page.
     *
     * @param TestResponse<\Symfony\Component\HttpFoundation\Response> $response
     */
    protected function assertGuestBlocked(TestResponse $response): void
    {
        $response->assertRedirect(route('admin.login'));
    }

    /**
     * Calls a Yajra listing endpoint the way DataTables does (AJAX + JSON), with optional filters.
     *
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $parameters
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    protected function datatable(string $url, array $filters = [], array $parameters = []): TestResponse
    {
        $query = http_build_query(array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'filters' => $filters,
        ], $parameters));

        return $this->getJson($url.'?'.$query, ['X-Requested-With' => 'XMLHttpRequest']);
    }

    /**
     * Precognitive (validate-only) request, as sent by <x-admin.form> before submitting.
     *
     * @param array<string, mixed> $data
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    protected function precognitive(string $method, string $url, array $data = [], ?string $only = null): TestResponse
    {
        $headers = ['Precognition' => 'true', 'X-Requested-With' => 'XMLHttpRequest'];

        if ($only !== null) {
            $headers['Precognition-Validate-Only'] = $only;
        }

        return $this->json($method, $url, $data, $headers);
    }
}
