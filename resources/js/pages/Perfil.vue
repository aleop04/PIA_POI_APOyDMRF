<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

const logout = async () => {
    await fetch('/logout', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '',
            'Accept': 'application/json',
        },
    });

    window.location.href = '/login';
};
</script>

<template>
    <Head title="Perfil" />

    <section class="p-8 relative">
        <!-- contenido -->
         <button
            @click="logout"
            class="absolute top-4 right-4 z-50 text-red-500 text-xl font-bold"
        >
            ✕
        </button>
    </section>
</template>