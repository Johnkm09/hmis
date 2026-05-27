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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('room_number')->unique();
            $table->decimal('price_per_night',10,2);
            $table->integer('capacity')->comment("number of guests");
            $table->enum('status',[
                'available',
                'occupied',
                'reserved',
                'maintenance'
            ])->default('available');
            $table->index(['status','price_per_night','room_type_id']);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
