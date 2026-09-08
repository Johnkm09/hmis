<?php

namespace Database\Factories\Guest;

use App\Models\Guest\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'id_number'    => $this->faker->unique()->bothify('########'),
            'phone_number' => $this->faker->phoneNumber(),
            'email' => $this->faker->email(),
            'country' => $this->faker->country(),
            'city' => $this->faker->city(),
            'address' => $this->faker->address(),
        ];
    }
}
