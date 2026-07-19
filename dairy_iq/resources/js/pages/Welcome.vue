<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, BarChart3, Building2, FileText, ShieldCheck } from '@lucide/vue';
import { computed } from 'vue';
import { dashboard, login } from '@/routes';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="DairyIQ Analytics" />

    <main class="min-h-screen bg-[#f5f7fb] text-[#2c3e50] dark:bg-[#07101f] dark:text-white">
        <header class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
            <div class="flex items-center gap-3">
                <div class="flex size-12 items-center justify-center rounded-xl bg-[#1f3a93] p-2 shadow-lg shadow-blue-900/20">
                    <img src="/brand/dairyiq-logo-white.png" alt="DairyIQ" class="size-full object-contain">
                </div>
                <div>
                    <p class="text-lg font-bold leading-tight text-[#1f3a93] dark:text-white">DairyIQ</p>
                    <p class="text-xs text-slate-500 dark:text-slate-300">Milk Quality Analytics</p>
                </div>
            </div>

            <nav class="flex items-center gap-3 text-sm font-medium">
                <Link
                    v-if="user"
                    :href="dashboard()"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#1f3a93] px-4 py-2 text-white shadow-sm hover:bg-[#18307a]"
                >
                    Dashboard
                    <ArrowRight class="size-4" />
                </Link>
                <template v-else>
                    <Link :href="login()" class="rounded-lg px-4 py-2 text-[#1f3a93] hover:bg-white dark:text-white dark:hover:bg-white/10">
                        Log in
                    </Link>
                </template>
            </nav>
        </header>

        <section class="mx-auto grid min-h-[calc(100vh-96px)] w-full max-w-6xl items-center gap-10 px-6 py-12 lg:grid-cols-[1.05fr_0.95fr]">
            <div>
                <div class="mb-5 inline-flex rounded-full bg-blue-100 px-4 py-2 text-sm font-semibold text-[#1f3a93] dark:bg-blue-950 dark:text-blue-100">
                    Random Forest milk quality prediction
                </div>
                <h1 class="max-w-3xl text-4xl font-bold tracking-tight text-[#1f3a93] sm:text-5xl lg:text-6xl dark:text-white">
                    DairyIQ Analytics for approved raw milk quality decisions.
                </h1>
                <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg dark:text-slate-300">
                    A company-scoped Laravel system connected to a Python Random Forest ML service for classifying milk quality as High, Medium, or Low using the approved 11-feature contract.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        :href="user ? dashboard() : login()"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#1f3a93] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 hover:bg-[#18307a]"
                    >
                        {{ user ? 'Open Dashboard' : 'Log in to DairyIQ' }}
                        <ArrowRight class="size-4" />
                    </Link>
                    <span class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-[#2c3e50] dark:border-white/15 dark:bg-white/5 dark:text-white">
                        Accounts are created by an administrator
                    </span>
                </div>
            </div>

            <div class="rounded-3xl border border-white/70 bg-white p-6 shadow-2xl shadow-blue-950/10 dark:border-white/10 dark:bg-white/5">
                <div class="rounded-2xl bg-gradient-to-br from-[#1f3a93] to-[#3498db] p-6 text-white">
                    <img src="/brand/dairyiq-logo-white.png" alt="DairyIQ logo" class="mb-8 h-24 w-24 object-contain">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-100">US EAS 67:2023 aligned</p>
                    <h2 class="mt-3 text-3xl font-bold">One workspace for milk testing, records, dashboards, and reports.</h2>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border p-4 dark:border-white/10">
                        <ShieldCheck class="mb-3 size-6 text-[#2ecc71]" />
                        <h3 class="font-semibold">Company scoped</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-300">Users only access records for their assigned company.</p>
                    </div>
                    <div class="rounded-2xl border p-4 dark:border-white/10">
                        <BarChart3 class="mb-3 size-6 text-[#3498db]" />
                        <h3 class="font-semibold">Analytics ready</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-300">Dashboards show quality trends and district performance.</p>
                    </div>
                    <div class="rounded-2xl border p-4 dark:border-white/10">
                        <FileText class="mb-3 size-6 text-[#f39c12]" />
                        <h3 class="font-semibold">Report exports</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-300">Saved batch records generate PDF reports.</p>
                    </div>
                    <div class="rounded-2xl border p-4 dark:border-white/10">
                        <Building2 class="mb-3 size-6 text-[#e74c3c]" />
                        <h3 class="font-semibold">Admin control</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-300">Super admins manage companies, users, and access.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>
