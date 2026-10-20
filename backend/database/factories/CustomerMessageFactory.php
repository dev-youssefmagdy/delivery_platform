<?php

namespace Database\Factories;

use App\Models\CustomerMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerMessage>
 */
class CustomerMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
        ];
    }
}
