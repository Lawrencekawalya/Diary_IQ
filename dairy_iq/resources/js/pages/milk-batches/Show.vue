<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { ApexOptions } from 'apexcharts';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

type Batch = {
    id: number;
    batch_number: string;
    prediction: string;
    ml_prediction: string | null;
    confidence: string | null;
    collection_center: string | null;
    district: string | null;
    tested_by: string | null;
    collected_at: string | null;
    liters_collected: string | null;
    measurements: Record<string, string | number | null>;
    sensory_inputs: Record<string, string> | null;
    probabilities: Record<string, number> | null;
    standards_observations: string[] | null;
    standards_quality_gate: Record<string, unknown> | null;
    feature_status: string[] | null;
    model_metadata: Record<string, unknown> | null;
};

const props = defineProps<{ batch: Batch }>();

const featureSnapshotLabels = [
    'pH',
    'Temp',
    'Taste',
    'Odor',
    'Fat',
    'Acidity',
    'Protein',
    'Lactose',
    'TPC',
    'SCC',
    'Color',
];

const featureSnapshotColors = computed(() => {
    const colors = props.batch.feature_status ?? [];

    return featureSnapshotLabels.map((_, index) => colors[index] ?? '#bdc3c7');
});

const featureSnapshotSeries = computed(() => [
    {
        name: 'Feature Status',
        data: featureSnapshotLabels.map(() => 1),
    },
]);

const featureSnapshotOptions = computed<ApexOptions>(() => ({
    chart: {
        animations: { enabled: true, easing: 'easeinout', speed: 700 },
        fontFamily: 'inherit',
        toolbar: { show: false },
    },
    colors: featureSnapshotColors.value,
    dataLabels: { enabled: false },
    grid: {
        borderColor: '#e5edf7',
        strokeDashArray: 3,
    },
    legend: { show: false },
    plotOptions: {
        bar: {
            borderRadius: 3,
            columnWidth: '72%',
            distributed: true,
        },
    },
    states: {
        hover: {
            filter: { type: 'none' },
        },
    },
    title: {
        align: 'center',
        text: 'Parameters (Green = Normal, Red = Out of Range)',
    },
    tooltip: {
        y: {
            formatter: (_value: number, options) => {
                const color = featureSnapshotColors.value[options?.dataPointIndex ?? 0];

                return color === '#2ecc71' ? 'Normal' : 'Out of Range';
            },
        },
    },
    xaxis: {
        categories: featureSnapshotLabels,
        labels: {
            rotate: 0,
            trim: false,
        },
    },
    yaxis: {
        max: 1,
        min: 0,
        tickAmount: 1,
        labels: {
            formatter: (value: number) => value === 1 ? 'Present' : '',
        },
    },
}));

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'History', href: '/milk-batches' },
            { title: 'Result', href: '#' },
        ],
    },
});

const predictionClass = (prediction: string) => ({
    'text-green-800': prediction === 'High',
    'text-amber-700': prediction === 'Medium',
    'text-red-700': prediction === 'Low',
});
</script>

<template>
    <Head :title="`Prediction ${batch.batch_number}`" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <section class="rounded-xl border bg-blue-50 p-6 shadow-sm dark:bg-blue-950/20">
            <p class="text-sm font-semibold text-blue-800 dark:text-blue-200">Prediction Result According to US EAS 67:2023</p>
            <h1 class="mt-3 text-4xl font-bold" :class="predictionClass(batch.prediction)">
                {{ batch.prediction }}
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Raw Random Forest vote: <strong>{{ batch.ml_prediction ?? 'N/A' }}</strong>
                <span v-if="batch.confidence"> | Confidence: <strong>{{ batch.confidence }}</strong></span>
            </p>
        </section>

        <section class="rounded-xl border bg-card p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-blue-900 dark:text-blue-200">Feature Standards Snapshot</h2>
            <div class="mt-4">
                <VueApexCharts
                    height="300"
                    type="bar"
                    :options="featureSnapshotOptions"
                    :series="featureSnapshotSeries"
                />
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border bg-card p-6 shadow-sm">
                <h2 class="text-lg font-semibold">Batch Details</h2>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">Batch Number</dt><dd class="font-medium">{{ batch.batch_number }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">Collection Center</dt><dd>{{ batch.collection_center ?? 'N/A' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">District</dt><dd>{{ batch.district ?? 'N/A' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">Tested By</dt><dd>{{ batch.tested_by ?? 'N/A' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">Collected At</dt><dd>{{ batch.collected_at ?? 'N/A' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">Liters</dt><dd>{{ batch.liters_collected ?? 'N/A' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-xl border bg-card p-6 shadow-sm">
                <h2 class="text-lg font-semibold">Class Probabilities</h2>
                <div class="mt-4 grid gap-3">
                    <div v-for="(value, label) in batch.probabilities ?? {}" :key="label">
                        <div class="mb-1 flex justify-between text-sm">
                            <span>{{ label }}</span>
                            <span>{{ (Number(value) * 100).toFixed(2) }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-muted">
                            <div class="h-2 rounded-full bg-blue-700" :style="{ width: `${Number(value) * 100}%` }" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border bg-card p-6 shadow-sm">
                <h2 class="text-lg font-semibold">Measured Inputs</h2>
                <div class="mt-4 grid gap-2 text-sm">
                    <div v-for="(value, feature) in batch.measurements" :key="feature" class="flex justify-between border-b py-2">
                        <span class="text-muted-foreground">{{ feature }}</span>
                        <span class="font-medium">{{ value }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card p-6 shadow-sm">
                <h2 class="text-lg font-semibold">Standards Observations</h2>
                <ul class="mt-4 list-disc space-y-2 pl-5 text-sm">
                    <li v-for="observation in batch.standards_observations ?? []" :key="observation">
                        {{ observation }}
                    </li>
                </ul>
                <div v-if="batch.standards_quality_gate" class="mt-5 rounded-lg bg-muted p-4 text-sm">
                    <p><strong>Gate applied:</strong> {{ batch.standards_quality_gate.applied ? 'Yes' : 'No' }}</p>
                    <p><strong>Reason:</strong> {{ batch.standards_quality_gate.reason }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-xl border bg-card p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Model Metadata</h2>
            <pre class="mt-4 overflow-x-auto rounded-lg bg-muted p-4 text-xs">{{ JSON.stringify(batch.model_metadata, null, 2) }}</pre>
        </section>

        <div class="flex justify-between">
            <Link href="/milk-batches" class="rounded-lg border px-4 py-2 text-sm font-semibold">Back to History</Link>
            <div class="flex gap-3">
                <a :href="`/reports/${batch.id}/preview`" target="_blank" rel="noopener" class="rounded-lg border px-4 py-2 text-sm font-semibold">Preview Report</a>
                <a :href="`/reports/${batch.id}`" class="rounded-lg border px-4 py-2 text-sm font-semibold">Download PDF</a>
                <Link href="/milk-batches/create" class="rounded-lg bg-blue-800 px-4 py-2 text-sm font-semibold text-white">New Prediction</Link>
            </div>
        </div>
    </div>
</template>
