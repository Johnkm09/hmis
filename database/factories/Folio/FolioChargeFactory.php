<?php

namespace Database\Factories\Folio;

use App\Models\Folio\Folio;
use App\Models\Folio\FolioCharge;
use App\Models\Service\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FolioCharge>
 */
class FolioChargeFactory extends Factory
{
    protected $model = FolioCharge::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 5);
        $unitPrice = fake()->randomFloat(2, 100, 10000);

        return [
            'folio_id' => Folio::factory(),
            'service_id' => Service::factory(),
            'type' => 'service',
            'description' => fake()->sentence(3),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => round($quantity * $unitPrice, 2),
            'charged_at' => now(),
            'charged_by' => User::factory(),
        ];
    }
}
