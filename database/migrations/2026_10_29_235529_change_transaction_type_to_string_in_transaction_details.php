<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_details', 'transaction_type')) {
                $table->string('transaction_type', 20)->default('refill')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_details', 'transaction_type')) {
                $table->enum('transaction_type', ['refill', 'pinjam_tabung', 'plus_tabung'])->change();
            }
        });
    }
};
