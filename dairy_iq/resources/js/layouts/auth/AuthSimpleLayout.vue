<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-cover bg-center bg-no-repeat p-6 md:p-10"
        style="background-image: url('/brand/login-bg.webp')"
    >
        <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-[1px]" />

        <div class="relative z-10 w-full max-w-sm rounded-2xl border border-white/25 bg-white/90 p-8 shadow-2xl shadow-slate-950/30 backdrop-blur-md dark:bg-slate-950/85">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-14 w-14 items-center justify-center rounded-xl bg-[#1f3a93] p-2 shadow-lg shadow-blue-900/30"
                        >
                            <AppLogoIcon
                                class-name="size-full object-contain"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-semibold text-[#1f3a93] dark:text-white">{{ title }}</h1>
                        <p class="text-center text-sm text-slate-600 dark:text-slate-300">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
