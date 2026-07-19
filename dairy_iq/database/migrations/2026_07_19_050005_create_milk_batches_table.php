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
        Schema::create('milk_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number');
            $table->string('collection_center')->nullable();
            $table->string('district')->nullable();
            $table->string('tested_by')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->decimal('liters_collected', 10, 2)->nullable();
            $table->decimal('ph', 4, 2);
            $table->decimal('temperature', 5, 2);
            $table->unsignedTinyInteger('taste');
            $table->unsignedTinyInteger('odor');
            $table->decimal('fat_content', 5, 2);
            $table->decimal('titratable_acidity', 5, 3);
            $table->decimal('protein_content', 5, 2);
            $table->decimal('lactose_content', 5, 2);
            $table->unsignedBigInteger('tpc');
            $table->unsignedBigInteger('scc');
            $table->unsignedTinyInteger('color');
            $table->json('sensory_inputs')->nullable();
            $table->json('encoded_sensory_values')->nullable();
            $table->string('prediction');
            $table->string('ml_prediction')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->json('probabilities')->nullable();
            $table->json('standards_observations')->nullable();
            $table->json('standards_quality_gate')->nullable();
            $table->json('feature_status')->nullable();
            $table->json('model_metadata')->nullable();
            $table->string('record_schema_version')->default('milk_batch_prediction_v1');
            $table->string('source')->default('laravel');
            $table->timestamps();

            $table->unique(['company_id', 'batch_number']);
            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'prediction']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milk_batches');
    }
};
