<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FlaskConical, History, TrendingUp } from '@lucide/vue';
import { computed } from 'vue';
import { dashboard } from '@/routes';

type Summary = {
    total: number;
    High: number;
    Medium: number;
    Low: number;
};

type LatestBatch = {
    id: number;
    batch_number: string;
    prediction: string;
    confidence: string | null;
    created_at: string | null;
};

type QualityTrendPoint = {
    id: number;
    batch_number: string;
    prediction: string;
    score: number;
    label: string | null;
    created_at: string | null;
};

type DistrictAnalytic = {
    district: string;
    total: number;
    liters: number;
    average_quality_score: number;
    High: number;
    Medium: number;
    Low: number;
};

const props = defineProps<{
    summary: Summary;
    latestBatches: LatestBatch[];
    qualityTrend: QualityTrendPoint[];
    districtAnalytics: DistrictAnalytic[];
}>();

const chartWidth = 760;
const chartHeight = 220;
const chartPadding = 36;

const trendPoints = computed(() => {
    const points = props.qualityTrend;

    if (points.length === 0) {
        return [];
    }

    return points.map((point, index) => {
        const x = points.length === 1
            ? chartWidth / 2
            : chartPadding + (index * (chartWidth - chartPadding * 2)) / (points.length - 1);
        const y = chartPadding + ((3 - point.score) * (chartHeight - chartPadding * 2)) / 2;

        return {
            ...point,
            x,
            y,
        };
    });
});

const trendPolyline = computed(() => trendPoints.value.map((point) => `${point.x},${point.y}`).join(' '));

const maxDistrictTotal = computed(() => Math.max(...props.districtAnalytics.map((district) => district.total), 1));
const qualityLabels = ['High', 'Medium', 'Low'] as const;

const summaryCount = (label: (typeof qualityLabels)[number]) => props.summary[label];

