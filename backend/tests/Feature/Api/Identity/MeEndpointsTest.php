<?php

namespace Tests\Feature\Api\Identity;

use App\Enums\Identity\UserStatus;
use App\Enums\Policy\PolicyType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

class MeEndpointsTest extends IdentityTestCase
{
    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401)->assertJsonPath('errors._code', 'unauthenticated');
    }

    public function test_customer_token_is_rejected_on_provider_scoped_routes_and_vice_versa(): void
    {
        \Illuminate\Support\Facades\Route::middleware(['api', 'auth:sanctum', 'ability:provider'])->get('/api/v1/_t/provider', fn () => ['ok' => true]);
        \Illuminate\Support\Facades\Route::middleware(['api', 'auth:sanctum', 'ability:customer'])->get('/api/v1/_t/customer', fn () => ['ok' => true]);

        $customer = $this->signIn('+201001111111', 'customer');
        $this->travel(61)->seconds();
        $provider = $this->signIn('+201002222222', 'provider');

        $this->getJson('/api/v1/_t/provider', $this->bearer($customer))->assertStatus(403);
        $this->getJson('/api/v1/_t/customer', $this->bearer($provider))->assertStatus(403);
        $this->getJson('/api/v1/_t/customer', $this->bearer($customer))->assertOk();
        $this->getJson('/api/v1/_t/provider', $this->bearer($provider))->assertOk();
        $this->getJson('/api/v1/me', $this->bearer($provider))->assertOk()->assertJsonPath('data.app', 'provider')->assertJsonPath('data.customer_profile', null);
    }

    public function test_patch_me_updates_allowed_fields_only(): void
    {
        $token = $this->signIn();
        $user = User::query()->firstOrFail();

        $this->patchJson('/api/v1/me', [
            'name' => 'Mona Ali', 'email' => 'mona@example.test', 'preferred_locale' => 'en',
            'accessibility_needs' => 'wheelchair', 'emergency_contact_name' => 'Omar', 'emergency_contact_phone' => '+201005555555',
            'marketing_opt_in' => true,
            'status' => 'suspended', 'phone_e164' => '+201009999999', 'id' => 99, 'uuid' => 'x', 'phone_verified_at' => null,
        ], $this->bearer($token))->assertOk()
            ->assertJsonPath('data.name', 'Mona Ali')->assertJsonPath('data.profile_complete', true)
            ->assertJsonPath('data.customer_profile.marketing_opt_in', true)
            ->assertJsonPath('data.customer_profile.emergency_contact_phone', '+201005555555');

        $user->refresh();
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertSame('+201001234567', $user->phone_e164);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertSame('en', $user->preferred_locale);
        $this->assertNotNull($user->customerProfile->marketing_opt_in_at);
    }

    public function test_patch_validation_errors_are_field_keyed(): void
    {
        $token = $this->signIn();

        $this->patchJson('/api/v1/me', ['name' => 'A', 'email' => 'nope', 'preferred_locale' => 'fr'], $this->bearer($token))
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'preferred_locale'], 'errors');
    }

    public function test_users_only_ever_touch_their_own_record(): void
    {
        $a = $this->signIn('+201001111111');
        $this->travel(61)->seconds();
        $b = $this->signIn('+201002222222');

        $this->patchJson('/api/v1/me', ['name' => 'Alice Aa'], $this->bearer($a))->assertOk();
        $this->patchJson('/api/v1/me', ['name' => 'Bobby Bb', 'id' => User::query()->where('phone_e164', '+201001111111')->value('id')], $this->bearer($b))->assertOk();

        $this->assertSame('Alice Aa', User::query()->where('phone_e164', '+201001111111')->value('name'));
        $this->assertSame('Bobby Bb', User::query()->where('phone_e164', '+201002222222')->value('name'));
    }

    public function test_photo_upload_is_private_signed_and_replaces_the_old_file(): void
    {
        Storage::fake('local');
        $token = $this->signIn();

        $first = $this->postJson('/api/v1/me/photo', ['photo' => UploadedFile::fake()->image('a.png', 300, 200)], $this->bearer($token))->assertOk();
        $url = (string) $first->json('data.photo_url');
        $this->assertStringContainsString('signature=', $url);
        $path = User::query()->firstOrFail()->photo_path;
        Storage::disk('local')->assertExists($path);
        $this->assertStringEndsWith('.jpg', $path);

        $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(preg_replace('/signature=[^&]+/', 'signature=bad', $url))->assertStatus(403);
        $this->get('/api/v1/media/users/'.User::query()->firstOrFail()->uuid.'/photo')->assertStatus(403);

        $this->postJson('/api/v1/me/photo', ['photo' => UploadedFile::fake()->image('b.jpg', 200, 200)], $this->bearer($token))->assertOk();
        Storage::disk('local')->assertMissing($path);
    }

    public function test_photo_validation_rejects_non_images(): void
    {
        Storage::fake('local');
        $token = $this->signIn();

        $this->postJson('/api/v1/me/photo', ['photo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')], $this->bearer($token))->assertStatus(422);
        $this->postJson('/api/v1/me/photo', ['photo' => UploadedFile::fake()->create('big.jpg', 9000, 'image/jpeg')], $this->bearer($token))->assertStatus(422);
        $this->postJson('/api/v1/me/photo', [], $this->bearer($token))->assertStatus(422);
    }

    public function test_policy_endpoint_serves_the_current_published_text_localised(): void
    {
        $this->getJson('/api/v1/policies/terms')->assertStatus(404);

        $old = $this->publishPolicy(PolicyType::Terms, 1);
        $new = $this->publishPolicy(PolicyType::Terms, 2);
        $new->update(['title' => ['ar' => 'الشروط', 'en' => 'Terms'], 'content' => ['ar' => 'نص', 'en' => 'Text']]);

        $this->getJson('/api/v1/policies/terms', ['Accept-Language' => 'en'])->assertOk()
            ->assertJsonPath('data.id', $new->uuid)->assertJsonPath('data.version', 2)->assertJsonPath('data.title', 'Terms')->assertJsonPath('data.content', 'Text');
        $this->getJson('/api/v1/policies/terms', ['Accept-Language' => 'ar'])->assertJsonPath('data.title', 'الشروط');
        $this->getJson('/api/v1/policies/bogus')->assertStatus(404);
        $this->assertNotSame($old->uuid, $new->uuid);
    }

    public function test_consents_reference_the_current_policy_versions(): void
    {
        $terms = $this->publishPolicy(PolicyType::Terms, 1);
        $privacy = $this->publishPolicy(PolicyType::Privacy, 1);
        $token = $this->signIn();

        $this->getJson('/api/v1/me', $this->bearer($token))->assertJsonCount(2, 'data.consents.pending');
        $this->getJson('/api/v1/me/consents', $this->bearer($token))->assertOk()->assertJsonCount(2, 'data.required')->assertJsonCount(0, 'data.accepted');

        $this->postJson('/api/v1/me/consents', ['policies' => [$terms->uuid, $privacy->uuid], 'marketing_opt_in' => false], $this->bearer($token))
            ->assertOk()->assertJsonCount(0, 'data.pending')->assertJsonCount(2, 'data.accepted');

        $user = User::query()->firstOrFail();
        $this->assertDatabaseHas('user_consents', ['user_id' => $user->id, 'policy_version_id' => $terms->id, 'locale' => 'en', 'app' => 'customer']);
        // Accepting again is harmless.
        $this->postJson('/api/v1/me/consents', ['policies' => [$terms->uuid]], $this->bearer($token))->assertOk();
        $this->assertSame(2, $user->consents()->count());

        // A new published version makes the consent pending again.
        $v2 = $this->publishPolicy(PolicyType::Terms, 2);
        $this->getJson('/api/v1/me', $this->bearer($token))->assertJsonPath('data.consents.pending.0.id', $v2->uuid);
        $this->postJson('/api/v1/me/consents', ['policies' => [$terms->uuid]], $this->bearer($token))
            ->assertStatus(422)->assertJsonPath('errors._code', 'policy_version_not_current');
    }

    public function test_consent_validation(): void
    {
        $token = $this->signIn();

        $this->postJson('/api/v1/me/consents', [], $this->bearer($token))->assertStatus(422);
        $this->postJson('/api/v1/me/consents', ['policies' => ['not-a-uuid']], $this->bearer($token))->assertStatus(422);
        $this->postJson('/api/v1/me/consents', ['policies' => [(string) \Illuminate\Support\Str::uuid()]], $this->bearer($token))
            ->assertStatus(422)->assertJsonPath('errors._code', 'policy_version_not_current');
    }

    public function test_devices_are_registered_and_push_tokens_move_between_users(): void
    {
        $a = $this->signIn('+201001111111');
        $this->travel(61)->seconds();
        $b = $this->signIn('+201002222222', device: 'dev-b');

        $this->postJson('/api/v1/me/devices', ['device_id' => 'dev-1', 'platform' => 'android', 'push_token' => 'tok-1', 'app_version' => '1.2.0'], $this->bearer($a))
            ->assertOk()->assertJsonPath('data.has_push_token', true)->assertJsonMissingPath('data.push_token');
        $this->postJson('/api/v1/me/devices', ['device_id' => 'dev-b', 'platform' => 'ios', 'push_token' => 'tok-1'], $this->bearer($b))->assertOk();

        $this->assertDatabaseHas('user_devices', ['device_id' => 'dev-1', 'push_token' => null, 'app_version' => '1.2.0']);
        $this->assertSame(1, \App\Models\UserDevice::query()->where('push_token_hash', hash('sha256', 'tok-1'))->count());
        $this->postJson('/api/v1/me/devices', ['device_id' => 'x', 'platform' => 'plan9'], $this->bearer($a))->assertStatus(422);
    }

    public function test_logout_revokes_only_the_current_token_and_clears_its_push_token(): void
    {
        $one = $this->signIn(device: 'dev-1');
        $this->travel(61)->seconds();
        $two = $this->signIn(device: 'dev-2');
        $this->postJson('/api/v1/me/devices', ['device_id' => 'dev-1', 'platform' => 'android', 'push_token' => 'tok'], $this->bearer($one))->assertOk();

        $this->postJson('/api/v1/auth/logout', [], $this->bearer($one))->assertOk();
        $this->assertSame(1, PersonalAccessToken::query()->count());
        $this->assertDatabaseHas('user_devices', ['device_id' => 'dev-1', 'push_token' => null]);

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/me', $this->bearer($one))->assertStatus(401);
        $this->getJson('/api/v1/me', $this->bearer($two))->assertOk();
    }

    public function test_refresh_reissues_the_token(): void
    {
        $token = $this->signIn();

        $new = (string) $this->postJson('/api/v1/auth/refresh', [], $this->bearer($token))->assertOk()->assertJsonPath('data.abilities', ['customer'])->json('data.token');

        $this->assertNotSame($token, $new);
        $this->assertSame(1, PersonalAccessToken::query()->count());
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/me', $this->bearer($token))->assertStatus(401);
        $this->getJson('/api/v1/me', $this->bearer($new))->assertOk();
    }

    public function test_expired_tokens_are_rejected(): void
    {
        $token = $this->signIn();
        $this->travel(91)->days();

        $this->getJson('/api/v1/me', $this->bearer($token))->assertStatus(401);
    }

    public function test_account_deletion_request_lifecycle(): void
    {
        $token = $this->signIn();
        $key = ['Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()];

        $this->postJson('/api/v1/me/account-deletion-requests', ['reason' => 'Leaving'])->assertStatus(401);
        $this->postJson('/api/v1/me/account-deletion-requests', [], $this->bearer($token))->assertStatus(422)->assertJsonPath('errors._code', 'idempotency_key_required');

        $created = $this->postJson('/api/v1/me/account-deletion-requests', ['reason' => 'Leaving'], $this->bearer($token) + $key)->assertCreated()->assertJsonPath('data.status', 'pending');
        $id = (string) $created->json('data.id');
        $this->assertSame(UserStatus::DeletionPending, User::query()->firstOrFail()->status);

        $this->getJson('/api/v1/me', $this->bearer($token))->assertOk()->assertJsonPath('data.status', 'deletion_pending')->assertJsonPath('data.deletion_request.id', $id);
        // Replay of the same intent returns the same response; business endpoints are blocked with a typed code.
        $this->postJson('/api/v1/me/account-deletion-requests', ['reason' => 'Leaving'], $this->bearer($token) + $key)->assertCreated()->assertHeader('Idempotent-Replayed', 'true');
        $this->patchJson('/api/v1/me', ['name' => 'Someone'], $this->bearer($token))->assertStatus(403)->assertJsonPath('errors._code', 'account_deletion_pending');

        $this->deleteJson("/api/v1/me/account-deletion-requests/{$id}", [], $this->bearer($token))->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertSame(UserStatus::Active, User::query()->firstOrFail()->status);
        $this->deleteJson("/api/v1/me/account-deletion-requests/{$id}", [], $this->bearer($token))->assertStatus(422)->assertJsonPath('errors._code', 'deletion_request_not_pending');
    }

    public function test_deletion_requests_cannot_be_cancelled_by_another_user(): void
    {
        $a = $this->signIn('+201001111111');
        $this->travel(61)->seconds();
        $b = $this->signIn('+201002222222');
        $id = (string) $this->postJson('/api/v1/me/account-deletion-requests', [], $this->bearer($a) + ['Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()])->assertCreated()->json('data.id');

        $this->deleteJson("/api/v1/me/account-deletion-requests/{$id}", [], $this->bearer($b))->assertStatus(404);
        $this->assertSame(UserStatus::DeletionPending, User::query()->where('phone_e164', '+201001111111')->firstOrFail()->status);
    }

    public function test_account_status_gates_with_typed_codes(): void
    {
        $token = $this->signIn();
        $user = User::query()->firstOrFail();

        foreach ([
            [UserStatus::Suspended, 'account_suspended'],
            [UserStatus::Deleted, 'account_deleted'],
            [UserStatus::VerificationRequired, 'account_verification_required'],
        ] as [$status, $code]) {
            $user->forceFill(['status' => $status])->save();
            $this->patchJson('/api/v1/me', ['name' => 'Same Name'], $this->bearer($token))->assertStatus(403)->assertJsonPath('errors._code', $code);
            $this->postJson('/api/v1/auth/refresh', [], $this->bearer($token))->assertStatus(403)->assertJsonPath('errors._code', $code);
            // Status-safe endpoints still answer so the app can show the right screen.
            $this->getJson('/api/v1/me', $this->bearer($token))->assertOk()->assertJsonPath('data.status', $status->value);
        }

        $user->forceFill(['status' => UserStatus::Restricted])->save();
        $this->patchJson('/api/v1/me', ['name' => 'Same Name'], $this->bearer($token))->assertOk();
    }

    public function test_last_seen_is_refreshed_at_most_every_interval(): void
    {
        $token = $this->signIn();
        $this->getJson('/api/v1/me', $this->bearer($token))->assertOk();
        $first = User::query()->firstOrFail()->last_seen_at;
        $this->assertNotNull($first);

        $this->travel(60)->seconds();
        $this->getJson('/api/v1/me', $this->bearer($token));
        $this->assertTrue($first->equalTo(User::query()->firstOrFail()->last_seen_at));

        $this->travel(301)->seconds();
        $this->getJson('/api/v1/me', $this->bearer($token));
        $this->assertTrue(User::query()->firstOrFail()->last_seen_at->gt($first));
    }
}
