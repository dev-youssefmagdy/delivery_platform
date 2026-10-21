<?php

namespace Tests\Feature\Api;

use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenActionException;
use App\Models\Currency;
use App\Support\Http\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * ADR-14 envelope and exception rendering for JSON requests (SRS §20, §20.7).
 */
class ApiEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api/v1/_test')->group(function (): void {
            Route::get('ok', fn () => ApiResponse::success('done', ['id' => 1], ['extra' => true]));
            Route::get('paginated', fn () => ApiResponse::paginated(new LengthAwarePaginator([['id' => 1], ['id' => 2]], 5, 2, 1)));
            Route::post('validate', fn (Request $request) => $request->validate(['name' => 'required|string']));
            Route::get('rule', fn () => throw BusinessRuleViolation::make('offer_expired', 'offer_id'));
            Route::get('conflict', fn () => throw ConflictException::make('conflict_without_translation'));
            Route::get('forbidden-action', fn () => throw ForbiddenActionException::make('not_your_booking'));
            Route::get('unauthenticated', fn () => throw new AuthenticationException);
            Route::get('unauthorized', fn () => throw new AuthorizationException('secret policy detail'));
            Route::get('missing-model', fn () => Currency::query()->findOrFail(999_999));
            Route::get('boom', fn () => throw new RuntimeException('SQLSTATE[42S02] secret /var/www/path'));
            Route::get('throttled', fn () => 'ok')->middleware('throttle:1,1');
        });
    }

    public function test_success_envelope_shape(): void
    {
        $this->getJson('/api/v1/_test/ok')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'done',
                'data' => ['id' => 1],
                'meta' => ['extra' => true],
                'errors' => [],
            ]);
    }

    public function test_paginated_envelope_has_pagination_meta(): void
    {
        $this->getJson('/api/v1/_test/paginated')
            ->assertOk()
            ->assertJsonPath('data', [['id' => 1], ['id' => 2]])
            ->assertJsonPath('meta.pagination', ['type' => 'page', 'current_page' => 1, 'per_page' => 2, 'total' => 5, 'last_page' => 3]);
    }

    public function test_validation_errors_are_field_level_with_stable_code(): void
    {
        $this->postJson('/api/v1/_test/validate', [], ['Accept-Language' => 'ar'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors._code', 'validation_failed')
            ->assertJsonPath('errors.name.0', 'حقل الاسم مطلوب.')
            ->assertJsonStructure(['meta' => ['request_id']]);
    }

    public function test_business_rule_violation_maps_to_422_with_field(): void
    {
        $this->getJson('/api/v1/_test/rule')
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'offer_expired')
            ->assertJsonCount(1, 'errors.offer_id');
    }

    public function test_conflict_and_forbidden_action(): void
    {
        $this->getJson('/api/v1/_test/conflict')->assertStatus(409)->assertJsonPath('errors._code', 'conflict_without_translation');
        $this->getJson('/api/v1/_test/forbidden-action')->assertStatus(403)->assertJsonPath('errors._code', 'not_your_booking');
    }

    public function test_auth_errors_do_not_leak_messages(): void
    {
        $this->getJson('/api/v1/_test/unauthenticated')->assertStatus(401)->assertJsonPath('errors._code', 'unauthenticated');

        $response = $this->getJson('/api/v1/_test/unauthorized')->assertStatus(403)->assertJsonPath('errors._code', 'forbidden');
        $this->assertStringNotContainsString('secret policy detail', (string) $response->getContent());
    }

    public function test_not_found_for_models_and_routes(): void
    {
        $this->getJson('/api/v1/_test/missing-model')->assertNotFound()->assertJsonPath('errors._code', 'not_found');
        $this->getJson('/api/v1/does-not-exist')->assertNotFound()->assertJsonPath('errors._code', 'not_found');
    }

    public function test_unexpected_errors_never_leak_internals(): void
    {
        $response = $this->getJson('/api/v1/_test/boom')
            ->assertStatus(500)
            ->assertJsonPath('errors._code', 'server_error')
            ->assertJsonStructure(['meta' => ['request_id']]);

        $body = (string) $response->getContent();
        $this->assertStringNotContainsString('SQLSTATE', $body);
        $this->assertStringNotContainsString('/var/www', $body);
        $this->assertStringNotContainsString('trace', $body);
    }

    public function test_api_routes_render_json_even_without_accept_header(): void
    {
        $this->get('/api/v1/_test/rule')->assertStatus(422)->assertJsonPath('errors._code', 'offer_expired');
    }

    public function test_throttled_requests_include_retry_metadata(): void
    {
        $this->getJson('/api/v1/_test/throttled')->assertOk();

        $this->getJson('/api/v1/_test/throttled')
            ->assertStatus(429)
            ->assertJsonPath('errors._code', 'too_many_requests')
            ->assertHeader('Retry-After')
            ->assertJsonStructure(['meta' => ['retry_after']]);
    }

    public function test_messages_follow_the_request_locale(): void
    {
        $this->getJson('/api/v1/_test/conflict', ['Accept-Language' => 'en'])
            ->assertJsonPath('message', 'The request conflicts with the current state. Please refresh and try again.');
    }

    public function test_error_envelope_echoes_the_inbound_request_id(): void
    {
        $this->getJson('/api/v1/_test/conflict', ['X-Request-Id' => 'client-req-12345'])
            ->assertHeader('X-Request-Id', 'client-req-12345')
            ->assertJsonPath('meta.request_id', 'client-req-12345');
    }

    public function test_unsafe_request_ids_are_replaced(): void
    {
        $response = $this->getJson('/api/v1/_test/ok', ['X-Request-Id' => "bad\nid<script>"]);

        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string) $response->headers->get('X-Request-Id'));
    }
}
