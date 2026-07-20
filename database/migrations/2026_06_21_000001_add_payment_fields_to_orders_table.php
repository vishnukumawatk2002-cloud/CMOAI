<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_provider')->nullable()->after('currency');
            $table->string('transaction_id')->nullable()->unique()->after('payment_provider');
            $table->string('billing_cycle', 20)->nullable()->after('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_provider', 'transaction_id', 'billing_cycle']);
        });
    }
};
