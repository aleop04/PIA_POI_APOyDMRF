<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import UserAvatar from '@/components/UserAvatar.vue';
import AudioRecordingBar from './AudioRecordingBar.vue';

const showAttachMenu = ref(false);

const imageInput = ref<HTMLInputElement | null>(null);

const fileInput = ref<HTMLInputElement | null>(null);

const selectedImageUrl = ref<string | null>(null);

const isRecordingAudio = ref(false);
const mediaRecorder = ref<MediaRecorder | null>(null);
const audioChunks = ref<Blob[]>([]);
const recordingSeconds = ref(0);
const recordingInterval = ref<number | null>(null);
const maxRecordingSeconds = 60;

function toggleAttachMenu() {
    showAttachMenu.value = !showAttachMenu.value;
}

function handleClickOutside() {
    showAttachMenu.value = false;
}

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);

    if (recordingInterval.value) {
        clearInterval(recordingInterval.value);
    }

    if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
        mediaRecorder.value.stop();
    }
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

    attachments?: {
        id: number;
        file_path: string;
        original_name: string | null;
        mime_type: string;
        size: number;
        url: string;
    }[];
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
    hasActiveGroupCall?: boolean;
}>();

const emit = defineEmits<{
    (e: 'toggle-info'): void;
    (e: 'message-sent', message: Message): void;
    (e: 'back-to-list'): void;
}>();

// para llamadas 1 a 1

const page = usePage();

type AuthUser = {
    id: number;
    username?: string;
    first_name?: string;
    last_name?: string;
    profile_photo?: string | null;
};

const authUser = computed(() => page.props.auth.user as unknown as AuthUser);

const isSelfPrivateChat = computed(() => {
    return (
        props.conversation?.type === 'private' &&
        props.user &&
        Number(props.user.id) === Number(authUser.value.id)
    );
});

function startVoiceCall() {
    if (!props.conversation) {
        return;
    }

    if (props.conversation.type === 'private') {
        if (!props.user) {
            return;
        }

        window.dispatchEvent(
            new CustomEvent('destinario:start-call', {
                detail: {
                    toUser: props.user,
                    fromUser: authUser.value,
                    conversationId: props.conversation.id,
                    callType: 'voice',
                    mode: 'private',
                },
            }),
        );

        return;
    }

    if (props.conversation.type === 'group') {
        window.dispatchEvent(
            new CustomEvent('destinario:start-group-call', {
                detail: {
                    fromUser: authUser.value,
                    conversationId: props.conversation.id,
                    callType: 'voice',
                    members: props.groupUsers ?? [],
                    group: {
                        id: props.conversation.id,
                        name: props.conversation.name,
                        photo: props.conversation.photo,
                    },
                },
            }),
        );
    }
}

function joinActiveGroupCall() {
    if (!props.conversation || props.conversation.type !== 'group') {
        return;
    }

    window.dispatchEvent(
        new CustomEvent('destinario:join-active-group-call', {
            detail: {
                conversationId: props.conversation.id,
                members: props.groupUsers ?? [],
                fromUser: authUser.value,
                group: {
                    id: props.conversation.id,
                    name: props.conversation.name,
                    photo: props.conversation.photo,
                },
            },
        }),
    );
}

//

// para llamada de video 1 a 1

function startVideoCall() {
    if (!props.conversation || props.conversation.type !== 'private' || !props.user) {
        return;
    }

    window.dispatchEvent(
        new CustomEvent('destinario:start-call', {
            detail: {
                toUser: props.user,
                fromUser: authUser.value,
                conversationId: props.conversation.id,
                callType: 'video',
            },
        }),
    );

    console.log('Videollamando a:', props.user.username);
}

const newMessage = ref('');

const messagesContainer = ref<HTMLElement | null>(null);

