<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

import ChatActionMenu from '@/components/Chats/ChatActionMenu.vue';
import ChatWindow from '@/components/Chats/ChatWindow.vue';
import ChatInfoPanel from '@/components/Chats/ChatInfoPanel.vue';
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

async function openChat(conversation: Conversation) {
    await openConversation(conversation);
}

function getOtherUser(conversation: Conversation): User | null {
    const otherUser = conversation.users.find(
        (user) => Number(user.id) !== Number(authUserId),
    );

    return otherUser ?? conversation.users[0] ?? null;
}

async function openConversationById(conversationId: number) {
    const conversation = conversationList.value.find(
        (c) => c.id === conversationId
    );

    if (!conversation) return;

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
    if (!selectedConversation.value) return;

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

    if (!socket) return;

    if (!socket.connected) {
        socket.connect();
    }

    socket.emit('get-users-online');
    joinAllConversationRooms();
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

    if (index === -1) return;

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

    if (exists) return;

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

    if (!message?.body) {
        return 'Sin mensajes...';
    }

    if (Number(message.sender_id) === Number(authUserId)) {
        return `Tú: ${message.body}`;
    }

    return message.body;
}

//sistema online offline desde sockets y last_seen_at en mi bd
function isOnline(user: User | undefined | null) {
    if (!user) return false;

    return onlineUserIds.value.includes(Number(user.id));
}

function getLastSeenText(user: User | undefined | null) {
    if (!user) return '';

    if (isOnline(user)) {
        return 'Activo';
    }

    if (!user.last_seen_at) {
        return '';
    }

    const diff = Date.now() - new Date(user.last_seen_at).getTime();
    const minutes = Math.floor(diff / 60000);

    if (minutes < 1) return 'Últ. vez hace un momento';
    if (minutes < 60) return `Últ. vez hace ${minutes} min`;

    const hours = Math.floor(minutes / 60);
    return `Últ. vez hace ${hours} h`;
}

function handleVisibilityChange() {
    if (!document.hidden) {
        refreshSocketConnection();
    }
}

function joinAllConversationRooms() {
    if (!socket) return;

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

    socket?.on('conversation-created', (conversation: Conversation) => {
        const belongsToMe = conversation.users.some(
            (user) => Number(user.id) === Number(authUserId),
        );

        if (!belongsToMe) return;

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
});

onBeforeUnmount(() => {
    socket?.off('users-online');
    socket?.off('receive-message');
    socket?.off('conversation-updated');
    socket?.off('conversation-created');

    window.removeEventListener('focus', refreshSocketConnection);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    socket?.off('connect');
});
</script>

<template>
    <Head title="Chats" />

    <section class="min-h-screen bg-[#FFF7EB] px-6 py-8">
        <div class="flex w-full gap-7">
            <!-- Panel izquierdo -->
            <aside class="w-[333px] shrink-0">
                <!-- Encabezado Chats -->
                <div
                    class="flex h-[86px] w-full items-center justify-center rounded-[25px] border border-[#FFF7EB] bg-[#FF7608] px-[39px] py-[15px]"
                >
                    <div class="flex h-[48px] w-[286px] items-end justify-between">
                        <h1
                            class="font-['Odor_Mean_Chey'] text-[48px] leading-none font-normal text-white"
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
                    class="mt-45 flex flex-col items-center gap-[17px]"
                >
                    <img
                        src="/images/nofound2.png"
                        alt="Sin chats"
                        class="h-[147px] w-[223px] object-contain"
                    />

                    <p
                        class="h-[47px] w-[224px] text-center font-['Nunito_Sans'] text-[20px] font-bold text-[#B4C5BD]"
                    >
                        ¡No hay información que mostrar!
                    </p>
                </div>

                <!-- Lista de chats -->
                <div v-else class="mt-6 flex flex-col gap-[30px]">
                    <button
                        v-for="conversation in conversationList"
                        :key="conversation.id"
                        type="button"
                        class="cursor-pointer flex items-start gap-3"
                        @click="openConversation(conversation)"
                    >
                        <!-- Avatar -->
                        <UserAvatar
                            :photo="
                                conversation.type === 'group'
                                    ? conversation.photo
                                    : getOtherUser(conversation)?.profile_photo
                            "
                            className="h-[44px] w-[44px] border-2 border-[#FFEBC9]"
                        />

                        <!-- CONTENIDO -->
                        <div class="flex flex-col">
                            <!-- Nombre + estado -->
                            <div class="flex items-center gap-3">
                                <p class="max-w-[220px] truncate text-left font-['Nunito_Sans'] text-[24px] font-bold text-[#442F2F]">
                                    {{
                                        conversation.type === 'group'
                                            ? conversation.name
                                            : getOtherUser(conversation)?.username
                                    }}
                                </p>

                                <!-- Estado -->
                                <div
                                    v-if="conversation.type === 'private'"
                                    class="flex items-center gap-2"
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
                                class="max-w-[220px] mt-1 truncate text-left font-['Nunito_Sans'] text-[14px] text-[#442F2F]"
                            >
                                {{ getLastMessagePreview(conversation) }}
                            </p>
                        </div>
                    </button>
                </div>
            </aside>

            <!-- Panel principal chats -->
            <ChatWindow
                :mode="chatMode"
                :selected-user="selectedUser"
                :selected-group-users="selectedGroupUsers"
                :selected-conversation="selectedConversation"
                :messages="messages"
                :online-user-ids="onlineUserIds"
                @open-chat="openChat"
                @open-group-chat="openGroupChat"
                @create-group="crearGrupo"
                @change-group-name="cambiarNombreGrupo"
                @toggle-info="showChatInfo = !showChatInfo"
                @message-sent="handleMessageSent"
            />

            <ChatInfoPanel
                v-if="showChatInfo"
                :user="selectedUser"
                :group-users="selectedGroupUsers"
                :conversation="selectedConversation"
                @group-name-updated="handleGroupNameUpdated"
                @group-photo-updated="handleGroupPhotoUpdated"
            />
        </div>
    </section>
</template>