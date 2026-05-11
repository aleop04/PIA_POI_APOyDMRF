<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import UserAvatar from '@/components/UserAvatar.vue';
import axios from 'axios';

const showAttachMenu = ref(false);

function toggleAttachMenu() {
    showAttachMenu.value = !showAttachMenu.value;
}

function handleClickOutside(event: MouseEvent) {
    showAttachMenu.value = false;
}

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

type ChatUser = {
    id: number;
    first_name: string;
    last_name: string;
    username: string;
    last_seen_at: string | null;
    profile_photo: string | null;
};

type Message = {
    id: number;
    conversation_id: number;
    sender_id: number;
    type: string;
    body_encrypted: string | null;
    created_at: string;
    sender?: ChatUser;
};

const props = defineProps<{
    user?: ChatUser | null;
    groupUsers?: ChatUser[];

    conversation: {
        id: number;
        type: 'private' | 'group';
        name: string | null;
        photo: string | null;
    } | null;

    messages: any[];
}>();

const emit = defineEmits<{
    (e: 'toggle-info'): void;
    (e: 'message-sent', message: Message): void;
}>();

const newMessage = ref('');

const messagesContainer = ref<HTMLElement | null>(null);

function scrollToBottom() {
    nextTick(() => {
        if (!messagesContainer.value) return;

        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    });
}

watch(
    () => props.messages.length,
    () => {
        scrollToBottom();
    },
);

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    scrollToBottom();
});

async function sendMessage() {
    if (!newMessage.value.trim() || !props.conversation) return;

    try {
        const res = await axios.post(`/chats/${props.conversation.id}/messages`, {
            body: newMessage.value,
            type: 'text',
        });

        emit('message-sent', res.data.message);

        newMessage.value = '';
    } catch (error: any) {
        console.error(error.response?.data ?? error);
    }
}
</script>

<template>
    <div class="relative h-full w-full">
        <!-- Header -->
        <header class="absolute left-[27px] top-[24px] flex w-[calc(100%-54px)] items-center justify-between">
            <!-- Usuario -->
            <div class="flex items-center gap-3">
                <UserAvatar
                    :photo="
                        conversation?.type === 'group'
                            ? conversation?.photo
                            : user?.profile_photo
                    "
                    className="h-[44px] w-[44px] outline outline-2 outline-[#FFEBC9]"
                />

                <h2 class="font-['Nunito_Sans'] text-[24px] font-bold text-[#442F2F]">
                    {{
                        conversation?.type === 'group'
                            ? conversation.name
                            : user?.username
                    }}
                </h2>
            </div>

            <!-- Acciones -->
            <div class="flex items-center gap-[27px]">
                <button type="button" class="cursor-pointer transition hover:scale-105 hover:opacity-80">
                    <img src="/icons/Phone.svg" alt="Llamada" class="h-[44px] w-[44px]" />
                </button>

                <button type="button" class="cursor-pointer transition hover:scale-105 hover:opacity-80">
                    <img src="/icons/Video.svg" alt="Videollamada" class="h-[44px] w-[44px]" />
                </button>

                <button type="button" class="cursor-pointer transition hover:scale-105 hover:opacity-80" @click="emit('toggle-info')">
                    <img src="/icons/Info.svg" alt="Información del chat" class="h-[44px] w-[44px]" />
                </button>
            </div>
        </header>

        <!-- Línea superior -->
        <div class="absolute top-[86px] h-[1px] w-full bg-[#FF7608]" />

        <section
            ref="messagesContainer"
            class="chat-scroll invisible-scrollbar absolute left-[27px] top-[114px] flex max-h-[570px] w-[calc(100%-54px)] flex-col gap-[20px] overflow-y-auto pb-[20px]"
        >
            <div
                v-for="msg in messages"
                :key="msg.id"
                class="flex w-full"
                :class="msg.sender_id === $page.props.auth.user.id ? 'justify-end' : 'justify-start'"
            >
                <!-- MENSAJE PROPIO -->
                <div
                    v-if="msg.sender_id === $page.props.auth.user.id"
                    class="max-w-[541px] whitespace-pre-wrap break-words rounded-[32px] bg-[#604646] px-5 py-2 text-white"
                >
                    {{ msg.body }}
                </div>

                <!-- MENSAJE RECIBIDO -->
                <div
                    v-else
                    class="flex items-end gap-[15px]"
                >
                    <UserAvatar
                        :photo="msg.sender?.profile_photo"
                        className="h-[44px] w-[44px] shrink-0"
                    />

                    <div
                        class="max-w-[541px] whitespace-pre-wrap break-words rounded-[32px] bg-[#FF7608] px-5 py-2 text-[#442F2F]"
                    >
                        {{ msg.body }}
                    </div>
                </div>
            </div>
        </section>

        <!-- Input inferior -->
        <footer class="absolute bottom-0 left-0 flex h-[69px] w-full items-center gap-[18px] px-[27px]">
            <div class="relative">
                <button
                    type="button"
                    @click.stop="toggleAttachMenu"
                    class="cursor-pointer transition hover:scale-105 hover:opacity-80"
                >
                    <img src="/icons/Plus circle.svg" class="h-[37px] w-[37px]" />
                </button>

                <!-- MENÚ -->
                <div
                    v-if="showAttachMenu"
                    class="absolute bottom-[50px] left-0 z-50 flex w-[202px] flex-col gap-[10px] rounded-[11px] bg-[#FFEDA5] px-[16px] py-[15px] shadow-[0px_1px_4px_rgba(0,0,0,0.25)]"
                >
                <!-- Archivo -->
                    <button class="flex items-center gap-[16px] text-left">
                        <img src="/icons/File plus.svg" class="h-[32px] w-[32px]" />
                        <span class="text-[15px] font-bold text-[#442F2F]">
                            Adjuntar archivos
                        </span>
                    </button>

                    <!-- Imagen -->
                    <button class="flex items-center gap-[16px] text-left">
                        <img src="/icons/Image2.svg" class="h-[32px] w-[32px]" />
                        <span class="text-[15px] font-bold text-[#442F2F]">
                            Enviar imágenes
                        </span>
                    </button>

                    <!-- Audio -->
                    <button class="flex items-center gap-[16px] text-left">
                        <img src="/icons/Mic.svg" class="h-[32px] w-[32px]" />
                        <span class="text-[15px] font-bold text-[#442F2F]">
                            Grabar audio
                        </span>
                    </button>

                    <!-- Ubicación -->
                    <button class="flex items-center gap-[16px] text-left">
                        <img src="/icons/Map pin.svg" class="h-[32px] w-[32px]" />
                        <span class="text-[15px] font-bold text-[#442F2F]">
                            Enviar ubicación
                        </span>
                    </button>
                </div>
            </div>

            <input
                v-model="newMessage"
                @keyup.enter="sendMessage"
                type="text"
                placeholder="Escribe un mensaje..."
                class="h-[38px] flex-1 rounded-[32px] bg-[#FFEDA5] px-5 font-['Nunito_Sans'] text-[16px] font-bold text-[#442F2F] outline-none placeholder:text-[#B4C5BD]"
            />

            <button type="button" @click="sendMessage" class="cursor-pointer transition hover:scale-105 hover:opacity-80">
                <img src="/icons/Send.svg" alt="Enviar mensaje" class="h-[37px] w-[37px]" />
            </button>
        </footer>
    </div>
</template>

<style scoped>
.invisible-scrollbar {
    scrollbar-width: none;
}

.invisible-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>