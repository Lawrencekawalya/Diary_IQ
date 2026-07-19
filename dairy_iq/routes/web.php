<?php

use App\Http\Controllers\AdminCompanyController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\CompanyUserController;
use App\Http\Controllers\ForcedPasswordChangeController;
use App\Http\Controllers\MilkBatchPredictionController;
use App\Http\Controllers\ReportController;
use App\Models\Company;
use App\Models\MilkBatch;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('force-password-change', [ForcedPasswordChangeController::class, 'edit'])
        ->name('password.force.edit');
    Route::put('force-password-change', [ForcedPasswordChangeController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.force.update');

    Route::get('dashboard', function () {
        $user = request()->user();
        $qualityScores = [
            'Low' => 0,
            'Medium' => 1,
            'High' => 2,
        ];

        if ($user->isSuperAdmin()) {
            $platformBatches = MilkBatch::query()
                ->with('company')
                ->latest()
                ->limit(100)
                ->get();
            $counts = MilkBatch::query()
                ->selectRaw('prediction, count(*) as total')
                ->groupBy('prediction')
                ->pluck('total', 'prediction');
            $roleCounts = User::query()
                ->selectRaw('role, count(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role');

            return Inertia::render('admin/Dashboard', [
                'summary' => [
                    'companies' => Company::count(),
                    'users' => User::count(),
                    'company_admins' => $roleCounts->get('company_admin', 0),
                    'testers' => $roleCounts->get('tester', 0),
                    'super_admins' => $roleCounts->get('super_admin', 0),
                    'batches' => MilkBatch::count(),
                    'liters' => round((float) MilkBatch::sum('liters_collected'), 2),
                    'High' => $counts->get('High', 0),
                    'Medium' => $counts->get('Medium', 0),
                    'Low' => $counts->get('Low', 0),
                ],
                'companyPerformance' => Company::query()
                    ->withCount(['users', 'milkBatches'])
                    ->with(['milkBatches' => fn ($query) => $query->latest()->limit(100)])
                    ->latest()
                    ->get()
                    ->map(function (Company $company) use ($qualityScores) {
                        $batches = $company->milkBatches;
                        $batchCount = $batches->count();

                        return [
                            'id' => $company->id,
                            'name' => $company->name,
                            'users_count' => $company->users_count,
                            'milk_batches_count' => $company->milk_batches_count,
                            'liters' => round((float) $batches->sum('liters_collected'), 2),
                            'average_quality_score' => $batchCount > 0
                                ? round((float) $batches->avg(fn (MilkBatch $batch) => $qualityScores[$batch->prediction] ?? 0), 2)
                                : 0,
                            'High' => $batches->where('prediction', 'High')->count(),
                            'Medium' => $batches->where('prediction', 'Medium')->count(),
                            'Low' => $batches->where('prediction', 'Low')->count(),
                        ];
                    })
                    ->sortByDesc('milk_batches_count')
                    ->take(8)
                    ->values(),
                'recentBatches' => $platformBatches
                    ->take(8)
                    ->values()
                    ->map(fn (MilkBatch $batch) => [
                        'id' => $batch->id,
                        'batch_number' => $batch->batch_number,
                        'company' => $batch->company?->name ?? 'Unknown Company',
                        'district' => $batch->district,
                        'prediction' => $batch->prediction,
                        'confidence' => $batch->confidence,
                        'created_at' => $batch->created_at?->toDayDateTimeString(),
                    ]),
            ]);
        }

        $counts = MilkBatch::forCompany($user->company_id)
            ->selectRaw('prediction, count(*) as total')
            ->groupBy('prediction')
            ->pluck('total', 'prediction');
        $dashboardBatches = MilkBatch::forCompany($user->company_id)
            ->latest()
            ->limit(100)
            ->get();

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
    Route::get('approved-features', function () {
        $standardsPath = base_path('../config/standards.json');
        $standards = is_file($standardsPath)
            ? json_decode((string) file_get_contents($standardsPath), true, flags: JSON_THROW_ON_ERROR)
            : [];

        return Inertia::render('reference/ApprovedFeatures', [
            'features' => $standards,
        ]);
    })->name('approved-features.index');
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
    Route::get('company/users', [CompanyUserController::class, 'index'])
        ->name('company.users.index');
    Route::post('company/users', [CompanyUserController::class, 'store'])
        ->name('company.users.store');
    Route::get('admin/companies', [AdminCompanyController::class, 'index'])
        ->name('admin.companies.index');
    Route::get('admin/companies/{company}/dashboard', function (Company $company) {
        abort_unless(request()->user()?->isSuperAdmin(), 403);

        $dashboardBatches = MilkBatch::forCompany($company->id)
            ->latest()
            ->limit(100)
            ->get();
        $counts = MilkBatch::forCompany($company->id)
            ->selectRaw('prediction, count(*) as total')
            ->groupBy('prediction')
            ->pluck('total', 'prediction');
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
            'adminViewingCompany' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
        ]);
    })->name('admin.companies.dashboard');
    Route::get('admin/documentation', function () {
        abort_unless(request()->user()?->isSuperAdmin(), 403);

        return Inertia::render('admin/Documentation');
    })->name('admin.documentation.index');
    Route::post('admin/companies', [AdminCompanyController::class, 'store'])
        ->name('admin.companies.store');
    Route::put('admin/companies/{company}', [AdminCompanyController::class, 'update'])
        ->name('admin.companies.update');
    Route::put('admin/companies/{company}/archive', [AdminCompanyController::class, 'archive'])
        ->name('admin.companies.archive');
    Route::put('admin/companies/{company}/restore', [AdminCompanyController::class, 'restore'])
        ->name('admin.companies.restore');
    Route::post('admin/users', [AdminCompanyController::class, 'storePlatformUser'])
        ->name('admin.users.store');
    Route::put('admin/users/{user}', [AdminCompanyController::class, 'updateUser'])
        ->name('admin.users.update');
    Route::put('admin/users/{user}/password', [AdminCompanyController::class, 'resetUserPassword'])
        ->name('admin.users.password');
    Route::put('admin/users/{user}/deactivate', [AdminCompanyController::class, 'deactivateUser'])
        ->name('admin.users.deactivate');
    Route::put('admin/users/{user}/activate', [AdminCompanyController::class, 'activateUser'])
        ->name('admin.users.activate');
    Route::delete('admin/users/{user}', [AdminCompanyController::class, 'destroyUser'])
        ->name('admin.users.destroy');
    Route::post('admin/companies/{company}/users', [AdminCompanyController::class, 'storeUser'])
        ->name('admin.companies.users.store');
});

require __DIR__.'/settings.php';
