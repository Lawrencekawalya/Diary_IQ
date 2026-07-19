<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Building2, Eye, EyeOff, Users } from '@lucide/vue';
import { ref } from 'vue';

type Company = {
    id: number;
    name: string;
    contact_email: string | null;
    phone: string | null;
    address: string | null;
    users_count: number;
    milk_batches_count: number;
};

const props = defineProps<{
    companies: Company[];
    roles: string[];
}>();

const companyForm = useForm({
    name: '',
    contact_email: '',
    phone: '',
    address: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
});

const userForm = useForm({
    company_id: '',
    name: '',
    email: '',
    password: '',
    role: 'tester',
});

const showCompanyAdminPassword = ref(false);
const showCompanyUserPassword = ref(false);

const createCompany = () => {
    companyForm.post('/admin/companies', {
        preserveScroll: true,
        onSuccess: () => companyForm.reset(),
    });
};

const createCompanyUser = () => {
    if (!userForm.company_id) {
        return;
    }

    userForm.post(`/admin/companies/${userForm.company_id}/users`, {
        preserveScroll: true,
        onSuccess: () => userForm.reset('name', 'email', 'password'),
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin Companies', href: '/admin/companies' },
        ],
    },
});
</script>

<template>
    <Head title="Admin Companies" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <section class="rounded-2xl border bg-gradient-to-br from-blue-950 to-blue-800 p-8 text-white shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-100">Super Admin</p>
            <h1 class="mt-3 text-3xl font-bold">Company and user administration</h1>
            <p class="mt-3 max-w-3xl text-blue-100">
                Create approved dairy company workspaces and assign users to the correct company before they create milk quality records.
            </p>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <form class="rounded-xl border bg-card p-6 shadow-sm" @submit.prevent="createCompany">
                <div class="mb-5 flex items-center gap-3">
                    <Building2 class="size-5 text-blue-800" />
                    <h2 class="text-lg font-semibold">Create Company Workspace</h2>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="grid gap-2 text-sm">
                        Company Name
                        <input v-model="companyForm.name" required class="rounded-md border bg-background px-3 py-2">
                    </label>
                    <label class="grid gap-2 text-sm">
                        Contact Email
                        <input v-model="companyForm.contact_email" type="email" class="rounded-md border bg-background px-3 py-2">
                    </label>
                    <label class="grid gap-2 text-sm">
                        Phone
                        <input v-model="companyForm.phone" class="rounded-md border bg-background px-3 py-2">
                    </label>
                    <label class="grid gap-2 text-sm">
                        Address
                        <input v-model="companyForm.address" class="rounded-md border bg-background px-3 py-2">
                    </label>
                    <label class="grid gap-2 text-sm">
                        Company Admin Name
                        <input v-model="companyForm.admin_name" required class="rounded-md border bg-background px-3 py-2">
                    </label>
                    <label class="grid gap-2 text-sm">
                        Company Admin Email
                        <input v-model="companyForm.admin_email" required type="email" class="rounded-md border bg-background px-3 py-2">
                    </label>
                    <label class="grid gap-2 text-sm md:col-span-2">
                        Temporary Password
                        <span class="relative">
                            <input
                                v-model="companyForm.admin_password"
                                required
                                :type="showCompanyAdminPassword ? 'text' : 'password'"
                                minlength="8"
                                class="w-full rounded-md border bg-background px-3 py-2 pr-11"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-muted-foreground hover:text-foreground"
                                :aria-label="showCompanyAdminPassword ? 'Hide password' : 'Show password'"
                                @click="showCompanyAdminPassword = !showCompanyAdminPassword"
                            >
                                <EyeOff v-if="showCompanyAdminPassword" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </span>
                    </label>
                </div>
                <button type="submit" :disabled="companyForm.processing" class="mt-5 rounded-lg bg-blue-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">
                    {{ companyForm.processing ? 'Creating...' : 'Create Company' }}
                </button>
            </form>

            <form class="rounded-xl border bg-card p-6 shadow-sm" @submit.prevent="createCompanyUser">
                <div class="mb-5 flex items-center gap-3">
                    <Users class="size-5 text-blue-800" />
                    <h2 class="text-lg font-semibold">Add User to Existing Company</h2>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="grid gap-2 text-sm md:col-span-2">
                        Company
                        <select v-model="userForm.company_id" required class="rounded-md border bg-background px-3 py-2">
                            <option value="" disabled>Select company</option>
                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                {{ company.name }}
                            </option>
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm">
                        Name
                        <input v-model="userForm.name" required class="rounded-md border bg-background px-3 py-2">
                    </label>
                    <label class="grid gap-2 text-sm">
                        Email
                        <input v-model="userForm.email" required type="email" class="rounded-md border bg-background px-3 py-2">
                    </label>
                    <label class="grid gap-2 text-sm">
                        Role
                        <select v-model="userForm.role" required class="rounded-md border bg-background px-3 py-2">
                            <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm">
                        Temporary Password
                        <span class="relative">
                            <input
                                v-model="userForm.password"
                                required
                                :type="showCompanyUserPassword ? 'text' : 'password'"
                                minlength="8"
                                class="w-full rounded-md border bg-background px-3 py-2 pr-11"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-muted-foreground hover:text-foreground"
                                :aria-label="showCompanyUserPassword ? 'Hide password' : 'Show password'"
                                @click="showCompanyUserPassword = !showCompanyUserPassword"
                            >
                                <EyeOff v-if="showCompanyUserPassword" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </span>
                    </label>
                </div>
                <button type="submit" :disabled="userForm.processing" class="mt-5 rounded-lg bg-blue-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">
                    {{ userForm.processing ? 'Adding...' : 'Add User' }}
                </button>
            </form>
        </section>

        <section class="rounded-xl border bg-card shadow-sm">
            <div class="border-b p-5">
                <h2 class="text-lg font-semibold">Company Workspaces</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Company</th>
                            <th class="px-5 py-3 font-semibold">Contact</th>
                            <th class="px-5 py-3 font-semibold">Users</th>
                            <th class="px-5 py-3 font-semibold">Batches</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="company in props.companies" :key="company.id" class="border-t">
                            <td class="px-5 py-3 font-medium">{{ company.name }}</td>
                            <td class="px-5 py-3 text-muted-foreground">{{ company.contact_email ?? 'N/A' }}</td>
                            <td class="px-5 py-3">{{ company.users_count }}</td>
                            <td class="px-5 py-3">{{ company.milk_batches_count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
