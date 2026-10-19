<?php

namespace Database\Factories;

use App\Enums\Admin\AdminStatus;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    protected static ?string $password;

    /**
     * Plain password of every factory admin.
     */
    public const PASSWORD = 'Str0ng-Password!';

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+2010'.fake()->unique()->numerify('########'),
            'password' => static::$password ??= Hash::make(self::PASSWORD),
            'locale' => null,
            'status' => AdminStatus::Active,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => ['status' => AdminStatus::Suspended, 'suspended_at' => now()]);
    }
}
