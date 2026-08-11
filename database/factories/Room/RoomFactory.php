<?php

namespace Database\Factories\Room;

use App\Models\Room\Room;
use App\Models\RoomType\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'room_number' => fake()->unique()->numerify('###'),
            'floor_no' => fake()->numberBetween(1, 10),
            'status' => 'available',
            'price' => fake()->randomFloat(2, 50, 500),
            'is_active' => true,
        ];
    }
}
