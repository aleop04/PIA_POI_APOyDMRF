<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';

const emit = defineEmits<{
    (e: 'create-group'): void;
    (e: 'send-message'): void;
}>();

const showMenu = ref(false);

function openCreateGroup() {
    emit('create-group');
    showMenu.value = false;
}

function openSendMessage() {
    emit('send-message');
    showMenu.value = false;
}

const menuRef = ref<HTMLElement | null>(null);

function handleClickOutside(event: MouseEvent) {
    if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
        showMenu.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="menuRef" class="relative">
        <button
            type="button"
            class="flex h-12 w-12 items-center justify-center cursor-pointer transition hover:scale-105 hover:opacity-80"
            @click="showMenu = !showMenu"
        >
            <img
                src="/icons/Edit.svg"
                alt="Opciones de chat"
                class="h-12 w-12"
            />
        </button>

        <div
            v-if="showMenu"
            class="absolute right-0 top-[58px] z-20 flex h-[90px] w-[165px] flex-col items-start gap-[10px] rounded-[11px] bg-[#FFEDA5] px-4 py-[15px] transition-all duration-200"
        >
            <button
                type="button"
                class="h-6 w-full text-left font-['Nunito_Sans'] text-[15px] font-bold text-black"
                @click="openCreateGroup"
            >
                Crear grupo
            </button>

            <button
                type="button"
                class="h-6 w-full text-left font-['Nunito_Sans'] text-[15px] font-bold text-black"
                @click="openSendMessage"
            >
                Enviar mensaje a...
            </button>
        </div>
    </div>
</template>