<?php

namespace Database\Factories\Reservation;

use App\Models\Guest\Guest;
use App\Models\Reservation\Reservation;
use App\Models\Room\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('+1 day', '+30 days');
        $checkOut = (clone $checkIn)->modify('+' . fake()->numberBetween(1, 5) . ' days');

        return [
            'guest_id' => Guest::factory(),
            'room_id' => Room::factory(),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'number_of_guests' => 1,
            'nightly_rate' => 0,
            'total_amount' => 0,
            'status' => 'pending',
        ];
    }
}