function scrollToBottom() {
    nextTick(() => {
        requestAnimationFrame(() => {
            if (!messagesContainer.value) {
                return;
            }

            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        });
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
    if (!newMessage.value.trim() || !props.conversation) {
return;
}

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

async function sendImage(event: Event) {
    const input = event.target as HTMLInputElement;

    const file = input.files?.[0];

    if (!file || !props.conversation) {
        return;
    }

    try {
        const formData = new FormData();

        formData.append('type', 'image');
        formData.append('image', file);

        const res = await axios.post(
            `/chats/${props.conversation.id}/messages`,
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            },
        );

        emit('message-sent', res.data.message);

        showAttachMenu.value = false;

        input.value = '';
    } catch (error: any) {
        console.error(error.response?.data ?? error);
    }
}

async function sendFile(event: Event) {
    const input = event.target as HTMLInputElement;

    const file = input.files?.[0];

    if (!file || !props.conversation) {
        return;
    }

    try {
        const formData = new FormData();

        formData.append('type', 'file');
        formData.append('file', file);

        const res = await axios.post(
            `/chats/${props.conversation.id}/messages`,
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            },
        );

        emit('message-sent', res.data.message);

        showAttachMenu.value = false;

        input.value = '';
    } catch (error: any) {
        console.error(error.response?.data ?? error);
    }
}

async function startRecordingAudio() {
    if (!props.conversation) {
        return;
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            audio: true,
        });

        audioChunks.value = [];
        recordingSeconds.value = 0;

        mediaRecorder.value = new MediaRecorder(stream);

        mediaRecorder.value.ondataavailable = (event) => {
            if (event.data.size > 0) {
                audioChunks.value.push(event.data);
            }
        };

        mediaRecorder.value.onstop = async () => {
            const audioBlob = new Blob(audioChunks.value, {
                type: 'audio/webm',
            });

            stream.getTracks().forEach((track) => track.stop());

            if (audioBlob.size > 0) {
                await sendAudio(audioBlob);
            }

            resetRecordingState();
        };

        mediaRecorder.value.start();
        isRecordingAudio.value = true;
        showAttachMenu.value = false;

        recordingInterval.value = window.setInterval(() => {
            recordingSeconds.value += 1;

            if (recordingSeconds.value >= maxRecordingSeconds) {
                stopRecordingAudio();
            }
        }, 1000);
    } catch (error) {
        console.error('No se pudo acceder al micrófono:', error);
    }
}

function stopRecordingAudio() {
    if (!mediaRecorder.value || mediaRecorder.value.state === 'inactive') {
        return;
    }

    mediaRecorder.value.stop();
}

function cancelRecordingAudio() {
    if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
        mediaRecorder.value.onstop = null;
        mediaRecorder.value.stop();
    }

    resetRecordingState();
}

function resetRecordingState() {
    isRecordingAudio.value = false;
    audioChunks.value = [];
    recordingSeconds.value = 0;

    if (recordingInterval.value) {
        clearInterval(recordingInterval.value);
        recordingInterval.value = null;
    }
}

async function sendAudio(audioBlob: Blob) {
    if (!props.conversation) {
        return;
    }

    try {
        const formData = new FormData();

        formData.append('type', 'audio');
        formData.append('audio', audioBlob, `audio-${Date.now()}.webm`);

        const res = await axios.post(
            `/chats/${props.conversation.id}/messages`,
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            },
        );

        emit('message-sent', res.data.message);
    } catch (error: any) {
        console.error(error.response?.data ?? error);
    }
}

async function sendLocation() {
    if (!props.conversation) {
        return;
    }

    if (!navigator.geolocation) {
        console.error('Tu navegador no soporta ubicación.');

        return;
    }

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            try {
                const res = await axios.post(
                    `/chats/${props.conversation!.id}/messages`,
                    {
                        type: 'location',
                        lat: lat,
                        lng: lng,
                    },
                );

                emit('message-sent', res.data.message);
                showAttachMenu.value = false;
            } catch (error: any) {
                console.error(error.response?.data ?? error);
            }
        },
        (error) => {
            console.error('No se pudo obtener la ubicación:', error);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
        },
    );
}
</script>

