<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted, onBeforeUnmount } from 'vue';

import ChatActionMenu from '@/components/Chats/ChatActionMenu.vue';
import ChatInfoPanel from '@/components/Chats/ChatInfoPanel.vue';
import ChatWindow from '@/components/Chats/ChatWindow.vue';
import UserAvatar from '@/components/UserAvatar.vue';

//socket
import { connectSocket, getSocket } from '@/lib/socket';
let socket = getSocket();


type User = {
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
    body?: string | null;
    created_at: string;
    sender?: User;
};

type Conversation = {
    id: number;
    type: 'private' | 'group';
    name: string | null;
    photo: string | null;
    created_by: number;
    users: User[];
    messages?: Message[];
};

type CallType = 'voice' | 'video';

//sistema online offline desde sockets y last_seen_at en mi bd
const onlineUserIds = ref<number[]>([]);

const props = defineProps<{
    conversations: Conversation[];
    openConversationId?: number | string | null;
}>();

const conversationList = ref<Conversation[]>([...props.conversations]);
const page = usePage();
const authUserId = page.props.auth.user.id;
console.log('Mi userId:', authUserId);

const chatMode = ref<'empty' | 'create-group' | 'send-message' | 'active-chat'>('empty');

const selectedConversation = ref<Conversation | null>(null);
const selectedUser = ref<User | null>(null);
const selectedGroupUsers = ref<User[]>([]);
const messages = ref<Message[]>([]);
const showChatInfo = ref(false);
const activeGroupCalls = ref<Map<number, CallType>>(new Map());

async function openChat(conversation: Conversation) {
    await openConversation(conversation);
}

function getOtherUser(conversation: Conversation): User | null {
    const otherUser = conversation.users.find(
        (user) => Number(user.id) !== Number(authUserId),
    );

    return otherUser ?? conversation.users[0] ?? null;
}

function backToChatList() {
    chatMode.value = 'empty';
    showChatInfo.value = false;
}

function markGroupCallActive(
    conversationId: number,
    callType: CallType = 'voice',
) {
    activeGroupCalls.value.set(Number(conversationId), callType);
}

function markGroupCallInactive(conversationId: number) {
    activeGroupCalls.value.delete(Number(conversationId));
}

function isGroupCallActive(conversationId: number | null | undefined) {
    if (!conversationId) {
        return false;
    }

    return activeGroupCalls.value.has(Number(conversationId));
}

function getActiveGroupCallType(
    conversationId: number | null | undefined,
): CallType {
    if (!conversationId) {
        return 'voice';
    }

    return activeGroupCalls.value.get(Number(conversationId)) ?? 'voice';
}

async function openConversationById(conversationId: number) {
    const conversation = conversationList.value.find(
        (c) => c.id === conversationId
    );

    if (!conversation) {
return;
}

    await openConversation(conversation);
}

async function openConversation(conversation: Conversation) {
    try {
        const res = await axios.get(`/chats/${conversation.id}`);
        const data = res.data;

        selectedConversation.value = data.conversation;
        messages.value = data.messages ?? [];

        socket?.emit('join-chat', data.conversation.id);

        if (data.conversation.type === 'group') {
            socket?.emit('get-active-group-call', {
                conversationId: data.conversation.id,
            });
        }

        if (data.conversation.type === 'group') {
            selectedGroupUsers.value = data.conversation.users;
            selectedUser.value = null;
        } else {
            selectedUser.value = getOtherUser(data.conversation);
            selectedGroupUsers.value = [];
        }

        showChatInfo.value = false;
        chatMode.value = 'active-chat';
    } catch (error) {
        console.error(error);
    }
}

async function crearGrupo() {
    if (selectedGroupUsers.value.length < 2) {
        console.log('Selecciona al menos 2 usuarios');

        return;
    }

    if (selectedGroupUsers.value.length > 4) {
        console.log('Máximo 4 usuarios');

        return;
    }

    try {
        const res = await axios.post('/conversations/group', {
            user_ids: selectedGroupUsers.value.map((user) => user.id),
        });

        const conversation = {
            ...res.data.conversation,
            messages: [],
        };

        selectedConversation.value = conversation;
        selectedGroupUsers.value = conversation.users;
        selectedUser.value = null;
        messages.value = [];

        addConversationIfMissing(conversation);

        socket?.emit('new-conversation', conversation);
        socket?.emit('join-chat', conversation.id);

        showChatInfo.value = false;
        chatMode.value = 'active-chat';
    } catch (error) {
        console.error(error);
    }
}

