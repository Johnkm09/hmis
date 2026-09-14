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
        Schema::table('folio_charges', function (Blueprint $table) {
            $table->foreignId('charged_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->after('charged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folio_charges', function (Blueprint $table) {
            $table->dropForeign(['charged_by']);
            $table->dropColumn('charged_by');
        });
    }
};
