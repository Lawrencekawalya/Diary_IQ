<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BookOpen, CheckCircle2, ShieldCheck, Users } from '@lucide/vue';

const roles = [
    {
        name: 'Super Admin',
        systemValue: 'super_admin',
        scope: 'Platform-wide',
        purpose: 'Owns the DairyIQ platform setup and creates company workspaces.',
        allowed: [
            'Create dairy company workspaces.',
            'Create company admins and testers under any company.',
            'View company workspace counts and setup information.',
            'Read platform documentation and approved model reference pages.',
        ],
        restricted: [
            'Does not submit milk prediction tests directly.',
            'Does not edit company-scoped prediction records as a company user.',
            'Is not attached to one company workspace.',
        ],
    },
    {
        name: 'Company Admin',
        systemValue: 'company_admin',
        scope: 'One assigned company',
        purpose: 'Manages the company workspace and supervises prediction operations.',
        allowed: [
            'Create milk quality predictions for their company.',
            'View history, reports, and dashboard analytics for their company only.',
            'Manage company profile/settings.',
            'Create company users for the same company.',
            'Access the approved 11-feature model contract reference.',
        ],
        restricted: [
            'Cannot create or manage other companies.',
            'Cannot access another company’s prediction records or reports.',
            'Cannot access the super-admin company administration screen.',
        ],
    },
    {
        name: 'Tester',
        systemValue: 'tester',
        scope: 'One assigned company',
        purpose: 'Performs milk quality tests and records prediction results.',
        allowed: [
            'Create milk quality predictions for their assigned company.',
            'View company-scoped history, result pages, dashboard analytics, and reports.',
            'Access the approved 11-feature model contract reference.',
        ],
        restricted: [
            'Cannot manage company users.',
            'Cannot edit company settings.',
            'Cannot create companies or assign users.',
            'Cannot access another company’s data.',
        ],
    },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin Documentation', href: '/admin/documentation' },
        ],
    },
});
</script>

<template>
    <Head title="Admin Documentation" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <section class="rounded-2xl border bg-gradient-to-br from-slate-950 to-blue-900 p-8 text-white shadow-sm">
            <div class="flex items-start gap-4">
                <div class="rounded-lg bg-white/15 p-3">
                    <BookOpen class="size-7" />
                </div>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-blue-100">Super Admin Guide</p>
                    <h1 class="mt-2 text-3xl font-bold">DairyIQ administration documentation</h1>
                    <p class="mt-3 max-w-4xl text-blue-100">
                        This page documents the platform roles, what each role can do, and the restrictions used to keep
                        company records separated.
                    </p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-xl border bg-card p-5 shadow-sm">
                <ShieldCheck class="size-6 text-blue-800" />
                <h2 class="mt-3 text-lg font-semibold">Platform Control</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Super admins handle company creation and high-level account setup.
                </p>
            </article>
            <article class="rounded-xl border bg-card p-5 shadow-sm">
                <Users class="size-6 text-blue-800" />
                <h2 class="mt-3 text-lg font-semibold">Company Isolation</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Company admins and testers only work inside their assigned company workspace.
                </p>
            </article>
            <article class="rounded-xl border bg-card p-5 shadow-sm">
                <CheckCircle2 class="size-6 text-blue-800" />
                <h2 class="mt-3 text-lg font-semibold">Audit Clarity</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Every prediction record is tied to a company and the user who created it.
                </p>
            </article>
        </section>

        <section class="rounded-xl border bg-card shadow-sm">
            <div class="border-b p-5">
                <h2 class="text-lg font-semibold">Roles and Permissions</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Use these roles when creating users so each person receives only the access needed for their work.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Role</th>
                            <th class="px-5 py-3 font-semibold">System Value</th>
                            <th class="px-5 py-3 font-semibold">Scope</th>
                            <th class="px-5 py-3 font-semibold">Main Purpose</th>
                            <th class="px-5 py-3 font-semibold">Allowed Actions</th>
                            <th class="px-5 py-3 font-semibold">Restrictions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="role in roles" :key="role.systemValue" class="border-t align-top">
                            <td class="px-5 py-4 font-semibold">{{ role.name }}</td>
                            <td class="px-5 py-4">
                                <code class="rounded bg-muted px-2 py-1 text-xs">{{ role.systemValue }}</code>
                            </td>
                            <td class="px-5 py-4">{{ role.scope }}</td>
                            <td class="px-5 py-4 text-muted-foreground">{{ role.purpose }}</td>
                            <td class="px-5 py-4">
                                <ul class="list-disc space-y-1 pl-4">
                                    <li v-for="item in role.allowed" :key="item">{{ item }}</li>
                                </ul>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                <ul class="list-disc space-y-1 pl-4">
                                    <li v-for="item in role.restricted" :key="item">{{ item }}</li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
