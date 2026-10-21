<?php

namespace Tests\Feature\Realtime;

use App\Enums\Admin\AdminPermission;
use App\Enums\Identity\UserStatus;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Dispatch\BookingParticipationRepositoryInterface;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsCustomers;
use Tests\Concerns\BuildsDispatch;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Private channel authorization (ADR-07, SRS §21): decided on the server from the signed-in account through
 * RealtimeChannelPolicy, so a guessed channel name never grants access. The tests run the real pusher-protocol
 * signing (Reverb driver) against POST /api/v1/broadcasting/auth and the admin session route.
 */
class ChannelAuthorizationTest extends TestCase
{
    use BuildsCustomers;
    use BuildsDispatch;
    use InteractsWithAdmin;
    use RefreshDatabase;

    private const BOOKING = '44444444-4444-4444-8444-444444444444';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
        // Channels are registered on the broadcaster that is the default when routes/channels.php loads (null in
        // tests): register them again on the Reverb one.
        app(BroadcastManager::class)->purge();
        require base_path('routes/channels.php');
    }

    private function authorizeChannel(string $channel)
    {
        return $this->postJson(route('api.v1.broadcasting.auth'), ['socket_id' => '1234.5678', 'channel_name' => $channel]);
    }

    private function participation(array $customerOf = [], array $providerOf = []): void
    {
        $this->app->instance(BookingParticipationRepositoryInterface::class, new class($customerOf, $providerOf) implements BookingParticipationRepositoryInterface
        {
            public function __construct(private readonly array $customers, private readonly array $providers) {}

            public function isCustomerOf(string $bookingUuid, int $userId): bool
            {
                return in_array([$bookingUuid, $userId], $this->customers, true);
            }

            public function isProviderOf(string $bookingUuid, int $providerProfileId): bool
            {
                return in_array([$bookingUuid, $providerProfileId], $this->providers, true);
            }
        });
    }

    private function asCustomer(?User $user = null): User
    {
        $user = ($user ?? $this->customer())->refresh();
        Sanctum::actingAs($user, ['customer']);

        return $user;
    }

    private function asProvider(?ProviderProfile $profile = null): ProviderProfile
    {
        $profile ??= $this->makeProvider();
        Sanctum::actingAs(User::query()->findOrFail($profile->user_id), ['provider']);

        return $profile;
    }

    public function test_a_customer_joins_only_their_own_channel(): void
    {
        $me = $this->asCustomer();
        $other = $this->customer();

        $this->authorizeChannel('private-customer.'.$me->uuid)->assertOk()->assertJsonStructure(['auth']);
        $this->authorizeChannel('private-customer.'.$other->uuid)->assertForbidden();
        $this->authorizeChannel('private-customer.'.self::BOOKING)->assertForbidden();
    }

    public function test_a_provider_joins_only_their_own_channel_and_not_a_customers(): void
    {
        $mine = $this->asProvider();
        $other = $this->makeProvider();

        $this->authorizeChannel('private-provider.'.$mine->uuid)->assertOk()->assertJsonStructure(['auth']);
        $this->authorizeChannel('private-provider.'.$other->uuid)->assertForbidden();
        $this->authorizeChannel('private-customer.'.User::query()->findOrFail($mine->user_id)->uuid)->assertForbidden();
    }

    public function test_a_customer_cannot_join_another_customers_booking_channel(): void
    {
        $owner = $this->customer();
        $stranger = $this->customer();
        $this->participation(customerOf: [[self::BOOKING, $owner->id]]);

        $this->asCustomer($owner);
        $this->authorizeChannel('private-booking.'.self::BOOKING)->assertOk();

        $this->asCustomer($stranger);
        $this->authorizeChannel('private-booking.'.self::BOOKING)->assertForbidden();
    }

    public function test_only_dispatched_or_selected_providers_join_a_booking_channel(): void
    {
        $dispatched = $this->makeProvider();
        $other = $this->makeProvider();
        $this->participation(providerOf: [[self::BOOKING, $dispatched->id]]);

        $this->asProvider($dispatched);
        $this->authorizeChannel('private-booking.'.self::BOOKING)->assertOk();

        $this->asProvider($other);
        $this->authorizeChannel('private-booking.'.self::BOOKING)->assertForbidden();
    }

    public function test_a_customer_token_does_not_open_provider_participation_and_vice_versa(): void
    {
        $provider = $this->makeProvider();
        $providerUser = User::query()->findOrFail($provider->user_id);
        $this->participation(providerOf: [[self::BOOKING, $provider->id]], customerOf: [[self::BOOKING, $providerUser->id]]);

        Sanctum::actingAs($providerUser->refresh(), ['customer']);
        $this->authorizeChannel('private-booking.'.self::BOOKING)->assertOk();
        $this->authorizeChannel('private-provider.'.$provider->uuid)->assertForbidden();
    }

    public function test_without_bookings_nobody_may_join_a_booking_channel(): void
    {
        $this->asCustomer();
        $this->authorizeChannel('private-booking.'.self::BOOKING)->assertForbidden();

        $this->asProvider();
        $this->authorizeChannel('private-booking.'.self::BOOKING)->assertForbidden();
    }

    public function test_guests_and_blocked_accounts_cannot_authorize(): void
    {
        $this->authorizeChannel('private-customer.'.self::BOOKING)->assertUnauthorized();

        $user = $this->customer(status: UserStatus::Suspended);
        $this->asCustomer($user);
        $this->authorizeChannel('private-customer.'.$user->uuid)->assertForbidden();
    }

    public function test_unknown_channels_are_refused(): void
    {
        $this->asCustomer();

        $this->authorizeChannel('private-anything.else')->assertForbidden();
        $this->authorizeChannel('private-admin.operations')->assertForbidden();
    }

    public function test_the_admin_operations_channel_is_permission_gated(): void
    {
        $this->actingAsAdmin([AdminPermission::BookingsView]);
        $this->postJson('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-admin.operations'])
            ->assertOk()->assertJsonStructure(['auth']);
    }

    public function test_an_admin_without_the_permission_is_refused(): void
    {
        $this->actingAsAdmin([AdminPermission::DriversView]);

        $this->postJson('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-admin.operations'])->assertForbidden();
    }

    public function test_a_mobile_token_cannot_join_the_admin_channel(): void
    {
        $this->asCustomer();

        $this->postJson('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-admin.operations'])->assertForbidden();
    }
}