const qualityColor = (prediction: string) => {
    if (prediction === 'High') {
        return '#16a34a';
    }

    if (prediction === 'Medium') {
        return '#f59e0b';
    }

    return '#ef4444';
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const predictionClass = (prediction: string) => ({
    'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200': prediction === 'High',
    'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200': prediction === 'Medium',
    'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200': prediction === 'Low',
});
</script>

<template>
    <Head title="DairyIQ Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <section class="rounded-2xl border bg-gradient-to-br from-blue-950 to-blue-800 p-8 text-white shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-100">DairyIQ Analytics</p>
            <h1 class="mt-3 text-3xl font-bold">Milk quality prediction dashboard</h1>
            <p class="mt-3 max-w-3xl text-blue-100">
                Submit the approved 11 milk quality measurements, receive the Python Random Forest prediction,
                and keep every saved batch record scoped to your company.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                <Link href="/milk-batches/create" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-blue-950">
                    <FlaskConical class="size-4" />
                    New Prediction
                </Link>
                <Link href="/milk-batches" class="inline-flex items-center gap-2 rounded-lg border border-white/40 px-4 py-2 text-sm font-semibold text-white">
                    <History class="size-4" />
                    View History
                </Link>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <p class="text-sm text-muted-foreground">Total Samples</p>
                <p class="mt-2 text-3xl font-bold">{{ summary.total }}</p>
            </div>
            <div class="rounded-xl border border-green-200 bg-green-50 p-5 shadow-sm dark:bg-green-950/30">
                <p class="text-sm text-green-700 dark:text-green-300">High Quality</p>
                <p class="mt-2 text-3xl font-bold text-green-800 dark:text-green-200">{{ summary.High }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm dark:bg-amber-950/30">
                <p class="text-sm text-amber-700 dark:text-amber-300">Medium Quality</p>
                <p class="mt-2 text-3xl font-bold text-amber-800 dark:text-amber-200">{{ summary.Medium }}</p>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-5 shadow-sm dark:bg-red-950/30">
                <p class="text-sm text-red-700 dark:text-red-300">Low Quality</p>
                <p class="mt-2 text-3xl font-bold text-red-800 dark:text-red-200">{{ summary.Low }}</p>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[2fr_1fr]">
            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">Milk Quality Over Time</h2>
                        <p class="text-sm text-muted-foreground">Latest company predictions plotted from Low to High.</p>
                    </div>
                    <TrendingUp class="size-5 text-blue-700" />
                </div>

                <div v-if="trendPoints.length === 0" class="flex h-64 items-center justify-center rounded-lg border border-dashed text-sm text-muted-foreground">
                    No chart data yet. Create predictions to populate the trend.
                </div>

                <div v-else class="overflow-x-auto">
                    <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="min-w-[720px] rounded-lg bg-blue-50/40">
                        <line :x1="chartPadding" :x2="chartWidth - chartPadding" :y1="chartPadding" :y2="chartPadding" stroke="#dbeafe" />
                        <line :x1="chartPadding" :x2="chartWidth - chartPadding" :y1="chartHeight / 2" :y2="chartHeight / 2" stroke="#dbeafe" />
                        <line :x1="chartPadding" :x2="chartWidth - chartPadding" :y1="chartHeight - chartPadding" :y2="chartHeight - chartPadding" stroke="#dbeafe" />
                        <text x="8" :y="chartPadding + 4" class="fill-muted-foreground text-[11px]">High</text>
                        <text x="8" :y="chartHeight / 2 + 4" class="fill-muted-foreground text-[11px]">Medium</text>
                        <text x="8" :y="chartHeight - chartPadding + 4" class="fill-muted-foreground text-[11px]">Low</text>
                        <polyline v-if="trendPoints.length > 1" :points="trendPolyline" fill="none" stroke="#2563eb" stroke-width="3" />
                        <g v-for="point in trendPoints" :key="point.id">
                            <circle :cx="point.x" :cy="point.y" r="5" :fill="qualityColor(point.prediction)" />
                            <title>{{ point.batch_number }} - {{ point.prediction }} - {{ point.created_at ?? 'N/A' }}</title>
                        </g>
                    </svg>
                </div>
            </div>

            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <h2 class="text-lg font-semibold">Quality Insights</h2>
                <p class="text-sm text-muted-foreground">Company-scoped prediction distribution.</p>
                <div class="mt-5 space-y-4">
                    <div v-for="label in qualityLabels" :key="label">
                        <div class="mb-1 flex justify-between text-sm">
                            <span>{{ label }} Quality</span>
                            <span class="font-semibold">{{ summaryCount(label) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-muted">
                            <div
                                class="h-2 rounded-full"
                                :style="{
                                    width: `${summary.total > 0 ? (summaryCount(label) / summary.total) * 100 : 0}%`,
                                    backgroundColor: qualityColor(label),
                                }"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-xl border bg-card p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-semibold">District Analytics</h2>
                <p class="text-sm text-muted-foreground">Top districts by saved prediction volume in the latest company records.</p>
            </div>
            <div v-if="districtAnalytics.length === 0" class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                No district data yet.
            </div>
            <div v-else class="grid gap-3 md:grid-cols-2">
                <div v-for="district in districtAnalytics" :key="district.district" class="rounded-lg border p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold">{{ district.district }}</h3>
                            <p class="text-xs text-muted-foreground">
                                {{ district.total }} samples · {{ district.liters.toLocaleString() }} liters · Avg score {{ district.average_quality_score }}
                            </p>
                        </div>
                        <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800">{{ district.total }}</span>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-muted">
                        <div class="h-2 rounded-full bg-blue-700" :style="{ width: `${(district.total / maxDistrictTotal) * 100}%` }" />
                    </div>
                    <div class="mt-3 flex gap-3 text-xs text-muted-foreground">
                        <span class="text-green-700">High: {{ district.High }}</span>
                        <span class="text-amber-700">Medium: {{ district.Medium }}</span>
                        <span class="text-red-700">Low: {{ district.Low }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-xl border bg-card shadow-sm">
            <div class="flex items-center justify-between border-b p-5">
                <div>
                    <h2 class="text-lg font-semibold">Latest Milk Batch Records</h2>
                    <p class="text-sm text-muted-foreground">Most recent predictions saved for your company.</p>
                </div>
                <TrendingUp class="size-5 text-blue-700" />
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">Batch</th>
                            <th class="px-5 py-3">Prediction</th>
                            <th class="px-5 py-3">Confidence</th>
                            <th class="px-5 py-3">Created</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="latestBatches.length === 0">
                            <td colspan="5" class="px-5 py-8 text-center text-muted-foreground">
                                No records yet. Create your first milk quality prediction.
                            </td>
                        </tr>
                        <tr v-for="batch in latestBatches" :key="batch.id" class="border-t">
                            <td class="px-5 py-3 font-medium">{{ batch.batch_number }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="predictionClass(batch.prediction)">
                                    {{ batch.prediction }}
                                </span>
                            </td>
                            <td class="px-5 py-3">{{ batch.confidence ?? 'N/A' }}</td>
                            <td class="px-5 py-3">{{ batch.created_at ?? 'N/A' }}</td>
                            <td class="px-5 py-3 text-right">
                                <Link :href="`/milk-batches/${batch.id}`" class="font-semibold text-blue-700 hover:underline">
                                    View Details
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
