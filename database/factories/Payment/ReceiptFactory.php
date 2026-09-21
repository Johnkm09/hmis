<?php

namespace Database\Factories\Payment;

use App\Models\Payment\Payment;
use App\Models\Payment\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'receipt_number' => 'RCT-' . fake()->unique()->numerify('######'),
            'issued_at' => now(),
            'issued_by' => User::factory(),
        ];
    }
}
