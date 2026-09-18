<?php

namespace App\Models;

use Database\Factories\MilkBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $user_id
 * @property string $batch_number
 * @property string $prediction
 * @property string|null $ml_prediction
 * @property Carbon|null $collected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'company_id',
    'user_id',
    'batch_number',
    'collection_center',
    'district',
    'tested_by',
    'driver_name',
    'vehicle_number',
    'collected_at',
    'liters_collected',
    'ph',
    'temperature',
    'taste',
    'odor',
    'fat_content',
    'titratable_acidity',
    'protein_content',
    'lactose_content',
    'tpc',
    'scc',
    'color',
    'sensory_inputs',
    'encoded_sensory_values',
    'prediction',
    'ml_prediction',
    'confidence',
    'probabilities',
    'standards_observations',
    'standards_quality_gate',
    'feature_status',
    'model_metadata',
    'record_schema_version',
    'source',
])]
class MilkBatch extends Model
{
    /** @use HasFactory<MilkBatchFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'collected_at' => 'datetime',
            'liters_collected' => 'decimal:2',
            'ph' => 'decimal:2',
            'temperature' => 'decimal:2',
            'taste' => 'integer',
            'odor' => 'integer',
            'fat_content' => 'decimal:2',
            'titratable_acidity' => 'decimal:3',
            'protein_content' => 'decimal:2',
            'lactose_content' => 'decimal:2',
            'tpc' => 'integer',
            'scc' => 'integer',
            'color' => 'integer',
            'sensory_inputs' => 'array',
            'encoded_sensory_values' => 'array',
            'confidence' => 'decimal:4',
            'probabilities' => 'array',
            'standards_observations' => 'array',
            'standards_quality_gate' => 'array',
            'feature_status' => 'array',
            'model_metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<MilkBatch>  $query
     * @return Builder<MilkBatch>
     */
    public function scopeForCompany(Builder $query, ?int $companyId): Builder
    {
        if ($companyId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('company_id', $companyId);
    }
}
