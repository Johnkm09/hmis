<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('folio_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('amount', 12, 2);

            $table->enum('method', [
                'mpesa',
                'stripe',
                'cash',
            ]);

            $table->string('provider')->nullable();

            $table->string('transaction_reference')->nullable();

            $table->enum('status', [
                'pending',
                'completed',
                'failed',
            ])->default('pending');

            $table->timestamp('paid_at')->nullable();

            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('method');
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
