<?php

namespace Database\Factories\RoomType;

use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name =  fake()->unique()->words(5, true);
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => fake()->sentence(),
        ];
    }
}
