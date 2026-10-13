<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\Idempotency;
use App\Models\IdempotencyKey;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Idempotency-Key middleware (Plan §4.4, invariant I9): replay, payload mismatch, concurrency, scoping.
 */
class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private int $executions = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->executions = 0;

        Route::middleware(['api', 'idempotent'])->prefix('api/v1/_test')->group(function (): void {
            Route::post('bookings', function (Request $request) {
                $this->executions++;

                return ApiResponse::created(data: ['execution' => $this->executions, 'fare' => $request->input('fare')]);
            })->name('test.bookings.store');

            Route::post('failing', function () {
                $this->executions++;

                return ApiResponse::error('nope', 422, 'some_rule');
            })->name('test.failing');

            Route::get('bookings', fn () => ApiResponse::success(data: ['read' => true]))->name('test.bookings.index');
        });
    }

    public function test_first_request_executes_and_replay_returns_the_stored_response(): void
    {
        $user = User::factory()->create();
        $key = (string) Str::uuid();

        $first = $this->actingAs($user)->postJson('/api/v1/_test/bookings', ['fare' => 5000], [Idempotency::HEADER => $key])
            ->assertCreated()
            ->assertJsonPath('data.execution', 1);

        $this->assertFalse($first->headers->has(Idempotency::REPLAY_HEADER));

        $replay = $this->actingAs($user)->postJson('/api/v1/_test/bookings', ['fare' => 5000], [Idempotency::HEADER => $key])
            ->assertCreated()
            ->assertHeader(Idempotency::REPLAY_HEADER, 'true')
            ->assertJsonPath('data.execution', 1);

        $this->assertSame($first->getContent(), $replay->getContent());
        $this->assertSame(1, $this->executions);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }

    public function test_same_key_with_different_payload_is_rejected(): void
    {
        $user = User::factory()->create();
        $key = (string) Str::uuid();

        $this->actingAs($user)->postJson('/api/v1/_test/bookings', ['fare' => 5000], [Idempotency::HEADER => $key])->assertCreated();

        $this->actingAs($user)->postJson('/api/v1/_test/bookings', ['fare' => 1], [Idempotency::HEADER => $key])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'idempotency_key_reused');

        $this->assertSame(1, $this->executions);
    }

    public function test_payload_key_order_does_not_change_the_hash(): void
    {
        $user = User::factory()->create();
        $key = (string) Str::uuid();

        $this->actingAs($user)->postJson('/api/v1/_test/bookings', ['fare' => 5000, 'note' => 'a'], [Idempotency::HEADER => $key])->assertCreated();
        $this->actingAs($user)->postJson('/api/v1/_test/bookings', ['note' => 'a', 'fare' => 5000], [Idempotency::HEADER => $key])
            ->assertHeader(Idempotency::REPLAY_HEADER, 'true');

        $this->assertSame(1, $this->executions);
    }

    public function test_concurrent_request_with_the_same_key_gets_409_while_the_first_is_in_progress(): void
    {
        $user = User::factory()->create();
        $key = (string) Str::uuid();
        $lockName = 'idempotency:'.hash('sha256', "user|{$user->id}|POST test.bookings.store|{$key}");

        $this->assertSame('redis', config('idempotency.lock_store'));
        $inFlight = Cache::store('redis')->lock($lockName, 30);
        $this->assertTrue($inFlight->get(), 'simulated in-flight request holds the lock');

        try {
            $this->actingAs($user)->postJson('/api/v1/_test/bookings', ['fare' => 5000], [Idempotency::HEADER => $key])
                ->assertStatus(409)
                ->assertJsonPath('errors._code', 'idempotency_request_in_progress');
        } finally {
            $inFlight->release();
        }

        $this->assertSame(0, $this->executions);

        $this->actingAs($user)->postJson('/api/v1/_test/bookings', ['fare' => 5000], [Idempotency::HEADER => $key])->assertCreated();
        $this->assertSame(1, $this->executions);
    }

    public function test_keys_are_scoped_per_actor(): void
    {
        $key = (string) Str::uuid();

        $this->actingAs(User::factory()->create())->postJson('/api/v1/_test/bookings', ['fare' => 1], [Idempotency::HEADER => $key])->assertCreated();
        $this->actingAs(User::factory()->create())->postJson('/api/v1/_test/bookings', ['fare' => 2], [Idempotency::HEADER => $key])
            ->assertCreated()
            ->assertJsonPath('data.fare', 2);

        $this->assertSame(2, $this->executions);
    }

    public function test_missing_or_invalid_key_is_rejected_on_unsafe_methods(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/_test/bookings', ['fare' => 1])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'idempotency_key_required');

        $this->actingAs($user)->postJson('/api/v1/_test/bookings', ['fare' => 1], [Idempotency::HEADER => 'not-a-uuid'])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'idempotency_key_invalid');

        $this->actingAs($user)->getJson('/api/v1/_test/bookings')->assertOk();
        $this->assertSame(0, $this->executions);
    }

    public function test_failed_responses_are_not_stored_so_the_client_can_retry(): void
    {
        $user = User::factory()->create();
        $key = (string) Str::uuid();

        $this->actingAs($user)->postJson('/api/v1/_test/failing', [], [Idempotency::HEADER => $key])->assertStatus(422);
        $this->actingAs($user)->postJson('/api/v1/_test/failing', [], [Idempotency::HEADER => $key])->assertStatus(422);

        $this->assertSame(2, $this->executions);
        $this->assertDatabaseCount('idempotency_keys', 0);
    }

    public function test_expired_keys_are_not_replayed_and_are_prunable(): void
    {
        $user = User::factory()->create();
        $key = (string) Str::uuid();

        $this->actingAs($user)->postJson('/api/v1/_test/bookings', ['fare' => 1], [Idempotency::HEADER => $key])->assertCreated();

        $this->travel(25)->hours();

        $this->actingAs($user)->postJson('/api/v1/_test/bookings', ['fare' => 1], [Idempotency::HEADER => $key])
            ->assertCreated()
            ->assertHeaderMissing(Idempotency::REPLAY_HEADER)
            ->assertJsonPath('data.execution', 2);

        $this->assertDatabaseCount('idempotency_keys', 1);

        $this->travel(25)->hours();
        $this->artisan('model:prune', ['--model' => [IdempotencyKey::class]])->assertSuccessful();
        $this->assertDatabaseCount('idempotency_keys', 0);
    }
}
