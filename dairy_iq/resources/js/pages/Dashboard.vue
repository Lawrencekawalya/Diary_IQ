<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChartBar, ChartLine, FlaskConical, History, Layers, TrendingUp } from '@lucide/vue';
import type { ApexOptions } from 'apexcharts';
import { computed, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
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
    adminViewingCompany?: {
        id: number;
        name: string;
    };
}>();

const districtView = ref<'volume' | 'quality' | 'combined'>('volume');
const qualityLabels = ['High', 'Medium', 'Low'] as const;
const visibleTrendPointCount = 20;

const sortedTrend = computed(() => [...props.qualityTrend].sort((a, b) => {
    if (!a.created_at || !b.created_at) {
        return 0;
    }

    return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
}));

const summaryCount = (label: (typeof qualityLabels)[number]) => props.summary[label];

const qualityColor = (prediction: string) => {
    if (prediction === 'High') {
        return '#2ecc71';
    }

    if (prediction === 'Medium') {
        return '#f39c12';
    }

    return '#e74c3c';
};

const qualityColorLight = (prediction: string) => {
    if (prediction === 'High') {
        return 'rgba(46, 204, 113, 0.16)';
    }

    if (prediction === 'Medium') {
        return 'rgba(243, 156, 18, 0.16)';
    }

    return 'rgba(231, 76, 60, 0.16)';
};

const qualityScoreLabel = (score: number) => {
    if (score >= 1.5) {
        return 'High';
    }

    if (score >= 0.5) {
        return 'Medium';
    }

    return 'Low';
};

const performanceColor = (value: number, maxValue: number) => {
    const ratio = maxValue > 0 ? value / maxValue : 0;

    if (ratio > 0.7) {
        return '#27ae60';
    }

    if (ratio > 0.4) {
        return '#f39c12';
    }

    return '#e74c3c';
};

const sortedDistrictAnalytics = computed(() => [...props.districtAnalytics].sort((first, second) => {
    if (districtView.value === 'quality') {
        return second.average_quality_score - first.average_quality_score
            || second.total - first.total
            || first.district.localeCompare(second.district);
    }

    return second.liters - first.liters
        || second.total - first.total
        || first.district.localeCompare(second.district);
}));

const districtCategories = computed(() => sortedDistrictAnalytics.value.map((district) => district.district));
const maxDistrictLiters = computed(() => Math.max(...sortedDistrictAnalytics.value.map((district) => district.liters), 1));
const trendWindowStart = computed(() => Math.max(sortedTrend.value.length - visibleTrendPointCount, 0));
const trendWindowEnd = computed(() => Math.max(sortedTrend.value.length - 1, 0));

const qualityTrendSeries = computed(() => [
    {
        name: 'Milk Quality Score',
        data: sortedTrend.value.map((point, index) => ({
            x: index,
            y: point.score,
        })),
    },
]);

const qualityTrendOptions = computed<ApexOptions>(() => ({
    chart: {
        animations: { enabled: true, easing: 'easeinout', speed: 900 },
        fontFamily: 'inherit',
        toolbar: {
            autoSelected: 'pan',
            show: sortedTrend.value.length > visibleTrendPointCount,
            tools: {
                download: false,
                pan: true,
                reset: true,
                selection: false,
                zoom: true,
                zoomin: true,
                zoomout: true,
            },
        },
        zoom: {
            enabled: true,
            type: 'x',
        },
    },
    colors: ['#3498db'],
    dataLabels: { enabled: false },
    fill: {
        opacity: 0.18,
        type: 'solid',
    },
    grid: {
        borderColor: '#e5edf7',
        strokeDashArray: 3,
    },
    legend: {
        position: 'top',
    },
    markers: {
        colors: sortedTrend.value.map((point) => qualityColor(point.prediction)),
        discrete: sortedTrend.value.map((point, index) => ({
            seriesIndex: 0,
            dataPointIndex: index,
            fillColor: qualityColor(point.prediction),
            strokeColor: '#ffffff',
            size: 6,
        })),
        hover: { size: 8 },
        strokeColors: '#ffffff',
        strokeWidth: 2,
    },
    stroke: {
        curve: 'smooth',
        width: 3,
    },
    tooltip: {
        custom: ({ dataPointIndex }) => {
            const point = sortedTrend.value[dataPointIndex];

            if (!point) {
                return '';
            }

            return `
                <div style="padding:8px 10px">
                    <strong>${point.batch_number}</strong><br>
                    Quality: ${point.prediction}<br>
                    Collected: ${point.created_at ?? 'N/A'}
                </div>
            `;
        },
    },
    xaxis: {
        max: sortedTrend.value.length > visibleTrendPointCount ? trendWindowEnd.value : undefined,
        min: sortedTrend.value.length > visibleTrendPointCount ? trendWindowStart.value : undefined,
        tickAmount: Math.min(sortedTrend.value.length, 10),
        labels: {
            formatter: (value: string) => {
                const point = sortedTrend.value[Number(value)];

                return point?.label ?? point?.created_at ?? '';
            },
            rotate: -45,
            trim: true,
        },
        title: { text: 'Collection Date' },
        type: 'numeric',
    },
    yaxis: {
        max: 2.2,
        min: -0.2,
        tickAmount: 2,
        title: { text: 'Quality Rating' },
        labels: {
            formatter: (value: number) => {
                if (value === 2) {
                    return 'High';
                }

                if (value === 1) {
                    return 'Medium';
                }

                if (value === 0) {
                    return 'Low';
                }

                return '';
            },
        },
    },
}));

