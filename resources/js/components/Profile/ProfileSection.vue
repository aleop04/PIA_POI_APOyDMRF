<script setup lang="ts">
defineProps<{
    title: string;
    imageHeight?: string;
    imageWidth?: string;
    hasContent?: boolean;
}>();
</script>

<template>
    <section class="flex w-full flex-col items-center gap-[40px]">
        <div class="flex w-full flex-col gap-[25px]">
            <h2 class="text-[36px] font-bold text-[#FF7608]">
                {{ title }}
            </h2>

            <div class="h-px w-full bg-[#FF7608]"></div>
        </div>

        <div v-if="hasContent" class="w-full">
            <slot />
        </div>

        <div v-else class="flex w-[320px] flex-col items-center gap-[12px]">
            <img
                src="/images/nofound2.png"
                :class="[imageHeight ?? 'h-[250px]', imageWidth ?? 'w-[240px]']"
            />

            <p class="text-center text-[22px] font-bold text-[#B4C5BD]">
                ¡No hay información que mostrar!
            </p>
        </div>
    </section>
</template>