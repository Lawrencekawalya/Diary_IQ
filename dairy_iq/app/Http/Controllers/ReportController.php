<?php

namespace App\Http\Controllers;

use App\Models\MilkBatch;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();

        return Inertia::render('reports/Index', [
            'batches' => MilkBatch::forCompany($user->company_id)
                ->latest()
                ->limit(25)
                ->get()
                ->map(fn (MilkBatch $batch) => [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'prediction' => $batch->prediction,
                    'tested_by' => $batch->tested_by,
                    'created_at' => $batch->created_at?->toDayDateTimeString(),
                ]),
        ]);
    }
}
