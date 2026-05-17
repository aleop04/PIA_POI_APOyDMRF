<script setup lang="ts">

import UserAvatar from '@/components/UserAvatar.vue';

type CallType = 'voice' | 'video';
type IncomingCallMode = 'private' | 'group';

defineProps<{
    mode: IncomingCallMode;
    photo: string | null;
    title: string;
    callType: CallType;
}>();

const emit = defineEmits<{
    (e: 'accept'): void;
    (e: 'reject'): void;
}>();

</script>

<template>

    <div class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/70">
        <div class="flex w-[320px] flex-col items-center rounded-[24px] bg-white px-8 py-8">
            <UserAvatar
                :photo="photo"
                className="h-[90px] w-[90px]"
                :alt="title"
            />

            <h2 class="mt-5 text-center text-[22px] font-bold text-[#442F2F]">
                {{ title }}
            </h2>

            <p class="mt-2 text-[15px] text-[#604646]">
                {{
                    mode === 'group'
                        ? 'Llamada grupal entrante...'
                        : callType === 'video'
                            ? 'Videollamada entrante...'
                            : 'Llamada entrante...'
                }}
            </p>

            <div class="mt-8 flex gap-6">
                <button
                    type="button"
                    class="rounded-full bg-red-500 px-6 py-3 font-bold text-white"
                    @click="emit('reject')"
                >
                    Rechazar
                </button>

                <button
                    type="button"
                    class="rounded-full bg-green-500 px-6 py-3 font-bold text-white"
                    @click="emit('accept')"
                >
                    Aceptar
                </button>
            </div>
        </div>
    </div>
    
</template>