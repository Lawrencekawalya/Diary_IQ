<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Building2, Eye, EyeOff, Users } from '@lucide/vue';
import { computed, ref } from 'vue';

type Company = {
    id: number;
    name: string;
    contact_email: string | null;
    phone: string | null;
    address: string | null;
    archived_at: string | null;
    users_count: number;
    milk_batches_count: number;
};

type AdminUser = {
    id: number;
    name: string;
    email: string;
    role: string;
    company_id: number | null;
    company: string | null;
    is_active: boolean;
    force_password_change: boolean;
    milk_batches_count: number;
    created_at: string | null;
};

type AuditLog = {
    id: number;
    actor: string;
    action: string;
    metadata: Record<string, unknown> | null;
    created_at: string | null;
};

type Paginator<T> = {
    data: T[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

const props = defineProps<{
    companies: Paginator<Company>;
    roles: string[];
    platformRoles: string[];
    users: Paginator<AdminUser>;
    auditLogs: AuditLog[];
    filters: Record<string, string | null>;
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

const platformUserForm = useForm({
    company_id: '',
    name: '',
    email: '',
    password: '',
    role: 'super_admin',
});

const showCompanyAdminPassword = ref(false);
const showCompanyUserPassword = ref(false);
const showPlatformUserPassword = ref(false);
const platformUserRequiresCompany = computed(() => platformUserForm.role !== 'super_admin');
const companyRows = computed(() => props.companies.data);
const userRows = computed(() => props.users.data);

const companyFilters = useForm({
    company_search: props.filters.company_search ?? '',
    company_status: props.filters.company_status ?? '',
});

const userFilters = useForm({
    user_search: props.filters.user_search ?? '',
    user_role: props.filters.user_role ?? '',
});

const companyOptions = computed(() => companyRows.value.filter((company) => !company.archived_at));

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

const createPlatformUser = () => {
    if (platformUserRequiresCompany.value && !platformUserForm.company_id) {
        return;
    }

    platformUserForm.post('/admin/users', {
        preserveScroll: true,
        onSuccess: () => platformUserForm.reset('company_id', 'name', 'email', 'password'),
    });
};

const applyCompanyFilters = () => {
    router.get('/admin/companies', companyFilters.data(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const applyUserFilters = () => {
    router.get('/admin/companies', userFilters.data(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const editCompany = (company: Company) => {
    const name = window.prompt('Company name', company.name);

    if (!name) {
        return;
    }

    router.put(`/admin/companies/${company.id}`, {
        name,
        contact_email: window.prompt('Contact email', company.contact_email ?? '') || null,
        phone: window.prompt('Phone', company.phone ?? '') || null,
        address: window.prompt('Address', company.address ?? '') || null,
    }, { preserveScroll: true });
};

const archiveCompany = (company: Company) => {
    if (window.confirm(`Archive ${company.name}? This will deactivate its users.`)) {
        router.put(`/admin/companies/${company.id}/archive`, {}, { preserveScroll: true });
    }
};

const restoreCompany = (company: Company) => {
    router.put(`/admin/companies/${company.id}/restore`, {}, { preserveScroll: true });
};

const editUser = (user: AdminUser) => {
    const role = window.prompt('Role: super_admin, company_admin, tester', user.role);

    if (!role || !props.platformRoles.includes(role)) {
        return;
    }

    const companyId = role === 'super_admin'
        ? null
        : Number(window.prompt('Company ID', String(user.company_id ?? '')));

    router.put(`/admin/users/${user.id}`, {
        name: window.prompt('Name', user.name) || user.name,
        email: window.prompt('Email', user.email) || user.email,
        role,
        company_id: companyId,
    }, { preserveScroll: true });
};

const resetPassword = (user: AdminUser) => {
    const password = window.prompt(`New temporary password for ${user.email}`);

    if (password) {
        router.put(`/admin/users/${user.id}/password`, { password }, { preserveScroll: true });
    }
};

const deactivateUser = (user: AdminUser) => {
    if (window.confirm(`Deactivate ${user.email}?`)) {
        router.put(`/admin/users/${user.id}/deactivate`, {}, { preserveScroll: true });
    }
};

const activateUser = (user: AdminUser) => {
    router.put(`/admin/users/${user.id}/activate`, {}, { preserveScroll: true });
};

const deleteUser = (user: AdminUser) => {
    if (window.confirm(`Delete ${user.email}? Users with prediction records should be deactivated instead.`)) {
        router.delete(`/admin/users/${user.id}`, { preserveScroll: true });
    }
};

const paginationLabel = (label: string) => label
    .replace('&laquo;', 'Previous')
    .replace('&raquo;', 'Next');

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

        <section class="grid gap-6 xl:grid-cols-3">
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

            <form class="rounded-xl border bg-card p-6 shadow-sm" @submit.prevent="createPlatformUser">
                <div class="mb-5 flex items-center gap-3">
                    <Users class="size-5 text-blue-800" />
                    <div>
                        <h2 class="text-lg font-semibold">Create Platform User</h2>
                        <p class="text-sm text-muted-foreground">Use this form to create super admins or company users.</p>
                    </div>
                </div>
                <div class="grid gap-4">
                    <label class="grid gap-2 text-sm">
                        Role
                        <select v-model="platformUserForm.role" required class="rounded-md border bg-background px-3 py-2">
                            <option v-for="role in platformRoles" :key="role" :value="role">{{ role }}</option>
                        </select>
                    </label>
                    <label v-if="platformUserRequiresCompany" class="grid gap-2 text-sm">
                        Company
                        <select v-model="platformUserForm.company_id" required class="rounded-md border bg-background px-3 py-2">
                            <option value="" disabled>Select company</option>
                            <option v-for="company in companyOptions" :key="company.id" :value="company.id">
                                {{ company.name }}
                            </option>
                        </select>
                    </label>
                    <div v-else class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-900">
                        Super admins are platform users and are not attached to one company.
                    </div>
                    <label class="grid gap-2 text-sm">
                        Name
                        <input v-model="platformUserForm.name" required class="rounded-md border bg-background px-3 py-2">
                    </label>
                    <label class="grid gap-2 text-sm">
                        Email
                        <input v-model="platformUserForm.email" required type="email" class="rounded-md border bg-background px-3 py-2">
                    </label>
                    <label class="grid gap-2 text-sm">
                        Temporary Password
                        <span class="relative">
                            <input
                                v-model="platformUserForm.password"
                                required
                                :type="showPlatformUserPassword ? 'text' : 'password'"
                                minlength="8"
                                class="w-full rounded-md border bg-background px-3 py-2 pr-11"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-muted-foreground hover:text-foreground"
                                :aria-label="showPlatformUserPassword ? 'Hide password' : 'Show password'"
                                @click="showPlatformUserPassword = !showPlatformUserPassword"
                            >
                                <EyeOff v-if="showPlatformUserPassword" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </span>
                    </label>
                </div>
                <button type="submit" :disabled="platformUserForm.processing" class="mt-5 rounded-lg bg-blue-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">
                    {{ platformUserForm.processing ? 'Creating...' : 'Create User' }}
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
                            <option v-for="company in companyOptions" :key="company.id" :value="company.id">
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
                <h2 class="text-lg font-semibold">Platform Users</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Super admins are platform-wide. Company admins and testers are attached to one company.
                </p>
                <form class="mt-4 flex flex-wrap gap-3" @submit.prevent="applyUserFilters">
                    <input v-model="userFilters.user_search" placeholder="Search users" class="rounded-md border bg-background px-3 py-2 text-sm">
                    <select v-model="userFilters.user_role" class="rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="">All roles</option>
                        <option v-for="role in platformRoles" :key="role" :value="role">{{ role }}</option>
                    </select>
                    <button type="submit" class="rounded-md bg-blue-800 px-4 py-2 text-sm font-semibold text-white">Filter</button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Name</th>
                            <th class="px-5 py-3 font-semibold">Email</th>
                            <th class="px-5 py-3 font-semibold">Role</th>
                            <th class="px-5 py-3 font-semibold">Company</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Records</th>
                            <th class="px-5 py-3 font-semibold">Created</th>
                            <th class="px-5 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="userRows.length === 0">
                            <td colspan="8" class="px-5 py-10 text-center text-muted-foreground">
                                No users have been created yet.
                            </td>
                        </tr>
                        <tr v-for="user in userRows" :key="user.id" class="border-t">
                            <td class="px-5 py-3 font-medium">{{ user.name }}</td>
                            <td class="px-5 py-3 text-muted-foreground">{{ user.email }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">
                                    {{ user.role }}
                                </span>
                            </td>
                            <td class="px-5 py-3">{{ user.company ?? 'Platform-wide' }}</td>
                            <td class="px-5 py-3">
                                <span :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="rounded-full px-2.5 py-1 text-xs font-semibold">
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span v-if="user.force_password_change" class="ml-2 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                    Must change password
                                </span>
                            </td>
                            <td class="px-5 py-3">{{ user.milk_batches_count }}</td>
                            <td class="px-5 py-3 text-muted-foreground">{{ user.created_at ?? 'N/A' }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button type="button" class="font-semibold text-blue-700 hover:underline" @click="editUser(user)">Edit</button>
                                    <button type="button" class="font-semibold text-blue-700 hover:underline" @click="resetPassword(user)">Reset Password</button>
                                    <button v-if="user.is_active" type="button" class="font-semibold text-amber-700 hover:underline" @click="deactivateUser(user)">Deactivate</button>
                                    <button v-else type="button" class="font-semibold text-green-700 hover:underline" @click="activateUser(user)">Activate</button>
                                    <button type="button" class="font-semibold text-red-700 hover:underline" @click="deleteUser(user)">Delete</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap gap-2 border-t p-4">
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    class="rounded-md border px-3 py-1 text-sm"
                    :class="{ 'bg-blue-800 text-white': link.active, 'pointer-events-none opacity-40': !link.url }"
                >
                    {{ paginationLabel(link.label) }}
                </Link>
            </div>
        </section>

        <section class="rounded-xl border bg-card shadow-sm">
            <div class="border-b p-5">
                <h2 class="text-lg font-semibold">Company Workspaces</h2>
                <form class="mt-4 flex flex-wrap gap-3" @submit.prevent="applyCompanyFilters">
                    <input v-model="companyFilters.company_search" placeholder="Search companies" class="rounded-md border bg-background px-3 py-2 text-sm">
                    <select v-model="companyFilters.company_status" class="rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="archived">Archived</option>
                    </select>
                    <button type="submit" class="rounded-md bg-blue-800 px-4 py-2 text-sm font-semibold text-white">Filter</button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Company</th>
                            <th class="px-5 py-3 font-semibold">Contact</th>
                            <th class="px-5 py-3 font-semibold">Users</th>
                            <th class="px-5 py-3 font-semibold">Batches</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="company in companyRows" :key="company.id" class="border-t">
                            <td class="px-5 py-3 font-medium">{{ company.name }}</td>
                            <td class="px-5 py-3 text-muted-foreground">{{ company.contact_email ?? 'N/A' }}</td>
                            <td class="px-5 py-3">{{ company.users_count }}</td>
                            <td class="px-5 py-3">{{ company.milk_batches_count }}</td>
                            <td class="px-5 py-3">
                                <span :class="company.archived_at ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'" class="rounded-full px-2.5 py-1 text-xs font-semibold">
                                    {{ company.archived_at ? 'Archived' : 'Active' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <Link :href="`/admin/companies/${company.id}/dashboard`" class="font-semibold text-blue-700 hover:underline">Dashboard</Link>
                                    <button type="button" class="font-semibold text-blue-700 hover:underline" @click="editCompany(company)">Edit</button>
                                    <button v-if="!company.archived_at" type="button" class="font-semibold text-amber-700 hover:underline" @click="archiveCompany(company)">Archive</button>
                                    <button v-else type="button" class="font-semibold text-green-700 hover:underline" @click="restoreCompany(company)">Restore</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap gap-2 border-t p-4">
                <Link
                    v-for="link in companies.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    class="rounded-md border px-3 py-1 text-sm"
                    :class="{ 'bg-blue-800 text-white': link.active, 'pointer-events-none opacity-40': !link.url }"
                >
                    {{ paginationLabel(link.label) }}
                </Link>
            </div>
        </section>

        <section class="rounded-xl border bg-card shadow-sm">
            <div class="border-b p-5">
                <h2 class="text-lg font-semibold">Recent Audit Log</h2>
                <p class="mt-1 text-sm text-muted-foreground">Latest super-admin actions captured by the system.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Actor</th>
                            <th class="px-5 py-3 font-semibold">Action</th>
                            <th class="px-5 py-3 font-semibold">Metadata</th>
                            <th class="px-5 py-3 font-semibold">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="auditLogs.length === 0">
                            <td colspan="4" class="px-5 py-10 text-center text-muted-foreground">No audit activity yet.</td>
                        </tr>
                        <tr v-for="log in auditLogs" :key="log.id" class="border-t">
                            <td class="px-5 py-3 font-medium">{{ log.actor }}</td>
                            <td class="px-5 py-3">{{ log.action }}</td>
                            <td class="px-5 py-3 text-muted-foreground">{{ JSON.stringify(log.metadata ?? {}) }}</td>
                            <td class="px-5 py-3 text-muted-foreground">{{ log.created_at ?? 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
