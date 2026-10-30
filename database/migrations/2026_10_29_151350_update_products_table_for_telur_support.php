<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category')->default('gas')->after('name');
            $table->string('unit')->default('tabung')->after('category');
            $table->decimal('min_stock', 12, 2)->default(5)->after('unit');
            
            $table->decimal('stock_filled', 12, 2)->default(0)->change();
            $table->decimal('stock_empty', 12, 2)->default(0)->change();
            $table->decimal('stock_borrowed', 12, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['category', 'unit', 'min_stock']);
            
            $table->integer('stock_filled')->default(0)->change();
            $table->integer('stock_empty')->default(0)->change();
            $table->integer('stock_borrowed')->default(0)->change();
        });
    }
};
