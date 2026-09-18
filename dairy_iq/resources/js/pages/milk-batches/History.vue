<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

type Batch = {
    id: number;
    batch_number: string;
    prediction: string;
    ml_prediction: string | null;
    confidence: string | null;
    collection_center: string | null;
    tested_by: string | null;
    created_at: string | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    batches: Paginated<Batch>;
    insights: { total: number; High: number; Medium: number; Low: number };
    filters: { prediction?: string; search?: string };
}>();

const filterForm = reactive({
    prediction: props.filters.prediction ?? '',
    search: props.filters.search ?? '',
});

const insightLabels = ['total', 'High', 'Medium', 'Low'] as const;

const applyFilters = () => {
    router.get('/milk-batches', filterForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filterForm.prediction = '';
    filterForm.search = '';
    applyFilters();
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'History', href: '/milk-batches' },
        ],
    },
});

const predictionClass = (prediction: string) => ({
    'bg-green-100 text-green-800': prediction === 'High',
    'bg-amber-100 text-amber-800': prediction === 'Medium',
    'bg-red-100 text-red-800': prediction === 'Low',
});

const paginationLabel = (label: string) => label
    .replace('&laquo;', 'Previous')
    .replace('&raquo;', 'Next');
</script>

<template>
    <Head title="Milk Batch History" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <section class="flex flex-wrap items-center justify-between gap-4 rounded-xl border bg-card p-6 shadow-sm">
            <div>
                <h1 class="text-2xl font-bold">Milk Batch Records</h1>
                <p class="text-sm text-muted-foreground">Latest predictions first, paginated by 20 records.</p>
            </div>
            <Link href="/milk-batches/create" class="rounded-lg bg-blue-800 px-4 py-2 text-sm font-semibold text-white">
                New Prediction
            </Link>
        </section>

        <section class="grid gap-4 md:grid-cols-4">
            <div v-for="label in insightLabels" :key="label" class="rounded-xl border bg-card p-5 shadow-sm">
                <p class="text-sm text-muted-foreground">{{ label === 'total' ? 'Total Samples' : `${label} Quality` }}</p>
                <p class="mt-2 text-3xl font-bold">{{ insights[label] }}</p>
            </div>
        </section>

        <section class="rounded-xl border bg-card p-5 shadow-sm">
            <form class="grid gap-4 md:grid-cols-[1fr_220px_auto_auto]" @submit.prevent="applyFilters">
                <input v-model="filterForm.search" class="rounded-md border bg-background px-3 py-2 text-sm" placeholder="Search batch, center, or tester">
                <select v-model="filterForm.prediction" class="rounded-md border bg-background px-3 py-2 text-sm">
                    <option value="">All predictions</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
                <button type="submit" class="rounded-lg bg-blue-800 px-4 py-2 text-sm font-semibold text-white">Apply</button>
                <button type="button" class="rounded-lg border px-4 py-2 text-sm font-semibold" @click="clearFilters">Clear</button>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">Batch</th>
                            <th class="px-5 py-3">Prediction</th>
                            <th class="px-5 py-3">Raw ML Vote</th>
                            <th class="px-5 py-3">Confidence</th>
                            <th class="px-5 py-3">Collection Center</th>
                            <th class="px-5 py-3">Tested By</th>
                            <th class="px-5 py-3">Created</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="batches.data.length === 0">
                            <td colspan="8" class="px-5 py-8 text-center text-muted-foreground">No milk batch records yet.</td>
                        </tr>
                        <tr v-for="batch in batches.data" :key="batch.id" class="border-t">
                            <td class="px-5 py-3 font-medium">{{ batch.batch_number }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="predictionClass(batch.prediction)">
                                    {{ batch.prediction }}
                                </span>
                            </td>
                            <td class="px-5 py-3">{{ batch.ml_prediction ?? 'N/A' }}</td>
                            <td class="px-5 py-3">{{ batch.confidence ?? 'N/A' }}</td>
                            <td class="px-5 py-3">{{ batch.collection_center ?? 'N/A' }}</td>
                            <td class="px-5 py-3">{{ batch.tested_by ?? 'N/A' }}</td>
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

            <div class="flex flex-wrap items-center justify-between gap-3 border-t p-4 text-sm">
                <span class="text-muted-foreground">Showing {{ batches.from ?? 0 }} to {{ batches.to ?? 0 }} of {{ batches.total }}</span>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in batches.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        class="rounded-md border px-3 py-1.5"
                        :class="{ 'bg-blue-800 text-white': link.active, 'pointer-events-none opacity-40': !link.url }"
                    >
                        {{ paginationLabel(link.label) }}
                    </Link>
                </div>
            </div>
        </section>
    </div>
</template>
