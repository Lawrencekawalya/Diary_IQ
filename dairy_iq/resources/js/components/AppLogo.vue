<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-10 items-center justify-center rounded-md bg-[#1f3a93] p-1.5"
    >
        <AppLogoIcon class-name="size-full object-contain" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold text-[#1f3a93] dark:text-white">DairyIQ</span>
        <span class="truncate text-xs text-muted-foreground">Milk Quality Analytics</span>
    </div>
</template>
