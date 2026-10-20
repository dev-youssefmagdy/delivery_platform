<?php

namespace Database\Factories;

use App\Enums\Identity\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => null,
            'preferred_locale' => 'ar',
        ];
    }

    /**
     * A verified account (as created by a successful OTP sign-in).
     */
    public function configure(): static
    {
        return $this->afterMaking(function (User $user): void {
            $user->phone_e164 ??= '+2010'.fake()->unique()->numerify('########');
            $user->phone_verified_at ??= now();
            $user->status ??= UserStatus::Active;
        });
    }

    public function withoutProfile(): static
    {
        return $this->state(['name' => null]);
    }

    public function status(UserStatus $status): static
    {
        return $this->afterMaking(static fn (User $user) => $user->status = $status);
    }

    public function unverified(): static
    {
        return $this->afterMaking(static fn (User $user) => $user->phone_verified_at = null);
    }
}
