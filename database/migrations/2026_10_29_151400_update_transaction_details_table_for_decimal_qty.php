<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_details', 'transaction_type')) {
                $table->string('transaction_type', 20)->change();
            }
            
            if (Schema::hasColumn('transaction_details', 'qty')) {
                $table->decimal('qty', 12, 2)->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_details', 'transaction_type')) {
                $table->enum('transaction_type', ['refill', 'pinjam_tabung', 'plus_tabung'])->change();
            }
            
            if (Schema::hasColumn('transaction_details', 'qty')) {
                $table->integer('qty')->change();
            }
        });
    }
};
