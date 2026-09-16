<?php

namespace Database\Factories\Payment;

use App\Models\Folio\Folio;
use App\Models\Payment\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'folio_id' => Folio::factory(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'method' => fake()->randomElement([
                'mpesa',
                'stripe',
                'cash',
            ]),
            'provider' => fake()->optional()->company(),
            'transaction_reference' => fake()->optional()->bothify('TXN-########'),
            'status' => 'completed',
            'paid_at' => now(),
            'received_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
