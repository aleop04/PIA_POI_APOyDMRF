<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
}>();
</script>

<template>
    <Link
        :href="href"
        class="text-inherit no-underline hover:underline transition"
    >
        <slot />
    </Link>
</template>