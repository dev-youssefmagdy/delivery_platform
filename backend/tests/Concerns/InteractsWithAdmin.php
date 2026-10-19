<?php

namespace Tests\Concerns;

use App\Enums\Admin\AdminPermission;
use Illuminate\Testing\TestResponse;
use Tests\Support\TestAdmin;

/**
 * Admin test helpers shared by every admin module test (reference: Admin Module Contract, Plan §5.6).
 * P1.1 swaps TestAdmin for the real Admin model/guard and makes guests redirect to the login page; only this
 * trait changes, the module tests stay as they are.
 */
trait InteractsWithAdmin
{
    /**
     * @param list<AdminPermission|string> $permissions
     */
    protected function actingAsAdmin(array $permissions = []): TestAdmin
    {
        $admin = new TestAdmin(array_map(
            static fn (AdminPermission|string $permission): string => $permission instanceof AdminPermission ? $permission->value : $permission,
            $permissions,
        ));

        $this->actingAs($admin);

        return $admin;
    }

    /**
     * Until P1.1 there is no admin login: guests fail authorization (403). P1.1: assertRedirect(route('admin.login')).
     *
     * @param TestResponse<\Symfony\Component\HttpFoundation\Response> $response
     */
    protected function assertGuestBlocked(TestResponse $response): void
    {
        $response->assertForbidden();
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
