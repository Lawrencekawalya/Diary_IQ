<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FlaskConical, History, TrendingUp } from '@lucide/vue';
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

defineProps<{
    summary: Summary;
    latestBatches: LatestBatch[];
}>();

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
