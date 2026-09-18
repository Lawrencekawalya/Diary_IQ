<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, ClipboardList, FileText, ShieldCheck, Users } from '@lucide/vue';
import { dashboard } from '@/routes';

type AdminSummary = {
    companies: number;
    users: number;
    company_admins: number;
    testers: number;
    super_admins: number;
    batches: number;
    liters: number;
    High: number;
    Medium: number;
    Low: number;
};

type CompanyPerformance = {
    id: number;
    name: string;
    users_count: number;
    milk_batches_count: number;
    liters: number;
    average_quality_score: number;
    High: number;
    Medium: number;
    Low: number;
};

type RecentBatch = {
    id: number;
    batch_number: string;
    company: string;
    district: string | null;
    prediction: string;
    confidence: string | null;
    created_at: string | null;
};

const props = defineProps<{
    summary: AdminSummary;
    companyPerformance: CompanyPerformance[];
    recentBatches: RecentBatch[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Admin Dashboard',
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

const qualityScoreLabel = (score: number) => {
    if (score >= 1.5) {
        return 'High';
    }

    if (score >= 0.5) {
        return 'Medium';
    }

    return 'Low';
};
</script>

<template>
    <Head title="DairyIQ Admin Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <section class="rounded-2xl border bg-gradient-to-br from-slate-950 to-blue-900 p-8 text-white shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-100">Platform Administration</p>
            <h1 class="mt-3 text-3xl font-bold">DairyIQ super-admin dashboard</h1>
            <p class="mt-3 max-w-3xl text-blue-100">
                Monitor company workspaces, users, and platform-wide milk quality activity from one place.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                <Link href="/admin/companies" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-blue-950 transition-colors hover:bg-blue-50">
                    <Building2 class="size-4" />
                    Manage Companies
                </Link>
                <Link href="/admin/documentation" class="inline-flex items-center gap-2 rounded-lg border border-white/40 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-white/10">
                    <FileText class="size-4" />
                    Admin Documentation
                </Link>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <Building2 class="size-5 text-blue-800" />
                <p class="mt-3 text-sm text-muted-foreground">Companies</p>
                <p class="mt-2 text-3xl font-bold">{{ summary.companies }}</p>
            </div>
            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <Users class="size-5 text-blue-800" />
                <p class="mt-3 text-sm text-muted-foreground">Total Users</p>
                <p class="mt-2 text-3xl font-bold">{{ summary.users }}</p>
            </div>
            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <ClipboardList class="size-5 text-blue-800" />
                <p class="mt-3 text-sm text-muted-foreground">Saved Batches</p>
                <p class="mt-2 text-3xl font-bold">{{ summary.batches }}</p>
            </div>
            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <ShieldCheck class="size-5 text-blue-800" />
                <p class="mt-3 text-sm text-muted-foreground">Liters Recorded</p>
                <p class="mt-2 text-3xl font-bold">{{ summary.liters.toLocaleString() }}</p>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1fr_1fr]">
            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <h2 class="text-lg font-semibold">Role Distribution</h2>
                <p class="mt-1 text-sm text-muted-foreground">Current users grouped by access level.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span>Super Admins</span>
                            <span class="font-semibold">{{ summary.super_admins }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-muted">
                            <div class="h-2 rounded-full bg-blue-900" :style="{ width: `${summary.users > 0 ? (summary.super_admins / summary.users) * 100 : 0}%` }" />
                        </div>
                    </div>
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span>Company Admins</span>
                            <span class="font-semibold">{{ summary.company_admins }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-muted">
                            <div class="h-2 rounded-full bg-blue-700" :style="{ width: `${summary.users > 0 ? (summary.company_admins / summary.users) * 100 : 0}%` }" />
                        </div>
                    </div>
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span>Testers</span>
                            <span class="font-semibold">{{ summary.testers }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-muted">
                            <div class="h-2 rounded-full bg-blue-500" :style="{ width: `${summary.users > 0 ? (summary.testers / summary.users) * 100 : 0}%` }" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <h2 class="text-lg font-semibold">Platform Quality Distribution</h2>
                <p class="mt-1 text-sm text-muted-foreground">All saved predictions across company workspaces.</p>

                <div class="mt-5 grid gap-3">
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:bg-green-950/30">
                        <div class="flex justify-between">
                            <span class="font-medium text-green-800 dark:text-green-200">High Quality</span>
                            <span class="font-bold">{{ summary.High }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:bg-amber-950/30">
                        <div class="flex justify-between">
                            <span class="font-medium text-amber-800 dark:text-amber-200">Medium Quality</span>
                            <span class="font-bold">{{ summary.Medium }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:bg-red-950/30">
                        <div class="flex justify-between">
                            <span class="font-medium text-red-800 dark:text-red-200">Low Quality</span>
                            <span class="font-bold">{{ summary.Low }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-xl border bg-card shadow-sm">
            <div class="border-b p-5">
                <h2 class="text-lg font-semibold">Company Workspace Performance</h2>
                <p class="mt-1 text-sm text-muted-foreground">Top companies by saved prediction count.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Company</th>
                            <th class="px-5 py-3 font-semibold">Users</th>
                            <th class="px-5 py-3 font-semibold">Batches</th>
                            <th class="px-5 py-3 font-semibold">Liters</th>
                            <th class="px-5 py-3 font-semibold">Avg Quality</th>
                            <th class="px-5 py-3 font-semibold">Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="companyPerformance.length === 0">
                            <td colspan="6" class="px-5 py-10 text-center text-muted-foreground">
                                No company workspaces have been created yet.
                            </td>
                        </tr>
                        <tr v-for="company in props.companyPerformance" :key="company.id" class="border-t">
                            <td class="px-5 py-3 font-medium">{{ company.name }}</td>
                            <td class="px-5 py-3">{{ company.users_count }}</td>
                            <td class="px-5 py-3">{{ company.milk_batches_count }}</td>
                            <td class="px-5 py-3">{{ company.liters.toLocaleString() }}</td>
                            <td class="px-5 py-3">{{ qualityScoreLabel(company.average_quality_score) }} ({{ company.average_quality_score.toFixed(2) }})</td>
                            <td class="px-5 py-3">
                                <span class="font-medium text-green-700">H: {{ company.High }}</span>
                                <span class="ml-3 font-medium text-amber-700">M: {{ company.Medium }}</span>
                                <span class="ml-3 font-medium text-red-700">L: {{ company.Low }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border bg-card shadow-sm">
            <div class="border-b p-5">
                <h2 class="text-lg font-semibold">Recent Platform Predictions</h2>
                <p class="mt-1 text-sm text-muted-foreground">Latest saved batches across all company workspaces.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Batch</th>
                            <th class="px-5 py-3 font-semibold">Company</th>
                            <th class="px-5 py-3 font-semibold">District</th>
                            <th class="px-5 py-3 font-semibold">Prediction</th>
                            <th class="px-5 py-3 font-semibold">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="recentBatches.length === 0">
                            <td colspan="5" class="px-5 py-10 text-center text-muted-foreground">
                                No saved prediction records yet.
                            </td>
                        </tr>
                        <tr v-for="batch in props.recentBatches" :key="batch.id" class="border-t">
                            <td class="px-5 py-3 font-medium">{{ batch.batch_number }}</td>
                            <td class="px-5 py-3">{{ batch.company }}</td>
                            <td class="px-5 py-3 text-muted-foreground">{{ batch.district ?? 'N/A' }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="predictionClass(batch.prediction)">
                                    {{ batch.prediction }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">{{ batch.created_at ?? 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
