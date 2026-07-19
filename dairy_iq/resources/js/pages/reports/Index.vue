<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type Batch = {
    id: number;
    batch_number: string;
    prediction: string;
    tested_by: string | null;
    created_at: string | null;
};

defineProps<{
    batches: Batch[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Reports', href: '/reports' },
        ],
    },
});
</script>

<template>
    <Head title="Reports" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <section class="rounded-xl border bg-card p-6 shadow-sm">
            <h1 class="text-2xl font-bold">Reports and exports</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Select a saved milk batch record to review its result. PDF generation will be completed in Phase 6 from these saved records.
            </p>
        </section>

        <section class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-5 py-3">Batch</th>
                        <th class="px-5 py-3">Prediction</th>
                        <th class="px-5 py-3">Tested By</th>
                        <th class="px-5 py-3">Created</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="batches.length === 0">
                        <td colspan="5" class="px-5 py-8 text-center text-muted-foreground">No saved records are available for reports yet.</td>
                    </tr>
                    <tr v-for="batch in batches" :key="batch.id" class="border-t">
                        <td class="px-5 py-3 font-medium">{{ batch.batch_number }}</td>
                        <td class="px-5 py-3">{{ batch.prediction }}</td>
                        <td class="px-5 py-3">{{ batch.tested_by ?? 'N/A' }}</td>
                        <td class="px-5 py-3">{{ batch.created_at ?? 'N/A' }}</td>
                        <td class="px-5 py-3 text-right">
                            <Link :href="`/milk-batches/${batch.id}`" class="font-semibold text-blue-700 hover:underline">
                                View Report Source
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
