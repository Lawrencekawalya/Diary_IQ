<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Building2, CheckCircle2, MapPin, Plus, Trash2, Truck, XCircle } from '@lucide/vue';
import { ref } from 'vue';

type Vehicle = {
    id: number;
    plate_number: string;
    driver_name: string | null;
    is_active: boolean;
    milk_batches_count: number;
    created_at: string | null;
};

type CollectionCenter = {
    id: number;
    name: string;
    district: string | null;
    is_active: boolean;
    milk_batches_count: number;
    created_at: string | null;
};

const props = defineProps<{
    vehicles: Vehicle[];
    collectionCenters: CollectionCenter[];
}>();

const activeTab = ref<'centers' | 'vehicles'>('centers');

// Vehicle form
const vehicleForm = useForm({
    plate_number: '',
    driver_name: '',
});

const submitVehicle = () => {
    vehicleForm.post('/company/vehicles', {
        preserveScroll: true,
        onSuccess: () => vehicleForm.reset(),
    });
};

const toggleVehicleStatus = (vehicle: Vehicle) => {
    router.put(`/company/vehicles/${vehicle.id}`, {
        plate_number: vehicle.plate_number,
        driver_name: vehicle.driver_name,
        is_active: !vehicle.is_active,
    }, {
        preserveScroll: true,
    });
};

const deleteVehicle = (vehicle: Vehicle) => {
    if (confirm(`Are you sure you want to remove vehicle ${vehicle.plate_number}?`)) {
        router.delete(`/company/vehicles/${vehicle.id}`, {
            preserveScroll: true,
        });
    }
};

// Collection center form
const centerForm = useForm({
    name: '',
    district: '',
});

const submitCenter = () => {
    centerForm.post('/company/collection-centers', {
        preserveScroll: true,
        onSuccess: () => centerForm.reset(),
    });
};

const toggleCenterStatus = (center: CollectionCenter) => {
    router.put(`/company/collection-centers/${center.id}`, {
        name: center.name,
        district: center.district,
        is_active: !center.is_active,
    }, {
        preserveScroll: true,
    });
};

const deleteCenter = (center: CollectionCenter) => {
    if (confirm(`Are you sure you want to remove collection center "${center.name}"?`)) {
        router.delete(`/company/collection-centers/${center.id}`, {
            preserveScroll: true,
        });
    }
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Company Assets', href: '/company/assets' },
        ],
    },
});
</script>

