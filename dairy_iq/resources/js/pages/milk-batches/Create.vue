<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { FlaskConical } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    batchNumber: string;
    districts: string[];
}>();

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
    driver_name: string;
    vehicle_number: string;
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
    batch_number: props.batchNumber,
    collection_center: '',
    district: '',
    tested_by: '',
    driver_name: '',
    vehicle_number: '',
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
const districtSearchOpen = ref(false);

const normalizeTextInput = (value: string) => value.trim().replace(/\s+/g, ' ');

const normalizeDistrict = () => {
    const normalized = normalizeTextInput(form.district);

    form.district = normalized
        ? normalized
            .toLocaleLowerCase()
            .replace(/\b\p{L}/gu, (letter) => letter.toLocaleUpperCase())
        : '';
};

const filteredDistricts = computed(() => {
    const search = normalizeTextInput(form.district).toLocaleLowerCase();

    if (!search) {
        return props.districts.slice(0, 12);
    }

    return props.districts
        .filter((district) => district.toLocaleLowerCase().includes(search))
        .slice(0, 12);
});

const selectDistrict = (district: string) => {
    form.district = district;
    districtSearchOpen.value = false;
};

const closeDistrictSearch = () => {
    normalizeDistrict();
    window.setTimeout(() => {
        districtSearchOpen.value = false;
    }, 120);
};

const submit = () => {
    normalizeDistrict();

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
                        <input v-model="form.batch_number" required readonly class="cursor-not-allowed rounded-md border bg-muted px-3 py-2 text-muted-foreground" :aria-invalid="Boolean(form.errors.batch_number)">
                        <span class="text-xs text-muted-foreground">Generated automatically for traceability.</span>
                        <span v-if="form.errors.batch_number" class="text-xs text-red-600">{{ form.errors.batch_number }}</span>
                    </label>
                    <label class="grid gap-2 text-sm">
                        Collection Center
                        <input v-model="form.collection_center" required class="rounded-md border bg-background px-3 py-2" placeholder="Example: ADC Dairy" :aria-invalid="Boolean(form.errors.collection_center)">
                        <span class="text-xs text-muted-foreground">Tell us where the milk was collected.</span>
                        <span v-if="form.errors.collection_center" class="text-xs text-red-600">{{ form.errors.collection_center }}</span>
                    </label>
                    <label class="grid gap-2 text-sm">
                        District
                        <div class="relative">
                            <input
                                v-model="form.district"
                                required
                                autocomplete="off"
                                class="w-full rounded-md border bg-background px-3 py-2"
                                list="uganda-districts"
                                placeholder="Type to search, e.g. Kazo"
                                :aria-expanded="districtSearchOpen"
                                :aria-invalid="Boolean(form.errors.district)"
                                role="combobox"
                                @blur="closeDistrictSearch"
                                @focus="districtSearchOpen = true"
                                @input="districtSearchOpen = true"
                            >
                            <div
                                v-if="districtSearchOpen && filteredDistricts.length > 0"
                                class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border bg-popover p-1 shadow-lg"
                            >
                                <button
                                    v-for="district in filteredDistricts"
                                    :key="district"
                                    type="button"
                                    class="block w-full rounded-sm px-3 py-2 text-left text-sm hover:bg-muted"
                                    @mousedown.prevent="selectDistrict(district)"
                                >
                                    {{ district }}
                                </button>
                            </div>
                        </div>
                        <datalist id="uganda-districts">
                            <option v-for="district in props.districts" :key="district" :value="district" />
                        </datalist>
                        <span class="text-xs text-muted-foreground">Select a Uganda district or city from the search list. Casing is normalized before saving.</span>
                        <span v-if="form.errors.district" class="text-xs text-red-600">{{ form.errors.district }}</span>
                    </label>
                    <label class="grid gap-2 text-sm">
                        Tested By
                        <input v-model="form.tested_by" required class="rounded-md border bg-background px-3 py-2" :aria-invalid="Boolean(form.errors.tested_by)">
                        <span v-if="form.errors.tested_by" class="text-xs text-red-600">{{ form.errors.tested_by }}</span>
                    </label>
                    <label class="grid gap-2 text-sm">
                        Driver Name
                        <input v-model="form.driver_name" required class="rounded-md border bg-background px-3 py-2" placeholder="Example: John Mukasa" :aria-invalid="Boolean(form.errors.driver_name)">
                        <span v-if="form.errors.driver_name" class="text-xs text-red-600">{{ form.errors.driver_name }}</span>
                    </label>
                    <label class="grid gap-2 text-sm">
                        Vehicle Number
                        <input v-model="form.vehicle_number" required class="rounded-md border bg-background px-3 py-2 uppercase" placeholder="Example: UBA 123A" :aria-invalid="Boolean(form.errors.vehicle_number)">
                        <span v-if="form.errors.vehicle_number" class="text-xs text-red-600">{{ form.errors.vehicle_number }}</span>
                    </label>
                    <label class="grid gap-2 text-sm">
                        Liters Collected
                        <input v-model="form.liters_collected" required type="number" step="0.01" min="0" class="rounded-md border bg-background px-3 py-2" :aria-invalid="Boolean(form.errors.liters_collected)">
                        <span v-if="form.errors.liters_collected" class="text-xs text-red-600">{{ form.errors.liters_collected }}</span>
                    </label>
                </div>
            </section>

            <section class="rounded-xl border bg-card p-6 shadow-sm">
                <h2 class="text-lg font-semibold">Approved 11 model inputs</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <label v-for="field in numericFields" :key="field" class="grid gap-2 text-sm">
                        {{ field }}
                        <input v-model="form[field]" required type="number" step="any" min="0" class="rounded-md border bg-background px-3 py-2" :aria-invalid="Boolean(form.errors[field])">
                        <span v-if="form.errors[field]" class="text-xs text-red-600">{{ form.errors[field] }}</span>
                    </label>

                    <label class="grid gap-2 text-sm">
                        Taste
                        <select v-model="form.Taste" required class="rounded-md border bg-background px-3 py-2" :aria-invalid="Boolean(form.errors.Taste)">
                            <option value="normal">Normal / acceptable</option>
                            <option value="abnormal">Abnormal / off-taste</option>
                        </select>
                        <span v-if="form.errors.Taste" class="text-xs text-red-600">{{ form.errors.Taste }}</span>
                    </label>
                    <label class="grid gap-2 text-sm">
                        Odor
                        <select v-model="form.Odor" required class="rounded-md border bg-background px-3 py-2" :aria-invalid="Boolean(form.errors.Odor)">
                            <option value="fresh">Fresh / normal</option>
                            <option value="abnormal">Abnormal / objectionable</option>
                        </select>
                        <span v-if="form.errors.Odor" class="text-xs text-red-600">{{ form.errors.Odor }}</span>
                    </label>
                    <label class="grid gap-2 text-sm">
                        Color
                        <select v-model="form.Color" required class="rounded-md border bg-background px-3 py-2" :aria-invalid="Boolean(form.errors.Color)">
                            <option value="normal">Normal / creamy-white</option>
                            <option value="abnormal">Abnormal appearance</option>
                        </select>
                        <span v-if="form.errors.Color" class="text-xs text-red-600">{{ form.errors.Color }}</span>
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
