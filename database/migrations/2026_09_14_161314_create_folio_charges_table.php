<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('folio_charges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('folio_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('service_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->enum('type', [
                'accommodation',
                'service',
                'other',
            ]);

            $table->string('description');

            $table->decimal('quantity', 10, 2);

            $table->decimal('unit_price', 12, 2);

            $table->decimal('amount', 12, 2);

            $table->timestamp('charged_at');

            $table->timestamps();

            $table->index('type');
            $table->index('charged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folio_charges');
    }
};
