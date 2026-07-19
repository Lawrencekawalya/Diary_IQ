<?php

use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\MilkBatchPredictionController;
use App\Http\Controllers\ReportController;
use App\Models\MilkBatch;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = request()->user();
        $counts = MilkBatch::forCompany($user->company_id)
            ->selectRaw('prediction, count(*) as total')
            ->groupBy('prediction')
            ->pluck('total', 'prediction');
        $dashboardBatches = MilkBatch::forCompany($user->company_id)
            ->latest()
            ->limit(100)
            ->get();
        $qualityScores = [
            'Low' => 0,
            'Medium' => 1,
            'High' => 2,
        ];

        return Inertia::render('Dashboard', [
            'summary' => [
                'total' => $counts->sum(),
                'High' => $counts->get('High', 0),
                'Medium' => $counts->get('Medium', 0),
                'Low' => $counts->get('Low', 0),
            ],
            'latestBatches' => $dashboardBatches
                ->take(5)
                ->values()
                ->map(fn (MilkBatch $batch) => [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'prediction' => $batch->prediction,
                    'confidence' => $batch->confidence,
                    'created_at' => $batch->created_at?->toDayDateTimeString(),
                ]),
            'qualityTrend' => $dashboardBatches
                ->sortBy('created_at')
                ->values()
                ->map(fn (MilkBatch $batch) => [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'prediction' => $batch->prediction,
                    'score' => $qualityScores[$batch->prediction] ?? 0,
                    'label' => $batch->created_at?->format('M j, H:i'),
                    'created_at' => $batch->created_at?->toDayDateTimeString(),
                ]),
            'districtAnalytics' => $dashboardBatches
                ->groupBy(fn (MilkBatch $batch) => $batch->district ?: 'Unknown')
                ->map(function ($batches, string $district) use ($qualityScores) {
                    $total = $batches->count();

                    return [
                        'district' => $district,
                        'total' => $total,
                        'liters' => round((float) $batches->sum('liters_collected'), 2),
                        'average_quality_score' => $total > 0
                            ? round((float) $batches->avg(fn (MilkBatch $batch) => $qualityScores[$batch->prediction] ?? 0), 2)
                            : 0,
                        'High' => $batches->where('prediction', 'High')->count(),
                        'Medium' => $batches->where('prediction', 'Medium')->count(),
                        'Low' => $batches->where('prediction', 'Low')->count(),
                    ];
                })
                ->sortByDesc('total')
                ->take(8)
                ->values(),
        ]);
    })->name('dashboard');
    Route::get('milk-batches', [MilkBatchPredictionController::class, 'index'])
        ->name('milk-batches.index');
    Route::get('milk-batches/create', [MilkBatchPredictionController::class, 'create'])
        ->name('milk-batches.create');
    Route::post('milk-batches/predictions', [MilkBatchPredictionController::class, 'store'])
        ->name('milk-batches.predictions.store');
    Route::get('milk-batches/{milkBatch}', [MilkBatchPredictionController::class, 'show'])
        ->name('milk-batches.show');
    Route::get('reports', [ReportController::class, 'index'])
        ->name('reports.index');
    Route::get('reports/{milkBatch}', [ReportController::class, 'show'])
        ->name('reports.show');
    Route::get('reports/{milkBatch}/preview', [ReportController::class, 'preview'])
        ->name('reports.preview');
    Route::get('company/settings', [CompanySettingsController::class, 'edit'])
        ->name('company.settings.edit');
    Route::put('company/settings', [CompanySettingsController::class, 'update'])
        ->name('company.settings.update');
});

require __DIR__.'/settings.php';
