<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->unsignedInteger('filled_before');
            $table->unsignedInteger('filled_after');
            $table->unsignedInteger('empty_before');
            $table->unsignedInteger('empty_after');
            $table->unsignedInteger('borrowed_before');
            $table->unsignedInteger('borrowed_after');

            $table->string('reason');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
