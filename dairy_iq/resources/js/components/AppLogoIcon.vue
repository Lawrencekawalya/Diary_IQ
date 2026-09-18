<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        src="/brand/dairyiq-logo-white.png"
        alt="DairyIQ"
        :class="className"
        v-bind="$attrs"
    >
</template>
