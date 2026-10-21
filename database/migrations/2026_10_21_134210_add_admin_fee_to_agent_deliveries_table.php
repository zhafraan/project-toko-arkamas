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
        Schema::table('agent_deliveries', function (Blueprint $table) {
            // Biaya admin/pengiriman dari agen (opsional)
            $table->decimal('admin_fee', 12, 2)->default(0)->after('total_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_deliveries', function (Blueprint $table) {
            $table->dropColumn('admin_fee');
        });
    }
};
