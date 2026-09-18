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
        Schema::table('milk_batches', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('tested_by');
            $table->string('vehicle_number')->nullable()->after('driver_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milk_batches', function (Blueprint $table) {
            $table->dropColumn(['driver_name', 'vehicle_number']);
        });
    }
};
