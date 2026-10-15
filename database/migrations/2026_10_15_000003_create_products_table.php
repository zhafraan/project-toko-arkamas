<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->decimal('price_buy', 12, 2)->default(0);
            $table->decimal('price_sell', 12, 2)->default(0);
            $table->decimal('price_tube', 12, 2)->default(0);
            $table->integer('stock_filled')->default(0);
            $table->integer('stock_empty')->default(0);
            $table->integer('stock_borrowed')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
