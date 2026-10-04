<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('collection_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('district')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('plate_number');
            $table->string('driver_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'plate_number']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::table('milk_batches', function (Blueprint $table) {
            $table->foreignId('collection_center_id')
                ->nullable()
                ->after('collection_center')
                ->constrained('collection_centers')
                ->nullOnDelete();

            $table->foreignId('vehicle_id')
                ->nullable()
                ->after('vehicle_number')
                ->constrained('vehicles')
                ->nullOnDelete();
        });

        // Backfill existing batches so historical data links to company assets seamlessly
        $now = now();
        $batches = DB::table('milk_batches')
            ->whereNotNull('company_id')
            ->get();

        foreach ($batches as $batch) {
            $centerId = null;
            $vehicleId = null;

            if (! empty($batch->collection_center)) {
                $centerName = trim((string) $batch->collection_center);
                $center = DB::table('collection_centers')
                    ->where('company_id', $batch->company_id)
                    ->where('name', $centerName)
                    ->first();

                if (! $center) {
                    $centerId = DB::table('collection_centers')->insertGetId([
                        'company_id' => $batch->company_id,
                        'name' => $centerName,
                        'district' => $batch->district ?? null,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $centerId = $center->id;
                }
            }

            if (! empty($batch->vehicle_number)) {
                $plateNumber = strtoupper(trim((string) $batch->vehicle_number));
                $vehicle = DB::table('vehicles')
                    ->where('company_id', $batch->company_id)
                    ->where('plate_number', $plateNumber)
                    ->first();

                if (! $vehicle) {
                    $vehicleId = DB::table('vehicles')->insertGetId([
                        'company_id' => $batch->company_id,
                        'plate_number' => $plateNumber,
                        'driver_name' => $batch->driver_name ?? null,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $vehicleId = $vehicle->id;
                }
            }

            if ($centerId || $vehicleId) {
                DB::table('milk_batches')
                    ->where('id', $batch->id)
                    ->update([
                        'collection_center_id' => $centerId ?? $batch->collection_center_id,
                        'vehicle_id' => $vehicleId ?? $batch->vehicle_id,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milk_batches', function (Blueprint $table) {
            $table->dropForeign(['collection_center_id']);
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn(['collection_center_id', 'vehicle_id']);
        });

        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('collection_centers');
    }
};
