<script setup lang="ts">
defineProps<{
    title: string;
    imageSrc?: string;
    logoSrc?: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-[#FFF1D9] overflow-hidden">
        <div class="relative flex min-h-screen">
            <!-- lado izquierdo -->
            <div class="hidden md:block md:w-[49%] relative">
                <img
                    v-if="imageSrc"
                    :src="imageSrc"
                    alt="Imagen lateral"
                    class="absolute inset-0 h-full w-full object-contain scale-179"
                />
            </div>

            <!-- lado derecho -->
            <div
                class="relative flex ml-auto w-full md:w-[40%] items-center justify-center bg-white md:rounded-l-[91px] px-8 py-12 md:px-14"
            >
                <div class="w-full max-w-[530px]">
                    <div class="mb-14 flex flex-col items-center gap-4 text-center">
                        <img
                            v-if="logoSrc"
                            :src="logoSrc"
                            alt="Logo"
                            class="h-[121px] w-[121px] object-contain"
                        />
                        <h1
                            class="text-[30px] md:text-[36px] font-bold text-[#FF7608]"
                            style="font-family: 'Nunito', sans-serif;"
                        >
                            {{ title }}
                        </h1>
                    </div>

                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>