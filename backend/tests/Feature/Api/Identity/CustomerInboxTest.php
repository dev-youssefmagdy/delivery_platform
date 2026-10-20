<?php

namespace Tests\Feature\Api\Identity;

use App\Models\CustomerMessage;
use App\Models\User;

/**
 * `GET /me/messages`: the help messages support sent to the signed-in customer.
 */
class CustomerInboxTest extends IdentityTestCase
{
    public function test_customer_reads_only_their_own_messages_and_marks_them_read(): void
    {
        $token = $this->signIn('+201001234567');
        $me = User::query()->where('phone_e164', '+201001234567')->firstOrFail();
        $mine = CustomerMessage::factory()->create(['user_id' => $me->id, 'subject' => 'Welcome back']);
        $foreign = CustomerMessage::factory()->create();

        $this->getJson('/api/v1/me/messages', $this->bearer($token))->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->uuid)
            ->assertJsonPath('data.0.subject', 'Welcome back')
            ->assertJsonPath('data.0.read', false);

        $this->postJson("/api/v1/me/messages/{$mine->uuid}/read", [], $this->bearer($token))->assertOk()->assertJsonPath('data.read', true);
        $this->postJson("/api/v1/me/messages/{$foreign->uuid}/read", [], $this->bearer($token))->assertNotFound();
        $this->assertNull($foreign->refresh()->read_at);
    }

    public function test_requires_a_customer_token(): void
    {
        $this->getJson('/api/v1/me/messages')->assertUnauthorized();
        $provider = $this->signIn('+201001234568', 'provider');
        $this->getJson('/api/v1/me/messages', $this->bearer($provider))->assertForbidden();
    }
}
