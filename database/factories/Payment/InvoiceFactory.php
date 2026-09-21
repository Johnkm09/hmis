<?php

namespace Database\Factories\Payment;

use App\Models\Folio\Folio;
use App\Models\Payment\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 1000, 30000);
        $tax = fake()->randomFloat(2, 0, 3000);
        $discount = fake()->randomFloat(2, 0, 1000);

        return [
            'folio_id' => Folio::factory(),
            'invoice_number' => 'INV-' . fake()->unique()->numerify('########'),
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'discount_amount' => $discount,
            'total_amount' => $subtotal + $tax - $discount,
            'status' => 'issued',
            'issued_at' => now(),
            'issued_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