async function cambiarNombreGrupo(nuevoNombre: string) {
    if (!selectedConversation.value) {
return;
}

    try {
        const res = await axios.patch(
            `/conversations/${selectedConversation.value.id}/name`,
            { name: nuevoNombre },
        );

        selectedConversation.value.name = res.data.conversation.name;

        socket?.emit('update-conversation', res.data.conversation);
    } catch (error) {
        console.error(error);
    }
}

function openCreateGroup() {
    selectedConversation.value = null;
    selectedUser.value = null;
    selectedGroupUsers.value = [];
    messages.value = [];
    showChatInfo.value = false;
    chatMode.value = 'create-group';
}

function openSendMessage() {
    selectedConversation.value = null;
    selectedUser.value = null;
    selectedGroupUsers.value = [];
    messages.value = [];
    showChatInfo.value = false;
    chatMode.value = 'send-message';
}

function openGroupChat(users: User[]) {
    selectedGroupUsers.value = users;
    selectedUser.value = null;
    selectedConversation.value = null;
    messages.value = [];
    showChatInfo.value = false;
    chatMode.value = 'create-group';
}

function refreshSocketConnection() {
    socket = getSocket();

    if (!socket) {
return;
}

    if (!socket.connected) {
        socket.connect();
    }

    socket.emit('get-users-online');
    joinAllConversationRooms();
    socket.emit('get-active-group-calls');
}

function appendMessageIfMissing(newMessage: Message) {
    const exists = messages.value.some((message) => message.id === newMessage.id);

    if (!exists) {
        messages.value.push(newMessage);
    }
}

function updateConversationLastMessage(newMessage: Message) {
    const index = conversationList.value.findIndex(
        (conversation) => conversation.id === newMessage.conversation_id,
    );

    if (index === -1) {
return;
}

    const conversation = conversationList.value[index];

    const updatedConversation = {
        ...conversation,
        messages: [newMessage],
    };

    conversationList.value.splice(index, 1);
    conversationList.value.unshift(updatedConversation);
}

function updateConversationData(updatedConversation: Conversation) {
    const index = conversationList.value.findIndex(
        (conversation) => Number(conversation.id) === Number(updatedConversation.id),
    );

    if (index !== -1) {
        conversationList.value[index] = {
            ...conversationList.value[index],
            ...updatedConversation,
        };
    }

    if (
        selectedConversation.value &&
        Number(selectedConversation.value.id) === Number(updatedConversation.id)
    ) {
        selectedConversation.value = {
            ...selectedConversation.value,
            ...updatedConversation,
        };
    }
}

function handleGroupNameUpdated(updatedConversation: Conversation) {
    updateConversationData(updatedConversation);

    socket?.emit('update-conversation', updatedConversation);
}

function handleGroupPhotoUpdated(updatedConversation: Conversation) {
    updateConversationData(updatedConversation);

    socket?.emit('update-conversation', updatedConversation);
}

function addConversationIfMissing(conversation: Conversation) {
    const exists = conversationList.value.some(
        (item) => item.id === conversation.id,
    );

    if (exists) {
return;
}

    conversationList.value.unshift({
        ...conversation,
        messages: conversation.messages ?? [],
    });

    socket?.emit('join-chat', conversation.id);
}

function handleMessageSent(newMessage: Message) {
    if (selectedConversation.value) {
        addConversationIfMissing({
            ...selectedConversation.value,
            messages: [newMessage],
        });

        socket?.emit('new-conversation', {
            ...selectedConversation.value,
            messages: [newMessage],
        });
    }

    appendMessageIfMissing(newMessage);
    updateConversationLastMessage(newMessage);

    socket?.emit('send-message', newMessage);
}

function getLastMessagePreview(conversation: Conversation) {
    const message = conversation.messages?.[0];

    if (!message) {
        return 'Sin mensajes...';
    }

    let preview = '';

    switch (message.type) {
        case 'image':
            preview = '📷 Foto';
            break;

        case 'audio':
            preview = '🎤 Audio';
            break;

        case 'file':
            preview = '📎 Archivo';
            break;

        case 'location':
            preview = '📍 Ubicación';
            break;

        default:
            preview = message.body ?? 'Sin mensajes...';
    }

    if (Number(message.sender_id) === Number(authUserId)) {
        return `Tú: ${preview}`;
    }

    return preview;
}

//sistema online offline desde sockets y last_seen_at en mi bd
function isOnline(user: User | undefined | null) {
    if (!user) {
return false;
}

    return onlineUserIds.value.includes(Number(user.id));
}

