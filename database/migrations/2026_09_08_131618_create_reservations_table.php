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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('guest_id')
                ->constrained('guests')
                ->restrictOnDelete();

            $table->foreignId('room_id')
                ->constrained('rooms')
                ->restrictOnDelete();

            $table->date('check_in');
            $table->date('check_out');

            $table->unsignedInteger('number_of_guests');

            $table->decimal('nightly_rate', 10, 2)->unsigned();
            $table->decimal('total_amount', 10, 2)->unsigned();

            $table->string('status')->default('pending');

            $table->index(['room_id', 'check_in', 'check_out']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
