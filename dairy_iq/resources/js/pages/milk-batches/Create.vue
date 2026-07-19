<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { FlaskConical } from '@lucide/vue';
import { computed } from 'vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'New Prediction', href: '/milk-batches/create' },
        ],
    },
});

type PredictionForm = {
    batch_number: string;
    collection_center: string;
    district: string;
    tested_by: string;
    liters_collected: string;
    pH: string;
    Temperature: string;
    Taste: string;
    Odor: string;
    Fat_Content: string;
    Titratable_Acidity: string;
    Protein_Content: string;
    Lactose_Content: string;
    TPC: string;
    SCC: string;
    Color: string;
};

const numericFields = [
    'pH',
    'Temperature',
    'Fat_Content',
    'Titratable_Acidity',
    'Protein_Content',
    'Lactose_Content',
    'TPC',
    'SCC',
] as const;

const form = useForm<PredictionForm>({
    batch_number: '',
    collection_center: '',
    district: '',
    tested_by: '',
    liters_collected: '',
    pH: '6.70',
    Temperature: '4.0',
    Taste: 'normal',
    Odor: 'fresh',
    Fat_Content: '3.8',
    Titratable_Acidity: '0.15',
    Protein_Content: '3.3',
    Lactose_Content: '4.8',
    TPC: '50000',
    SCC: '200000',
    Color: 'normal',
});

const predictionError = computed(() => (form.errors as Record<string, string>).prediction);

const submit = () => {
    form.post('/milk-batches/predictions', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="New Milk Quality Prediction" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <section class="rounded-xl border bg-card p-6 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="rounded-lg bg-blue-100 p-3 text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                    <FlaskConical class="size-6" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold">New milk quality prediction</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Enter exactly the approved thesis 11 features. SNF and Turbidity are intentionally not part of this model contract.
                    </p>
                </div>
            </div>
        </section>

        <form class="grid gap-6" @submit.prevent="submit">
            <div v-if="predictionError" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                {{ predictionError }}
            </div>

            <section class="rounded-xl border bg-card p-6 shadow-sm">
                <h2 class="text-lg font-semibold">Batch details</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <label class="grid gap-2 text-sm">
                        Batch Number
                        <input v-model="form.batch_number" class="rounded-md border bg-background px-3 py-2" placeholder="Auto-generated if empty">
                    </label>
                    <label class="grid gap-2 text-sm">
                        Collection Center
                        <input v-model="form.collection_center" class="rounded-md border bg-background px-3 py-2">
                    </label>
                    <label class="grid gap-2 text-sm">
                        District
                        <input v-model="form.district" class="rounded-md border bg-background px-3 py-2">
                    </label>
                    <label class="grid gap-2 text-sm">
                        Tested By
                        <input v-model="form.tested_by" class="rounded-md border bg-background px-3 py-2">
                    </label>
                    <label class="grid gap-2 text-sm">
                        Liters Collected
                        <input v-model="form.liters_collected" type="number" step="0.01" min="0" class="rounded-md border bg-background px-3 py-2">
                    </label>
                </div>
            </section>

            <section class="rounded-xl border bg-card p-6 shadow-sm">
                <h2 class="text-lg font-semibold">Approved 11 model inputs</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <label v-for="field in numericFields" :key="field" class="grid gap-2 text-sm">
                        {{ field }}
                        <input v-model="form[field]" type="number" step="any" min="0" class="rounded-md border bg-background px-3 py-2" :aria-invalid="Boolean(form.errors[field])">
                        <span v-if="form.errors[field]" class="text-xs text-red-600">{{ form.errors[field] }}</span>
                    </label>

                    <label class="grid gap-2 text-sm">
                        Taste
                        <select v-model="form.Taste" class="rounded-md border bg-background px-3 py-2">
                            <option value="normal">Normal / acceptable</option>
                            <option value="abnormal">Abnormal / off-taste</option>
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm">
                        Odor
                        <select v-model="form.Odor" class="rounded-md border bg-background px-3 py-2">
                            <option value="fresh">Fresh / normal</option>
                            <option value="abnormal">Abnormal / objectionable</option>
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm">
                        Color
                        <select v-model="form.Color" class="rounded-md border bg-background px-3 py-2">
                            <option value="normal">Normal / creamy-white</option>
                            <option value="abnormal">Abnormal appearance</option>
                        </select>
                    </label>
                </div>
            </section>

            <div class="flex justify-end">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-800 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60">
                    {{ form.processing ? 'Predicting...' : 'Predict Milk Quality' }}
                </button>
            </div>
        </form>
    </div>
</template>