function getLastSeenText(user: User | undefined | null) {
    if (!user) {
return '';
}

    if (isOnline(user)) {
        return 'Activo';
    }

    if (!user.last_seen_at) {
        return '';
    }

    const diff = Date.now() - new Date(user.last_seen_at).getTime();
    const minutes = Math.floor(diff / 60000);

    if (minutes < 1) {
return 'Últ. vez hace un momento';
}

    if (minutes < 60) {
return `Últ. vez hace ${minutes} min`;
}

    const hours = Math.floor(minutes / 60);

    return `Últ. vez hace ${hours} h`;
}

function handleVisibilityChange() {
    if (!document.hidden) {
        refreshSocketConnection();
    }
}

function joinAllConversationRooms() {
    if (!socket) {
return;
}

    conversationList.value.forEach((conversation) => {
        socket?.emit('join-chat', conversation.id);
    });
}

onMounted(async () => {
    socket = connectSocket(authUserId);

    socket?.on('connect', refreshSocketConnection);

    window.addEventListener('focus', refreshSocketConnection);
    document.addEventListener('visibilitychange', handleVisibilityChange);

    socket?.on('users-online', (userIds: number[]) => {
        onlineUserIds.value = userIds.map(Number);
    });

    socket?.emit('get-users-online');
    joinAllConversationRooms();
    socket?.emit('get-active-group-calls');

    if (props.openConversationId) {
        await openConversationById(Number(props.openConversationId));
    }

    socket?.on('receive-message', (newMessage: Message) => {
        if (
            selectedConversation.value &&
            newMessage.conversation_id === selectedConversation.value.id
        ) {
            appendMessageIfMissing(newMessage);
        }

        updateConversationLastMessage(newMessage);
    });

    socket?.on(
        'active-group-calls',
        (calls: { conversationId: number; callType?: CallType }[]) => {
            const nextActiveGroupCalls = new Map<number, CallType>();

            calls.forEach((call) => {
                nextActiveGroupCalls.set(
                    Number(call.conversationId),
                    call.callType ?? 'voice',
                );
            });

            activeGroupCalls.value = nextActiveGroupCalls;
        },
    );

    socket?.on('active-group-call-status', ({ conversationId, active, callType }) => {
        if (active) {
            markGroupCallActive(Number(conversationId), callType ?? 'voice');
        } else {
            markGroupCallInactive(Number(conversationId));
        }
    });

    socket?.on('conversation-created', (conversation: Conversation) => {
        const belongsToMe = conversation.users.some(
            (user) => Number(user.id) === Number(authUserId),
        );

        if (!belongsToMe) {
            return;
        }

        const hasMessages = (conversation.messages ?? []).length > 0;

        if (conversation.type === 'private' && !hasMessages) {
            return;
        }

        addConversationIfMissing({
            ...conversation,
            messages: conversation.messages ?? [],
        });

        const firstMessage = conversation.messages?.[0];

        if (firstMessage) {
            updateConversationLastMessage(firstMessage);
        }

        socket?.emit('join-chat', conversation.id);
    });

    socket?.on('conversation-updated', (updatedConversation: Conversation) => {
        updateConversationData(updatedConversation);
    });

    socket?.on('group-call-user-joined', ({ conversationId }) => {
        markGroupCallActive(
            Number(conversationId),
            getActiveGroupCallType(Number(conversationId)),
        );
    });

    socket?.on('group-call-ended', ({ conversationId }) => {
        markGroupCallInactive(Number(conversationId));
    });
});

onBeforeUnmount(() => {
    socket?.off('users-online');
    socket?.off('receive-message');
    socket?.off('conversation-updated');
    socket?.off('conversation-created');

    socket?.off('incoming-group-call');
    socket?.off('group-call-user-joined');
    socket?.off('group-call-ended');
    socket?.off('active-group-calls');
    socket?.off('active-group-call-status');

    window.removeEventListener('focus', refreshSocketConnection);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    socket?.off('connect');
});
</script>

