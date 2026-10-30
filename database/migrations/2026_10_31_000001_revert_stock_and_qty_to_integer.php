<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'stock_crates')) {
                $table->dropColumn('stock_crates');
            }
            $table->integer('min_stock')->default(5)->change();
            $table->integer('stock_filled')->default(0)->change();
            $table->integer('stock_empty')->default(0)->change();
            $table->integer('stock_borrowed')->default(0)->change();
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->integer('qty')->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('min_stock', 12, 2)->default(5)->change();
            $table->decimal('stock_filled', 12, 2)->default(0)->change();
            $table->decimal('stock_empty', 12, 2)->default(0)->change();
            $table->decimal('stock_borrowed', 12, 2)->default(0)->change();
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->decimal('qty', 12, 2)->default(1)->change();
        });
    }
};
