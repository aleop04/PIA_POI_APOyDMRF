<script setup lang="ts">
const props = defineProps<{
    seconds: number;
    maxSeconds: number;
}>();

const emit = defineEmits<{
    (e: 'cancel'): void;
    (e: 'send'): void;
}>();

function formatTime(value: number) {
    const minutes = Math.floor(value / 60);
    const seconds = value % 60;

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function progressWidth() {
    return `${Math.min((props.seconds / props.maxSeconds) * 100, 100)}%`;
}
</script>

<template>
    <div class="flex h-[69px] w-full items-center gap-[18px] px-[27px]">
        <!-- Cancelar -->
        <button
            type="button"
            class="shrink-0 cursor-pointer transition hover:scale-105 hover:opacity-80"
            @click="emit('cancel')"
        >
            <img
                src="/icons/X circle green.svg"
                alt="Cancelar audio"
                class="h-[37px] w-[37px]"
            />
        </button>

        <!-- Barra de grabación -->
        <div class="relative h-[38px] flex-1 overflow-hidden rounded-[32px] bg-[#00BF63]">
            <!-- Progreso -->
            <div
                class="absolute left-0 top-0 h-full rounded-l-[32px] bg-[#009A50] transition-all duration-300"
                :style="{ width: progressWidth() }"
            />

            <!-- Tiempo -->
            <div class="absolute right-[14px] top-1/2 -translate-y-1/2 font-['Nunito_Sans'] text-[20px] font-bold text-[#FFDC51]">
                {{ formatTime(seconds) }}
            </div>
        </div>

        <!-- Enviar audio -->
        <button
            type="button"
            class="shrink-0 cursor-pointer transition hover:scale-105 hover:opacity-80"
            @click="emit('send')"
        >
            <img
                src="/icons/Send.svg"
                alt="Enviar audio"
                class="h-[37px] w-[37px]"
            />
        </button>
    </div>
</template>