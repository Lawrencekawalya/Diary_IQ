<?php

namespace App\Http\Controllers;

use App\Models\MilkBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response as HttpResponse;
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
                    'ml_prediction' => $batch->ml_prediction,
                    'confidence' => $batch->confidence,
                    'tested_by' => $batch->tested_by,
                    'created_at' => $batch->created_at?->toDayDateTimeString(),
                ]),
        ]);
    }

    public function show(MilkBatch $milkBatch): HttpResponse
    {
        $this->authorizeCompanyAccess($milkBatch);

        $pdf = Pdf::loadView('reports.milk-batch-pdf', $this->reportData($milkBatch))
            ->setPaper('a4');

        return $pdf->download("DairyIQ_Report_{$milkBatch->batch_number}.pdf");
    }

    public function preview(MilkBatch $milkBatch): View
    {
        $this->authorizeCompanyAccess($milkBatch);

        return view('reports.milk-batch-preview', $this->reportData($milkBatch));
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(MilkBatch $milkBatch): array
    {
        return [
            'batch' => $milkBatch,
            'company' => request()->user()->company,
            'measurements' => $this->measurements($milkBatch),
            'standardsRows' => $this->standardsRows($milkBatch),
        ];
    }

    /**
     * @return array<string, string|int|null>
     */
    private function measurements(MilkBatch $batch): array
    {
        return [
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
        ];
    }

    /**
     * @return array<int, array<string, string|int|null>>
     */
    private function standardsRows(MilkBatch $batch): array
    {
        $measurements = $this->measurements($batch);
        $features = array_keys($measurements);
        $status = $batch->feature_status ?? [];

        return collect($measurements)
            ->map(fn ($value, string $feature) => [
                'feature' => $feature,
                'value' => $value,
                'status' => ($status[array_search($feature, $features, true)] ?? null) === '#2ecc71'
                    ? 'Normal'
                    : 'Out of Range',
            ])
            ->values()
            ->all();
    }

    private function authorizeCompanyAccess(MilkBatch $batch): void
    {
        abort_unless($batch->company_id === request()->user()->company_id, 404);
    }
}
