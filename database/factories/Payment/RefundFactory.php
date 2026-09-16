<?php

namespace Database\Factories\Payment;

use App\Models\Payment\Refund;
use App\Models\Payment\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    protected $model = Refund::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'reason' => fake()->sentence(),
            'status' => 'completed',
            'refunded_at' => now(),
            'refunded_by' => User::factory(),
            'transaction_reference' => fake()->optional()->bothify('REF-########'),
        ];
    }
}