<template>
    <Head title="Chats" />

    <section class="min-h-screen bg-[#FFF7EB] px-4 py-6 md:px-6 md:py-8">
        <div class="flex w-full flex-col gap-6 xl:flex-row xl:gap-7">
            <!-- Panel izquierdo -->
            <aside class="w-full shrink-0 xl:w-[333px]" :class="chatMode !== 'empty' ? 'hidden xl:block' : 'block'">
                <!-- Encabezado Chats -->
                <div
                    class="flex h-[76px] w-full items-center justify-center rounded-[25px] border border-[#FFF7EB] bg-[#FF7608] px-5 py-[15px] md:h-[86px] md:px-[39px]"
                >
                    <div class="flex w-full items-end justify-between md:h-[48px]">
                        <h1
                            class="font-['Odor_Mean_Chey'] text-[38px] leading-none font-normal text-white md:text-[48px]"
                        >
                            Chats
                        </h1>

                        <ChatActionMenu
                            @create-group="openCreateGroup"
                            @send-message="openSendMessage"
                        />
                    </div>
                </div>

                <!-- Si no hay chats -->
                <div
                    v-if="conversationList.length === 0"
                    class="mt-16 flex flex-col items-center gap-[17px] xl:mt-45"
                >
                    <img
                        src="/images/nofound2.png"
                        alt="Sin chats"
                        class="h-[147px] w-[223px] object-contain"
                    />

                    <p
                        class="w-[224px] text-center font-['Nunito_Sans'] text-[20px] font-bold text-[#B4C5BD]"
                    >
                        ¡No hay información que mostrar!
                    </p>
                </div>

                <!-- Lista de chats -->
                <div v-else class="invisible-scrollbar mt-6 flex max-h-[calc(100vh-260px)] flex-col gap-5 overflow-y-auto pr-1 md:gap-[30px]">
                    <button
                        v-for="conversation in conversationList"
                        :key="conversation.id"
                        type="button"
                        class="grid w-full cursor-pointer grid-cols-[44px_minmax(0,1fr)] gap-3 rounded-2xl p-2 text-left hover:bg-[#FFEBC9]/50"
                        @click="openConversation(conversation)"
                    >
                        <!-- Avatar -->
                        <UserAvatar
                            :photo="
                                conversation.type === 'group'
                                    ? conversation.photo
                                    : getOtherUser(conversation)?.profile_photo
                            "
                            className="h-[44px] w-[44px] shrink-0 border-2 border-[#FFEBC9]"
                        />

                        <!-- CONTENIDO -->
                        <div class="min-w-0 w-full">
                            <!-- Nombre + estado -->
                            <div class="flex min-w-0 items-center gap-2">
                                <p class="min-w-0 truncate text-left font-['Nunito_Sans'] text-[20px] font-bold text-[#442F2F] md:text-[24px]">
                                    {{
                                        conversation.type === 'group'
                                            ? conversation.name
                                            : getOtherUser(conversation)?.username
                                    }}
                                </p>

                                <!-- Estado -->
                                <div
                                    v-if="conversation.type === 'private'"
                                    class="flex shrink-0 items-center gap-1"
                                >
                                    <span
                                        class="h-[10px] w-[10px] rounded-full"
                                        :class="
                                            isOnline(getOtherUser(conversation))
                                                ? 'bg-green-500'
                                                : 'bg-gray-400'
                                        "
                                    ></span>

                                    <span class="font-['Nunito_Sans'] text-[12px] text-[#442F2F]">
                                        {{ getLastSeenText(getOtherUser(conversation)) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Último mensaje -->
                            <p
                                class="mt-1 max-w-full truncate text-left font-['Nunito_Sans'] text-[14px] text-[#442F2F] xl:max-w-[220px]"
                            >
                                {{ getLastMessagePreview(conversation) }}
                            </p>
                        </div>
                    </button>
                </div>
            </aside>

            <!-- Panel principal chats -->
            <div class="min-w-0 flex-1" :class="chatMode === 'empty' || showChatInfo ? 'hidden xl:block' : 'block'">
                <ChatWindow
                    :mode="chatMode"
                    :selected-user="selectedUser"
                    :selected-group-users="selectedGroupUsers"
                    :selected-conversation="selectedConversation"
                    :messages="messages"
                    :online-user-ids="onlineUserIds"
                    :has-active-group-call="isGroupCallActive(selectedConversation?.id)"
                    :active-group-call-type="getActiveGroupCallType(selectedConversation?.id)"
                    @open-chat="openChat"
                    @open-group-chat="openGroupChat"
                    @create-group="crearGrupo"
                    @change-group-name="cambiarNombreGrupo"
                    @toggle-info="showChatInfo = !showChatInfo"
                    @message-sent="handleMessageSent"
                    @back-to-list="backToChatList"
                />
            </div>

            <div v-if="showChatInfo" class="w-full shrink-0 xl:w-auto">

                <ChatInfoPanel
                    :user="selectedUser"
                    :group-users="selectedGroupUsers"
                    :conversation="selectedConversation"
                    @group-name-updated="handleGroupNameUpdated"
                    @group-photo-updated="handleGroupPhotoUpdated"
                    @close-info="showChatInfo = false"
                />
            </div>
        </div>
    </section>
</template>

<style scoped>
.invisible-scrollbar {
    scrollbar-width: none;
}

.invisible-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>