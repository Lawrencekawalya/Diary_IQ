<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { logout } from '@/routes';

defineOptions({
    layout: {
        title: 'Change your password',
        description: 'Your administrator reset your password. Set a new password before continuing.',
    },
});

const form = useForm({
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put('/force-password-change', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Change Password" />

    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="password">New password</Label>
            <Input id="password" v-model="form.password" type="password" required autofocus autocomplete="new-password" />
            <InputError :message="form.errors.password" />
        </div>

        <div class="grid gap-2">
            <Label for="password_confirmation">Confirm password</Label>
            <Input id="password_confirmation" v-model="form.password_confirmation" type="password" required autocomplete="new-password" />
            <InputError :message="form.errors.password_confirmation" />
        </div>

        <Button type="submit" class="w-full" :disabled="form.processing">
            {{ form.processing ? 'Saving...' : 'Save new password' }}
        </Button>

        <TextLink :href="logout()" method="post" as="button" class="mx-auto block text-sm">
            Log out
        </TextLink>
    </form>
</template>