<template>
    <div class="relative h-full w-full overflow-hidden">
        <!-- Header -->
        <header class="absolute left-[27px] top-[24px] flex w-[calc(100%-54px)] items-center justify-between">
            <!-- Usuario -->
            <div class="min-w-0 flex flex-1 items-center gap-3 pr-4">

                <button
                    type="button"
                    class="mr-1 text-[28px] font-bold text-[#FF7608] xl:hidden"
                    @click="emit('back-to-list')"
                >
                    ←
                </button>

                <UserAvatar
                    :photo="
                        conversation?.type === 'group'
                            ? conversation?.photo
                            : user?.profile_photo
                    "
                    className="h-[44px] w-[44px] outline outline-2 outline-[#FFEBC9]"
                />

                <h2 class="min-w-0 truncate font-['Nunito_Sans'] text-[18px] font-bold text-[#442F2F] sm:text-[24px]">
                    {{
                        conversation?.type === 'group'
                            ? conversation.name
                            : user?.username
                    }}
                </h2>
            </div>

            <!-- Acciones -->
            <div class="flex shrink-0 items-center gap-3 sm:gap-[27px]">
                <button
                    v-if="!isSelfPrivateChat"
                    type="button"
                    class="cursor-pointer transition hover:scale-105 hover:opacity-80"
                    @click="startVoiceCall"
                >
                    <img src="/icons/Phone.svg" alt="Llamada" class="h-[34px] w-[34px] sm:h-[44px] sm:w-[44px]" />
                </button>

                <button
                    v-if="!isSelfPrivateChat"
                    type="button"
                    class="cursor-pointer transition hover:scale-105 hover:opacity-80"
                    @click="startVideoCall"
                >
                    <img src="/icons/Video.svg" alt="Videollamada" class="h-[34px] w-[34px] sm:h-[44px] sm:w-[44px]" />
                </button>

                <button type="button" class="cursor-pointer transition hover:scale-105 hover:opacity-80" @click="emit('toggle-info')">
                    <img src="/icons/Info.svg" alt="Información del chat" class="h-[34px] w-[34px] sm:h-[44px] sm:w-[44px]" />
                </button>
            </div>
        </header>

        <!-- Línea superior -->
        <div class="absolute top-[86px] h-[1px] w-full bg-[#FF7608]" />

        <div
            v-if="
                conversation?.type === 'group' &&
                hasActiveGroupCall
            "
            class="absolute left-[27px] top-[96px] z-20 flex w-[calc(100%-54px)] items-center justify-between rounded-[18px] bg-[#FF7608] px-4 py-3 text-white shadow-lg"
        >
            <div class="flex items-center gap-3">
                <div class="h-[12px] w-[12px] animate-pulse rounded-full bg-green-400"></div>

                <div>
                    <p class="text-[15px] font-bold">
                        Llamada grupal activa
                    </p>

                    <p class="text-[12px] opacity-90">
                        Puedes volver a unirte
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="rounded-full bg-white px-4 py-2 text-[14px] font-bold text-[#FF7608] transition hover:scale-105"
                @click="joinActiveGroupCall"
            >
                Unirse
            </button>
        </div>

        <section
            ref="messagesContainer"
            :class="['chat-scroll invisible-scrollbar absolute bottom-[85px] left-[27px] flex w-[calc(100%-54px)] flex-col gap-[20px] overflow-y-auto pb-[20px]', conversation?.type === 'group' && hasActiveGroupCall ? 'top-[172px]' : 'top-[114px]']"
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
                    :class="[
                        'max-w-[541px] whitespace-pre-wrap break-words',
                        msg.type === 'image' || msg.type === 'audio' || msg.type === 'file' || msg.type === 'location'
                            ? ''
                            : 'rounded-[32px] bg-[#604646] px-5 py-2 text-white'
                    ]"
                >
                    <template v-if="msg.type === 'image'">
                        <img
                            :src="msg.attachments?.[0]?.url"
                            class="max-w-[260px] cursor-pointer rounded-[16px] object-cover transition hover:opacity-90"
                            @click="selectedImageUrl = msg.attachments?.[0]?.url ?? null"
                            @load="scrollToBottom"
                        />
                    </template>

                    <template v-else-if="msg.type === 'audio'">
                        <div class="flex items-center">
                            <audio
                                :src="msg.attachments?.[0]?.url"
                                controls
                                class="max-w-[260px]"
                            ></audio>
                        </div>
                    </template>

                    <template v-else-if="msg.type === 'file'">
                        <a
                            :href="msg.attachments?.[0]?.url"
                            target="_blank"
                            class="flex items-center gap-3 rounded-[16px] bg-[#FFF7EB] px-4 py-3 text-[#442F2F]"
                        >
                            <img
                                src="/icons/File plus.svg"
                                class="h-[28px] w-[28px]"
                            />

                            <div class="min-w-0">
                                <p class="truncate font-bold">
                                    {{ msg.attachments?.[0]?.original_name }}
                                </p>
                            </div>
                        </a>
                    </template>

                    <template v-else-if="msg.type === 'location'">
                        <a
                            :href="msg.body"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-3 rounded-[16px] bg-[#FFF7EB] px-4 py-3 font-bold text-[#442F2F]"
                        >
                            <img src="/icons/Map pin.svg" class="h-[28px] w-[28px]" />
                            <span>Ver ubicación</span>
                        </a>
                    </template>

                    <template v-else>
                        {{ msg.body }}
                    </template>
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
                        :class="[
                            'max-w-[541px] whitespace-pre-wrap break-words',
                            msg.type === 'image' || msg.type === 'audio' || msg.type === 'file' || msg.type === 'location'
                            ? ''
                            : 'rounded-[32px] bg-[#FF7608] px-5 py-2 text-white'
                        ]"
                    >
                        <template v-if="msg.type === 'image'">
                            <img
                                :src="msg.attachments?.[0]?.url"
                                class="max-w-[260px] cursor-pointer rounded-[16px] object-cover transition hover:opacity-90"
                                @click="selectedImageUrl = msg.attachments?.[0]?.url ?? null"
                                @load="scrollToBottom"
                            />
                        </template>

                        <template v-else-if="msg.type === 'audio'">
                            <div class="flex items-center">
                                <audio
                                    :src="msg.attachments?.[0]?.url"
                                    controls
                                    class="max-w-[260px]"
                                ></audio>
                            </div>
                        </template>

                        <template v-else-if="msg.type === 'file'">
                            <a
                                :href="msg.attachments?.[0]?.url"
                                target="_blank"
                                class="flex items-center gap-3 rounded-[16px] bg-[#FFF7EB] px-4 py-3 text-[#442F2F]"
                            >
                                <img
                                    src="/icons/File plus.svg"
                                    class="h-[28px] w-[28px]"
                                />

                                <div class="min-w-0">
                                    <p class="truncate font-bold">
                                        {{ msg.attachments?.[0]?.original_name }}
                                    </p>
                                </div>
                            </a>
                        </template>

                        <template v-else-if="msg.type === 'location'">
                            <a
                                :href="msg.body"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center gap-3 rounded-[16px] bg-[#FFF7EB] px-4 py-3 font-bold text-[#442F2F]"
                            >
                                <img src="/icons/Map pin.svg" class="h-[28px] w-[28px]" />
                                <span>Ver ubicación</span>
                            </a>
                        </template>

                        <template v-else>
                            {{ msg.body }}
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <input
            ref="imageInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="sendImage"
        />

        <input
            ref="fileInput"
            type="file"
            class="hidden"
            @change="sendFile"
        />

        <!-- Input inferior -->
        <footer class="absolute bottom-0 left-0 w-full">
            <AudioRecordingBar
                v-if="isRecordingAudio"
                :seconds="recordingSeconds"
                :max-seconds="maxRecordingSeconds"
                @cancel="cancelRecordingAudio"
                @send="stopRecordingAudio"
            />

            <div
                v-else
                class="flex h-[69px] w-full items-center gap-[18px] px-[27px]"
            >
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
                        <button
                            type="button"
                            @click.stop="fileInput?.click()"
                            class="flex items-center gap-[16px] text-left"
                        >
                            <img src="/icons/File plus.svg" class="h-[32px] w-[32px]" />
                            <span class="text-[15px] font-bold text-[#442F2F]">
                                Adjuntar archivos
                            </span>
                        </button>

                        <!-- Imagen -->
                        <button
                            type="button"
                            @click.stop="imageInput?.click()"
                            class="flex items-center gap-[16px] text-left"
                        >
                            <img src="/icons/Image2.svg" class="h-[32px] w-[32px]" />
                            <span class="text-[15px] font-bold text-[#442F2F]">
                                Enviar imágenes
                            </span>
                        </button>

                        <!-- Audio -->
                        <button
                            type="button"
                            @click.stop="startRecordingAudio"
                            class="flex items-center gap-[16px] text-left"
                        >
                            <img src="/icons/Mic.svg" class="h-[32px] w-[32px]" />
                            <span class="text-[15px] font-bold text-[#442F2F]">
                                Grabar audio
                            </span>
                        </button>

                        <!-- Ubicación -->
                        <button
                            type="button"
                            @click.stop="sendLocation"
                            class="flex items-center gap-[16px] text-left"
                        >
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

             </div>
        </footer>

        <div
            v-if="selectedImageUrl"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 px-4"
            @click="selectedImageUrl = null"
        >
            <button
                type="button"
                class="absolute right-6 top-6 text-[38px] font-bold text-white"
                @click.stop="selectedImageUrl = null"
            >
                ×
            </button>

            <img
                :src="selectedImageUrl"
                class="max-h-[90vh] max-w-[90vw] rounded-[18px] object-contain"
                @click.stop
            />
        </div>

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