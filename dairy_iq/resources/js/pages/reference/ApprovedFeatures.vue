<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ClipboardList } from '@lucide/vue';

type ApprovedFeature = {
    Parameter: string;
    DisplayName: string;
    Type: string;
    Unit: string;
    NormalRange: string;
    Min: number | null;
    Max: number | null;
    Source: string;
    Remarks: string;
};

defineProps<{
    features: ApprovedFeature[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Approved 11-feature contract', href: '/approved-features' },
        ],
    },
});
</script>

<template>
    <Head title="Approved 11-feature Contract" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <section class="rounded-2xl border bg-gradient-to-br from-blue-950 to-blue-800 p-8 text-white shadow-sm">
            <div class="flex items-start gap-4">
                <div class="rounded-lg bg-white/15 p-3">
                    <ClipboardList class="size-7" />
                </div>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-blue-100">Shared Reference</p>
                    <h1 class="mt-2 text-3xl font-bold">Approved 11-feature contract</h1>
                    <p class="mt-3 max-w-4xl text-blue-100">
                        These are the approved model inputs, normal/reference ranges, and sources used across DairyIQ.
                        This page is shared by all company workspaces and does not depend on company-specific records.
                    </p>
                </div>
            </div>
        </section>

        <section class="rounded-xl border bg-card shadow-sm">
            <div class="border-b p-5">
                <h2 class="text-lg font-semibold">Approved Parameters and References</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    SNF and Turbidity are intentionally excluded because they are not part of the approved 11-feature model contract.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">#</th>
                            <th class="px-5 py-3 font-semibold">Model Feature</th>
                            <th class="px-5 py-3 font-semibold">Display Name</th>
                            <th class="px-5 py-3 font-semibold">Type</th>
                            <th class="px-5 py-3 font-semibold">Unit</th>
                            <th class="px-5 py-3 font-semibold">Normal / Reference Range</th>
                            <th class="px-5 py-3 font-semibold">Source</th>
                            <th class="px-5 py-3 font-semibold">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="features.length === 0">
                            <td colspan="8" class="px-5 py-10 text-center text-muted-foreground">
                                No approved feature reference data is configured.
                            </td>
                        </tr>
                        <tr v-for="(feature, index) in features" :key="feature.Parameter" class="border-t align-top transition-colors hover:bg-muted/30">
                            <td class="px-5 py-4 font-medium text-muted-foreground">{{ index + 1 }}</td>
                            <td class="px-5 py-4 font-semibold">{{ feature.Parameter }}</td>
                            <td class="px-5 py-4">{{ feature.DisplayName }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold capitalize text-blue-800">
                                    {{ feature.Type }}
                                </span>
                            </td>
                            <td class="px-5 py-4">{{ feature.Unit }}</td>
                            <td class="px-5 py-4 font-medium">{{ feature.NormalRange }}</td>
                            <td class="px-5 py-4">{{ feature.Source }}</td>
                            <td class="px-5 py-4 text-muted-foreground">{{ feature.Remarks }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
