<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMilkBatchPredictionRequest;
use App\Models\MilkBatch;
use App\Services\MilkQualityPredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class MilkBatchPredictionController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();
        $filters = request()->only(['prediction', 'search']);
        $query = MilkBatch::forCompany($user->company_id)
            ->when(in_array($filters['prediction'] ?? null, ['High', 'Medium', 'Low'], true), function ($query) use ($filters) {
                $query->where('prediction', $filters['prediction']);
            })
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('batch_number', 'like', "%{$search}%")
                        ->orWhere('collection_center', 'like', "%{$search}%")
                        ->orWhere('tested_by', 'like', "%{$search}%");
                });
            });

        $batches = (clone $query)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = (clone $query)
            ->selectRaw('prediction, count(*) as total')
            ->groupBy('prediction')
            ->pluck('total', 'prediction');

        return Inertia::render('milk-batches/History', [
            'batches' => $batches->through(fn (MilkBatch $batch) => $this->batchSummary($batch)),
            'insights' => [
                'total' => $counts->sum(),
                'High' => $counts->get('High', 0),
                'Medium' => $counts->get('Medium', 0),
                'Low' => $counts->get('Low', 0),
            ],
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('milk-batches/Create', [
            'batchNumber' => $this->generateBatchNumber(),
            'districts' => config('dairyiq.uganda_districts', []),
        ]);
    }

    public function show(MilkBatch $milkBatch): Response
    {
        $this->authorizeCompanyAccess($milkBatch);

        return Inertia::render('milk-batches/Show', [
            'batch' => $this->batchDetails($milkBatch),
        ]);
    }

    public function store(
        StoreMilkBatchPredictionRequest $request,
        MilkQualityPredictionService $predictionService
    ): JsonResponse|RedirectResponse {
        try {
            $prediction = $predictionService->predict($request->predictionPayload());
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Prediction could not be completed.',
                    'error' => $exception->getMessage(),
                ], 502);
            }

            return back()->withErrors([
                'prediction' => $exception->getMessage(),
            ])->withInput();
        }

        $user = $request->user();
        $data = $request->validated();

        $batch = MilkBatch::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'batch_number' => $data['batch_number'] ?? $this->generateBatchNumber(),
            'collection_center' => $data['collection_center'] ?? null,
            'district' => $data['district'] ?? null,
            'tested_by' => $data['tested_by'] ?? $user->name,
            'driver_name' => $data['driver_name'],
            'vehicle_number' => $data['vehicle_number'],
            'collected_at' => $data['collected_at'] ?? now(),
            'liters_collected' => $data['liters_collected'] ?? null,
            'ph' => $data['pH'],
            'temperature' => $data['Temperature'],
            'taste' => $prediction['encoded_sensory_values']['Taste'],
            'odor' => $prediction['encoded_sensory_values']['Odor'],
            'fat_content' => $data['Fat_Content'],
            'titratable_acidity' => $data['Titratable_Acidity'],
            'protein_content' => $data['Protein_Content'],
            'lactose_content' => $data['Lactose_Content'],
            'tpc' => $data['TPC'],
            'scc' => $data['SCC'],
            'color' => $prediction['encoded_sensory_values']['Color'],
            'sensory_inputs' => $prediction['sensory_inputs'],
            'encoded_sensory_values' => $prediction['encoded_sensory_values'],
            'prediction' => $prediction['prediction'],
            'ml_prediction' => $prediction['ml_prediction'],
            'confidence' => $prediction['confidence'],
            'probabilities' => $prediction['probabilities'],
            'standards_observations' => $prediction['standards_observations'],
            'standards_quality_gate' => $prediction['standards_quality_gate'],
            'feature_status' => $prediction['feature_status'] ?? $prediction['colors'] ?? null,
            'model_metadata' => $prediction['model_metadata'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'prediction' => $batch->prediction,
                'ml_prediction' => $batch->ml_prediction,
                'confidence' => $batch->confidence,
            ], 201);
        }

        return redirect()->route('milk-batches.show', $batch);
    }

    private function generateBatchNumber(): string
    {
        return 'BATCH-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(6));
    }

    /**
     * @return array<string, mixed>
     */
    private function batchSummary(MilkBatch $batch): array
    {
        return [
            'id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'prediction' => $batch->prediction,
            'ml_prediction' => $batch->ml_prediction,
            'confidence' => $batch->confidence,
            'collection_center' => $batch->collection_center,
            'tested_by' => $batch->tested_by,
            'driver_name' => $batch->driver_name,
            'vehicle_number' => $batch->vehicle_number,
            'created_at' => $batch->created_at?->toDayDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function batchDetails(MilkBatch $batch): array
    {
        return [
            ...$this->batchSummary($batch),
            'district' => $batch->district,
            'collected_at' => $batch->collected_at?->toDayDateTimeString(),
            'liters_collected' => $batch->liters_collected,
            'measurements' => [
                'pH' => $batch->ph,
                'Temperature' => $batch->temperature,
                'Taste' => $batch->taste,
                'Odor' => $batch->odor,
                'Fat_Content' => $batch->fat_content,
                'Titratable_Acidity' => $batch->titratable_acidity,
                'Protein_Content' => $batch->protein_content,
                'Lactose_Content' => $batch->lactose_content,
                'TPC' => $batch->tpc,
                'SCC' => $batch->scc,
                'Color' => $batch->color,
            ],
            'sensory_inputs' => $batch->sensory_inputs,
            'encoded_sensory_values' => $batch->encoded_sensory_values,
            'probabilities' => $batch->probabilities,
            'standards_observations' => $batch->standards_observations,
            'standards_quality_gate' => $batch->standards_quality_gate,
            'feature_status' => $batch->feature_status,
            'model_metadata' => $batch->model_metadata,
        ];
    }

    private function authorizeCompanyAccess(MilkBatch $batch): void
    {
        abort_unless($batch->company_id === request()->user()->company_id, 404);
    }
}
