<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tube_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->integer('qty_borrowed');
            $table->integer('qty_returned')->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->enum('status', ['dipinjam', 'sebagian_kembali', 'dikembalikan'])->default('dipinjam');
            $table->date('borrow_date');
            $table->date('return_date')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tube_loans');
    }
};
