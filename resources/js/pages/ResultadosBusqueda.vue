<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    q?: string;
    resultados: any[];
}>();

</script>

<template>
    <Head title="Resultados de Búsqueda" />

    <section class="p-8">
        <!-- Si hay resultados -->
        <div v-if="resultados.length > 0">
            <div v-for="item in resultados" :key="item.id">
                <!-- aquí luego pondrás tus cards -->
                {{ item.titulo }}
            </div>
        </div>

        <!-- Si NO hay resultados -->
        <div
            v-else
            class="mx-auto mt-20 flex w-[409px] flex-col items-center gap-[34px]"
        >
            <img
                src="/images/nofound.png"
                class="h-[228px] w-[277px] object-contain"
            />

            <p class="text-center text-[20px] font-bold text-[#B4C5BD]">
                ¡Oh! Por el momento no hay resultados para tu búsqueda
            </p>
        </div>
    </section>
</template>