const districtChartTitle = computed(() => {
    if (districtView.value === 'quality') {
        return 'District Quality Performance';
    }

    if (districtView.value === 'combined') {
        return 'District Performance: Volume vs. Quality';
    }

    return 'District Volume Performance';
});

const districtSeries = computed(() => {
    if (districtView.value === 'quality') {
        return [
            {
                name: 'Average Quality Score',
                data: sortedDistrictAnalytics.value.map((district) => district.average_quality_score),
            },
        ];
    }

    if (districtView.value === 'combined') {
        return [
            {
                name: 'Total Liters Collected',
                type: 'column',
                data: sortedDistrictAnalytics.value.map((district) => district.liters),
            },
            {
                name: 'Avg Quality Score',
                type: 'line',
                data: sortedDistrictAnalytics.value.map((district) => district.average_quality_score),
            },
        ];
    }

    return [
        {
            name: 'Total Liters Collected',
            data: sortedDistrictAnalytics.value.map((district) => district.liters),
        },
    ];
});

const districtColors = computed(() => {
    if (districtView.value === 'quality') {
        return sortedDistrictAnalytics.value.map((district) => qualityColor(qualityScoreLabel(district.average_quality_score)));
    }

    if (districtView.value === 'combined') {
        return ['#3498db', '#1f3a93'];
    }

    return sortedDistrictAnalytics.value.map((district) => performanceColor(district.liters, maxDistrictLiters.value));
});

