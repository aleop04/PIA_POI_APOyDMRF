<script setup lang="ts">

// socket 
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import GlobalCallManager from '@/components/Calls/GlobalCallManager.vue';
import AppFooter from '@/components/Destinariofooter.vue';
import AppNavbar from '@/components/Destinarionavbar.vue';
import { connectSocket } from '@/lib/socket';
import type { BreadcrumbItem } from '@/types';

const page = usePage();

const socketReady = ref(false);

onMounted(() => {
    const userId = page.props.auth?.user?.id;

    if (userId) {
        connectSocket(userId);
        socketReady.value = true;
    }
});


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

        <main class="relative flex-1 pt-[170px] md:pt-[125px]">
            <slot />
        </main>

        <AppFooter />

        <GlobalCallManager v-if="socketReady" />
    </div>
</template>
