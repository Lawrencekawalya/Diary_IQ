<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    company: {
        name: string | null;
        contact_email: string | null;
        phone: string | null;
        address: string | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Company Settings', href: '/company/settings' },
        ],
    },
});

const form = useForm({
    name: props.company.name ?? '',
    contact_email: props.company.contact_email ?? '',
    phone: props.company.phone ?? '',
    address: props.company.address ?? '',
});

const submit = () => {
    form.put('/company/settings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Company Settings" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <section class="rounded-xl border bg-card p-6 shadow-sm">
            <h1 class="text-2xl font-bold">Company profile</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                These details identify the company that owns prediction records and reports.
            </p>
        </section>

        <form class="rounded-xl border bg-card p-6 shadow-sm" @submit.prevent="submit">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-2 text-sm">
                    Company Name
                    <input v-model="form.name" class="rounded-md border bg-background px-3 py-2">
                    <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
                </label>
                <label class="grid gap-2 text-sm">
                    Contact Email
                    <input v-model="form.contact_email" type="email" class="rounded-md border bg-background px-3 py-2">
                    <span v-if="form.errors.contact_email" class="text-xs text-red-600">{{ form.errors.contact_email }}</span>
                </label>
                <label class="grid gap-2 text-sm">
                    Phone
                    <input v-model="form.phone" class="rounded-md border bg-background px-3 py-2">
                </label>
                <label class="grid gap-2 text-sm">
                    Address
                    <input v-model="form.address" class="rounded-md border bg-background px-3 py-2">
                </label>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-800 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60">
                    {{ form.processing ? 'Saving...' : 'Save Company Profile' }}
                </button>
            </div>
        </form>
    </div>
</template>
