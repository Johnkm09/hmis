<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('mpesa_phone')->nullable();

            $table->string('mpesa_merchant_request_id')
                ->nullable();

            $table->string('mpesa_checkout_request_id')
                ->nullable()
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex([
                'mpesa_checkout_request_id',
            ]);

            $table->dropColumn([
                'mpesa_phone',
                'mpesa_merchant_request_id',
                'mpesa_checkout_request_id',
            ]);
        });
    }
};
