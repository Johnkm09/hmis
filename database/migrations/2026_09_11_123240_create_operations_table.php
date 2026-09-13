<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reservation_id')
                ->constrained()
                ->restrictOnDelete();

            $table->enum('type', [
                'check_in',
                'check_out',
            ]);

            $table->timestamp('performed_at');

            $table->foreignId('performed_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('type');
            $table->index('performed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};
