<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Users } from '@lucide/vue';

type CompanyUser = {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string | null;
};

defineProps<{
    users: CompanyUser[];
    roles: string[];
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'tester',
});

const submit = () => {
    form.post('/company/users', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Company Users', href: '/company/users' },
        ],
    },
});
</script>

<template>
    <Head title="Company Users" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <section class="rounded-2xl border bg-gradient-to-br from-blue-950 to-blue-800 p-8 text-white shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-100">Company Admin</p>
            <h1 class="mt-3 text-3xl font-bold">Company users</h1>
            <p class="mt-3 max-w-3xl text-blue-100">
                Add users to your company workspace. These users can only access records, reports, and analytics for this company.
            </p>
        </section>

        <form class="rounded-xl border bg-card p-6 shadow-sm" @submit.prevent="submit">
            <div class="mb-5 flex items-center gap-3">
                <Users class="size-5 text-blue-800" />
                <h2 class="text-lg font-semibold">Create Company User</h2>
            </div>
            <div class="grid gap-4 md:grid-cols-4">
                <label class="grid gap-2 text-sm">
                    Name
                    <input v-model="form.name" required class="rounded-md border bg-background px-3 py-2">
                </label>
                <label class="grid gap-2 text-sm">
                    Email
                    <input v-model="form.email" required type="email" class="rounded-md border bg-background px-3 py-2">
                </label>
                <label class="grid gap-2 text-sm">
                    Role
                    <select v-model="form.role" required class="rounded-md border bg-background px-3 py-2">
                        <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                    </select>
                </label>
                <label class="grid gap-2 text-sm">
                    Temporary Password
                    <input v-model="form.password" required type="password" minlength="8" class="rounded-md border bg-background px-3 py-2">
                </label>
            </div>
            <button type="submit" :disabled="form.processing" class="mt-5 rounded-lg bg-blue-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">
                {{ form.processing ? 'Creating...' : 'Create User' }}
            </button>
        </form>

        <section class="rounded-xl border bg-card shadow-sm">
            <div class="border-b p-5">
                <h2 class="text-lg font-semibold">Workspace Users</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Name</th>
                            <th class="px-5 py-3 font-semibold">Email</th>
                            <th class="px-5 py-3 font-semibold">Role</th>
                            <th class="px-5 py-3 font-semibold">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id" class="border-t">
                            <td class="px-5 py-3 font-medium">{{ user.name }}</td>
                            <td class="px-5 py-3 text-muted-foreground">{{ user.email }}</td>
                            <td class="px-5 py-3">{{ user.role }}</td>
                            <td class="px-5 py-3 text-muted-foreground">{{ user.created_at ?? 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
