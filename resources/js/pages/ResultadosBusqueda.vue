<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import CardPublicacion from '@/components/Posts/CardPublicacion.vue'

defineProps<{
    q?: string
    resultados: any[]
}>()
</script>

<template>
    <Head title="Resultados de Búsqueda" />

    <section class="min-h-screen bg-[#FFF7EA] p-8">

        <div class="mx-auto w-full max-w-7xl">

            <div class="mb-6">
                <h1 class="text-[28px] font-bold text-[#442F2F]">
                    Resultados de búsqueda
                </h1>

                <p
                    v-if="q"
                    class="mt-1 text-sm text-gray-500"
                >
                    Resultados para:
                    <span class="font-semibold text-[#FF7608]">
                        "{{ q }}"
                    </span>
                </p>
            </div>

            <!-- SI HAY RESULTADOS -->
            <div
                v-if="resultados.length > 0"
                class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <CardPublicacion
                    v-for="post in resultados"
                    :key="post.id"
                    :post="post"
                />
            </div>

            <!-- SI NO HAY RESULTADOS -->
            <div
                v-else
                class="mx-auto mt-20 flex w-full max-w-[409px] flex-col items-center gap-[34px]"
            >
                <img
                    src="/images/nofound.png"
                    class="h-[228px] w-[277px] object-contain"
                />

                <p class="text-center text-[20px] font-bold text-[#B4C5BD]">
                    ¡Oh! Por el momento no hay resultados para tu búsqueda
                </p>
            </div>

        </div>

    </section>
</template>