const districtChartOptions = computed<ApexOptions>(() => ({
    chart: {
        animations: { enabled: true, easing: 'easeOutQuart', speed: 900 },
        fontFamily: 'inherit',
        toolbar: { show: true },
    },
    colors: districtColors.value,
    dataLabels: {
        enabled: true,
        formatter: (value: number) => {
            if (districtView.value === 'quality') {
                return value.toFixed(2);
            }

            if (value >= 1000) {
                return `${(value / 1000).toFixed(1)}k`;
            }

            return value.toLocaleString();
        },
    },
    grid: {
        borderColor: '#e5edf7',
        strokeDashArray: 3,
    },
    labels: districtCategories.value,
    legend: {
        show: false,
    },
    markers: {
        size: districtView.value === 'combined' ? 6 : 0,
        strokeColors: '#ffffff',
        strokeWidth: 2,
    },
    plotOptions: {
        bar: {
            borderRadius: 5,
            columnWidth: '58%',
            distributed: districtView.value !== 'combined',
        },
    },
    stroke: {
        curve: 'smooth',
        width: districtView.value === 'combined' ? [0, 3] : 0,
    },
    title: {
        align: 'center',
        text: districtChartTitle.value,
    },
    tooltip: {
        shared: districtView.value === 'combined',
        y: {
            formatter: (value: number, options) => {
                const dataPointIndex = options?.dataPointIndex ?? 0;
                const seriesIndex = options?.seriesIndex ?? 0;
                const district = sortedDistrictAnalytics.value[dataPointIndex];

                if (districtView.value === 'quality' || seriesIndex === 1) {
                    return `${qualityScoreLabel(value)} (${value.toFixed(2)})`;
                }

                return `${value.toLocaleString()} liters; High: ${district?.High ?? 0}, Medium: ${district?.Medium ?? 0}, Low: ${district?.Low ?? 0}`;
            },
        },
    },
    xaxis: {
        categories: districtCategories.value,
        title: { text: 'Districts' },
    },
    yaxis: districtView.value === 'combined'
        ? [
            {
                min: 0,
                title: { text: 'Liters Collected' },
                labels: {
                    formatter: (value: number) => value >= 1000 ? `${(value / 1000).toFixed(1)}k` : value.toLocaleString(),
                },
            },
            {
                max: 2,
                min: 0,
                opposite: true,
                title: { text: 'Quality Score' },
                labels: {
                    formatter: (value: number) => value.toFixed(1),
                },
            },
        ]
        : {
            max: districtView.value === 'quality' ? 2 : undefined,
            min: 0,
            title: { text: districtView.value === 'quality' ? 'Quality Score' : 'Liters Collected' },
            labels: {
                formatter: (value: number) => {
                    if (districtView.value === 'quality') {
                        return value.toFixed(1);
                    }

                    return value >= 1000 ? `${(value / 1000).toFixed(1)}k` : value.toLocaleString();
                },
            },
        },
}));

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

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <section class="rounded-2xl border bg-gradient-to-br from-blue-950 to-blue-800 p-8 text-white shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-100">DairyIQ Analytics</p>
            <h1 class="mt-3 text-3xl font-bold">
                {{ adminViewingCompany ? `${adminViewingCompany.name} company dashboard` : 'Milk quality prediction dashboard' }}
            </h1>
            <p class="mt-3 max-w-3xl text-blue-100">
                {{
                    adminViewingCompany
                        ? 'Super-admin read-only view of this company workspace analytics.'
                        : 'Submit the approved 11 milk quality measurements, receive the Python Random Forest prediction, and keep every saved batch record scoped to your company.'
                }}
            </p>
            <div v-if="!adminViewingCompany" class="mt-6 flex flex-wrap gap-3">
                <Link href="/milk-batches/create" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-blue-950 transition-colors hover:bg-blue-50">
                    <FlaskConical class="size-4" />
                    New Prediction
                </Link>
                <Link href="/milk-batches" class="inline-flex items-center gap-2 rounded-lg border border-white/40 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-white/10">
                    <History class="size-4" />
                    View History
                </Link>
            </div>
            <div v-else class="mt-6">
                <Link href="/admin/companies" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-blue-950 transition-colors hover:bg-blue-50">
                    Back to Admin Companies
                </Link>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border bg-card p-5 shadow-sm transition-all hover:shadow-md">
                <p class="text-sm text-muted-foreground">Total Samples</p>
                <p class="mt-2 text-3xl font-bold">{{ summary.total }}</p>
            </div>
            <div class="rounded-xl border border-green-200 bg-green-50 p-5 shadow-sm transition-all hover:shadow-md dark:bg-green-950/30">
                <p class="text-sm text-green-700 dark:text-green-300">High Quality</p>
                <p class="mt-2 text-3xl font-bold text-green-800 dark:text-green-200">{{ summary.High }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm transition-all hover:shadow-md dark:bg-amber-950/30">
                <p class="text-sm text-amber-700 dark:text-amber-300">Medium Quality</p>
                <p class="mt-2 text-3xl font-bold text-amber-800 dark:text-amber-200">{{ summary.Medium }}</p>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-5 shadow-sm transition-all hover:shadow-md dark:bg-red-950/30">
                <p class="text-sm text-red-700 dark:text-red-300">Low Quality</p>
                <p class="mt-2 text-3xl font-bold text-red-800 dark:text-red-200">{{ summary.Low }}</p>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[2fr_1fr]">
            <div class="rounded-xl border bg-card p-5 shadow-sm transition-all hover:shadow-md">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">Milk Quality Over Time</h2>
                        <p class="text-sm text-muted-foreground">Latest company predictions plotted from Low to High.</p>
                    </div>
                    <TrendingUp class="size-5 text-blue-700" />
                </div>

                <div v-if="sortedTrend.length === 0" class="flex h-80 items-center justify-center rounded-lg border border-dashed text-sm text-muted-foreground">
                    No chart data yet. Create predictions to populate the trend.
                </div>

                <VueApexCharts
                    v-else
                    height="360"
                    type="line"
                    :options="qualityTrendOptions"
                    :series="qualityTrendSeries"
                />
            </div>

            <div class="rounded-xl border bg-card p-5 shadow-sm transition-all hover:shadow-md">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold">Quality Insights</h2>
                    <p class="text-sm text-muted-foreground">Summary of quality distribution.</p>
                </div>

                <ul class="space-y-3">
                    <li class="flex items-center justify-between rounded-lg bg-muted/60 p-3">
                        <span class="text-sm text-muted-foreground">Total Samples:</span>
                        <span class="text-lg font-bold">{{ summary.total }}</span>
                    </li>
                    <li
                        v-for="label in qualityLabels"
                        :key="label"
                        class="flex items-center justify-between rounded-lg p-3 transition-all hover:translate-x-1"
                        :style="{ borderLeft: `4px solid ${qualityColor(label)}`, background: qualityColorLight(label) }"
                    >
                        <span class="text-sm font-medium">{{ label }} Quality:</span>
                        <span class="text-lg font-bold">
                            {{ summaryCount(label) }}
                            ({{ summary.total > 0 ? ((summaryCount(label) / summary.total) * 100).toFixed(1) : '0.0' }}%)
                        </span>
                    </li>
                </ul>
            </div>
        </section>

        <section class="rounded-xl border bg-card p-5 shadow-sm transition-all hover:shadow-md">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">District Performance Analysis</h2>
                    <p class="text-sm text-muted-foreground">Volume, quality, and combined district performance views.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="view in ['volume', 'quality', 'combined'] as const"
                        :key="view"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold capitalize transition-all hover:shadow-md"
                        :class="districtView === view ? 'border-blue-800 bg-blue-800 text-white' : 'bg-background text-foreground hover:bg-muted/50'"
                        @click="districtView = view"
                    >
                        <ChartBar v-if="view === 'volume'" class="size-3.5" />
                        <ChartLine v-else-if="view === 'quality'" class="size-3.5" />
                        <Layers v-else class="size-3.5" />
                        {{ view }} View
                    </button>
                </div>
            </div>

            <div v-if="districtAnalytics.length === 0" class="rounded-lg border border-dashed p-12 text-center text-sm text-muted-foreground">
                No district data yet.
            </div>

            <div v-else class="space-y-5">
                <VueApexCharts
                    height="390"
                    :type="districtView === 'combined' ? 'line' : 'bar'"
                    :options="districtChartOptions"
                    :series="districtSeries"
                />

                <div class="flex flex-wrap gap-4 text-xs text-muted-foreground">
                    <div class="flex items-center gap-2">
                        <span class="size-3.5 rounded-sm bg-[#27ae60]" />
                        <span>High Performance (&gt;70%)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="size-3.5 rounded-sm bg-[#f39c12]" />
                        <span>Medium Performance (40-70%)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="size-3.5 rounded-sm bg-[#e74c3c]" />
                        <span>Low Performance (&lt;40%)</span>
                    </div>
                    <div v-if="districtView === 'combined'" class="flex items-center gap-2">
                        <span class="h-0.5 w-6 rounded-sm bg-[#1f3a93]" />
                        <span>Average Quality Score</span>
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div v-for="district in sortedDistrictAnalytics" :key="district.district" class="rounded-lg border p-3 transition-all hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold">{{ district.district }}</h3>
                                <p class="text-xs text-muted-foreground">
                                    {{ district.liters.toLocaleString() }} liters · Avg score {{ district.average_quality_score.toFixed(2) }}
                                </p>
                            </div>
                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">
                                {{ district.total }}
                            </span>
                        </div>
                        <div class="mt-2 flex gap-4 text-xs">
                            <span class="font-medium text-green-700">H: {{ district.High }}</span>
                            <span class="font-medium text-amber-700">M: {{ district.Medium }}</span>
                            <span class="font-medium text-red-700">L: {{ district.Low }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-xl border bg-card shadow-sm transition-all hover:shadow-md">
            <div class="flex items-center justify-between border-b p-5">
                <div>
                    <h2 class="text-lg font-semibold">Latest Milk Batch Records</h2>
                    <p class="text-sm text-muted-foreground">Most recent predictions saved for your company.</p>
                </div>
                <Link v-if="!adminViewingCompany" href="/milk-batches" class="text-sm font-medium text-blue-700 hover:underline">
                    View All
                </Link>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Batch</th>
                            <th class="px-5 py-3 font-semibold">Prediction</th>
                            <th class="px-5 py-3 font-semibold">Confidence</th>
                            <th class="px-5 py-3 font-semibold">Created</th>
                            <th v-if="!adminViewingCompany" class="px-5 py-3 text-right font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="latestBatches.length === 0">
                            <td :colspan="adminViewingCompany ? 4 : 5" class="px-5 py-10 text-center text-muted-foreground">
                                No records yet. Create your first milk quality prediction.
                            </td>
                        </tr>
                        <tr v-for="batch in latestBatches" :key="batch.id" class="border-t transition-colors hover:bg-muted/30">
                            <td class="px-5 py-3 font-medium">{{ batch.batch_number }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="predictionClass(batch.prediction)">
                                    {{ batch.prediction }}
                                </span>
                            </td>
                            <td class="px-5 py-3">{{ batch.confidence ?? 'N/A' }}</td>
                            <td class="px-5 py-3 text-muted-foreground">{{ batch.created_at ?? 'N/A' }}</td>
                            <td v-if="!adminViewingCompany" class="px-5 py-3 text-right">
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
