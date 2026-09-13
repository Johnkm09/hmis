<?php

namespace Database\Factories\Contract;

use App\Models\Contract\Operation;
use App\Models\Reservation\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Operation>
 */
class OperationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'type' => fake()->randomElement([
                'check_in',
                'check_out',
            ]),
            'performed_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'performed_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
