<script setup lang="ts">
import AppNavbar from '@/components/Destinarionavbar.vue';
import AppFooter from '@/components/Destinariofooter.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <div class="min-h-screen flex flex-col bg-[#FFF1D9]">
        <AppNavbar />

        <main class="flex-1 pt-[125px] relative">
            <slot />
        </main>

        <AppFooter />
    </div>
</template>
