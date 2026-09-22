<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex([
                'mpesa_checkout_request_id',
            ]);

            $table->unique('mpesa_checkout_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique([
                'mpesa_checkout_request_id_unique',
            ]);

            $table->index('mpesa_checkout_request_id');
        });
    }
};
