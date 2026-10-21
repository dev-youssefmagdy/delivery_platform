<?php

use App\Models\Admin;
use App\Models\User;
use App\Policies\RealtimeChannelPolicy;
use Illuminate\Support\Facades\Broadcast;

/*
| Private broadcast channels (ADR-07, Plan §7.5, SRS §21). Every callback delegates to RealtimeChannelPolicy.
| Mobile apps authorize through POST /api/v1/broadcasting/auth (Sanctum token); the admin panel through the session
| guard on the default /broadcasting/auth. A channel is bound to the guard that may use it.
*/

Broadcast::channel('customer.{userUuid}', static fn (User $user, string $userUuid): bool => app(RealtimeChannelPolicy::class)->joinCustomer($user, $userUuid), ['guards' => ['sanctum']]);

Broadcast::channel('provider.{providerUuid}', static fn (User $user, string $providerUuid): bool => app(RealtimeChannelPolicy::class)->joinProvider($user, $providerUuid), ['guards' => ['sanctum']]);

Broadcast::channel('booking.{bookingUuid}', static fn (User $user, string $bookingUuid): bool => app(RealtimeChannelPolicy::class)->joinBooking($user, $bookingUuid), ['guards' => ['sanctum']]);

Broadcast::channel('admin.operations', static fn (Admin $admin): bool => app(RealtimeChannelPolicy::class)->joinAdminOperations($admin), ['guards' => ['admin']]);