<template>
    <Head title="Company Fleet & Centers" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Hero Header -->
        <section class="rounded-2xl border bg-gradient-to-br from-blue-950 to-blue-800 p-8 text-white shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-100">Company Logistics & Traceability</p>
            <h1 class="mt-3 text-3xl font-bold">Company Vehicles & Collection Centers</h1>
            <p class="mt-3 max-w-3xl text-blue-100">
                Register and manage the authorized vehicles and collection centers for your company. Testers can only select from active assets registered here when recording milk quality tests.
            </p>
        </section>

        <!-- Tab Selector -->
        <div class="flex gap-2 border-b">
            <button
                type="button"
                class="flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-semibold transition"
                :class="activeTab === 'centers' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-muted-foreground hover:text-foreground'"
                @click="activeTab = 'centers'"
            >
                <Building2 class="size-4" />
                Collection Centers ({{ props.collectionCenters.length }})
            </button>
            <button
                type="button"
                class="flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-semibold transition"
                :class="activeTab === 'vehicles' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-muted-foreground hover:text-foreground'"
                @click="activeTab = 'vehicles'"
            >
                <Truck class="size-4" />
                Vehicles & Tankers ({{ props.vehicles.length }})
            </button>
        </div>

        <!-- COLLECTION CENTERS SECTION -->
        <div v-if="activeTab === 'centers'" class="grid gap-6">
            <!-- Add Center Form -->
            <form class="rounded-xl border bg-card p-6 shadow-sm" @submit.prevent="submitCenter">
                <div class="mb-4 flex items-center gap-3">
                    <Building2 class="size-5 text-blue-600" />
                    <h2 class="text-lg font-semibold">Register New Collection Center</h2>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <label class="grid gap-2 text-sm">
                        Center Name *
                        <input
                            v-model="centerForm.name"
                            required
                            class="rounded-md border bg-background px-3 py-2"
                            placeholder="e.g. Nyabushozi Milk Shed"
                            :aria-invalid="Boolean(centerForm.errors.name)"
                        >
                        <span v-if="centerForm.errors.name" class="text-xs text-red-600">{{ centerForm.errors.name }}</span>
                    </label>
                    <label class="grid gap-2 text-sm">
                        District / Location
                        <input
                            v-model="centerForm.district"
                            class="rounded-md border bg-background px-3 py-2"
                            placeholder="e.g. Kiruhura"
                            :aria-invalid="Boolean(centerForm.errors.district)"
                        >
                        <span v-if="centerForm.errors.district" class="text-xs text-red-600">{{ centerForm.errors.district }}</span>
                    </label>
                    <div class="flex items-end">
                        <button
                            type="submit"
                            :disabled="centerForm.processing"
                            class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
                        >
                            <Plus class="size-4" />
                            Add Center
                        </button>
                    </div>
                </div>
            </form>

            <!-- Centers Table -->
            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h3 class="font-semibold text-base">Registered Centers</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                            <tr>
                                <th class="p-4">Name</th>
                                <th class="p-4">District</th>
                                <th class="p-4">Batches Tested</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="center in props.collectionCenters" :key="center.id" class="hover:bg-muted/30">
                                <td class="p-4 font-medium flex items-center gap-2">
                                    <Building2 class="size-4 text-muted-foreground" />
                                    {{ center.name }}
                                </td>
                                <td class="p-4 text-muted-foreground">
                                    <span v-if="center.district" class="inline-flex items-center gap-1">
                                        <MapPin class="size-3 text-muted-foreground" />
                                        {{ center.district }}
                                    </span>
                                    <span v-else>—</span>
                                </td>
                                <td class="p-4 font-mono text-xs">
                                    {{ center.milk_batches_count }} batches
                                </td>
                                <td class="p-4">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="center.is_active ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300'"
                                    >
                                        <CheckCircle2 v-if="center.is_active" class="size-3" />
                                        <XCircle v-else class="size-3" />
                                        {{ center.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded border px-2.5 py-1 text-xs font-medium hover:bg-muted"
                                            @click="toggleCenterStatus(center)"
                                        >
                                            {{ center.is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded p-1 text-muted-foreground hover:text-red-600"
                                            title="Delete"
                                            @click="deleteCenter(center)"
                                        >
                                            <Trash2 class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="props.collectionCenters.length === 0">
                                <td colspan="5" class="p-8 text-center text-muted-foreground">
                                    No collection centers registered yet. Use the form above to add your first center.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- VEHICLES SECTION -->
        <div v-if="activeTab === 'vehicles'" class="grid gap-6">
            <!-- Add Vehicle Form -->
            <form class="rounded-xl border bg-card p-6 shadow-sm" @submit.prevent="submitVehicle">
                <div class="mb-4 flex items-center gap-3">
                    <Truck class="size-5 text-blue-600" />
                    <h2 class="text-lg font-semibold">Register New Vehicle / Tanker</h2>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <label class="grid gap-2 text-sm">
                        Number Plate *
                        <input
                            v-model="vehicleForm.plate_number"
                            required
                            class="rounded-md border bg-background px-3 py-2 uppercase font-mono"
                            placeholder="e.g. UBG 456K"
                            :aria-invalid="Boolean(vehicleForm.errors.plate_number)"
                        >
                        <span v-if="vehicleForm.errors.plate_number" class="text-xs text-red-600">{{ vehicleForm.errors.plate_number }}</span>
                    </label>
                    <label class="grid gap-2 text-sm">
                        Default Driver Name
                        <input
                            v-model="vehicleForm.driver_name"
                            class="rounded-md border bg-background px-3 py-2"
                            placeholder="e.g. Dennis Okello"
                            :aria-invalid="Boolean(vehicleForm.errors.driver_name)"
                        >
                        <span v-if="vehicleForm.errors.driver_name" class="text-xs text-red-600">{{ vehicleForm.errors.driver_name }}</span>
                    </label>
                    <div class="flex items-end">
                        <button
                            type="submit"
                            :disabled="vehicleForm.processing"
                            class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
                        >
                            <Plus class="size-4" />
                            Add Vehicle
                        </button>
                    </div>
                </div>
            </form>

            <!-- Vehicles Table -->
            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="p-4 border-b">
                    <h3 class="font-semibold text-base">Registered Vehicles</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                            <tr>
                                <th class="p-4">Number Plate</th>
                                <th class="p-4">Assigned Driver</th>
                                <th class="p-4">Batches Hauled</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="vehicle in props.vehicles" :key="vehicle.id" class="hover:bg-muted/30">
                                <td class="p-4 font-mono font-semibold flex items-center gap-2">
                                    <Truck class="size-4 text-muted-foreground" />
                                    {{ vehicle.plate_number }}
                                </td>
                                <td class="p-4 text-muted-foreground">
                                    {{ vehicle.driver_name || '—' }}
                                </td>
                                <td class="p-4 font-mono text-xs">
                                    {{ vehicle.milk_batches_count }} batches
                                </td>
                                <td class="p-4">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="vehicle.is_active ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300'"
                                    >
                                        <CheckCircle2 v-if="vehicle.is_active" class="size-3" />
                                        <XCircle v-else class="size-3" />
                                        {{ vehicle.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded border px-2.5 py-1 text-xs font-medium hover:bg-muted"
                                            @click="toggleVehicleStatus(vehicle)"
                                        >
                                            {{ vehicle.is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded p-1 text-muted-foreground hover:text-red-600"
                                            title="Delete"
                                            @click="deleteVehicle(vehicle)"
                                        >
                                            <Trash2 class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="props.vehicles.length === 0">
                                <td colspan="5" class="p-8 text-center text-muted-foreground">
                                    No vehicles registered yet. Use the form above to register your fleet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
