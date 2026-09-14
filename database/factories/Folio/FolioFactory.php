<?php

namespace Database\Factories\Folio;

use App\Models\Folio\Folio;
use App\Models\Reservation\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Folio>
 */
class FolioFactory extends Factory
{
    protected $model = Folio::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'status' => 'open',
            'opened_at' => now(),
            'closed_at' => null,
        ];
    }
}
