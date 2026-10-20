<?php

namespace Database\Factories;

use App\Enums\Geo\SavedPlaceType;
use App\Models\SavedPlace;
use App\Models\User;
use App\Support\Geo\GeoPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedPlace>
 */
class SavedPlaceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => 'Place '.fake()->unique()->numerify('###'),
            'type' => SavedPlaceType::Other,
            'point' => GeoPoint::make(30.05, 31.25),
            'address' => ['title' => 'Somewhere', 'line' => 'Somewhere, Cairo', 'precision' => 'street', 'provider' => 'fake'],
        ];
    }
}
