<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('transaction_date');
            $table->index('payment_status');
            $table->index('payment_method');
        });

        Schema::table('tube_loans', function (Blueprint $table) {
            $table->index('status');
            $table->index('borrow_date');
        });

        Schema::table('debt_payments', function (Blueprint $table) {
            $table->index('payment_date');
        });

        Schema::table('agent_deliveries', function (Blueprint $table) {
            $table->index('delivery_date');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('name');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['transaction_date']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['payment_method']);
        });

        Schema::table('tube_loans', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['borrow_date']);
        });

        Schema::table('debt_payments', function (Blueprint $table) {
            $table->dropIndex(['payment_date']);
        });

        Schema::table('agent_deliveries', function (Blueprint $table) {
            $table->dropIndex(['delivery_date']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['phone']);
        });
    }
};

