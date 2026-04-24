<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import ChatActionMenu from '@/components/Chats/ChatActionMenu.vue';
import ChatWindow from '@/components/Chats/ChatWindow.vue';
import ChatInfoPanel from '@/components/Chats/ChatInfoPanel.vue';

type User = {
    id: number;
    first_name: string;
    last_name: string;
    username: string;
    last_seen_at: string | null;
};

type Message = {
    id: number;
    conversation_id: number;
    sender_id: number;
    type: string;
    body_encrypted: string | null;
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

defineProps<{
    conversations: Conversation[];
}>();

const showMenu = ref(false);
const chatMode = ref<'empty' | 'create-group' | 'send-message' | 'active-chat'>('empty');

const emit = defineEmits<{
    (e: 'open-chat', user: User): void;
    (e: 'open-group-chat', users: User[]): void;
    (e: 'toggle-info'): void;
}>();

const selectedGroupUsers = ref<User[]>([]);
const selectedUser = ref<User | null>(null);
const selectedConversation = ref<Conversation | null>(null);
const messages = ref<Message[]>([]);
const showChatInfo = ref(false);

function openCreateGroup() {
    chatMode.value = 'create-group';
    showMenu.value = false;
}

function openSendMessage() {
    chatMode.value = 'send-message';
    showMenu.value = false;
}

function openGroupChat(users: User[]) {
    selectedGroupUsers.value = users;
    selectedUser.value = null;
    chatMode.value = 'active-chat';
}

function openChat(user: User) {
    selectedUser.value = user;
    selectedGroupUsers.value = [];
    chatMode.value = 'active-chat';
}

function isOnline(user: User | undefined) {
    if (!user?.last_seen_at) return false;

    const lastSeen = new Date(user.last_seen_at).getTime();
    const now = Date.now();

    return now - lastSeen < 2 * 60 * 1000;
}

function getLastSeenText(user: User | undefined) {
    if (!user?.last_seen_at) return '';

    const diff = Date.now() - new Date(user.last_seen_at).getTime();
    const minutes = Math.floor(diff / 60000);

    if (minutes < 1) return 'Activo';
    if (minutes < 60) return `Últ. vez hace ${minutes} min`;

    const hours = Math.floor(minutes / 60);
    return `Últ. vez hace ${hours} h`;
}

async function openConversation(conversation: Conversation) {
    const response = await fetch(`/chats/${conversation.id}`);
    const data = await response.json();

    selectedConversation.value = data.conversation;
    messages.value = data.messages;

    if (data.conversation.type === 'group') {
        selectedGroupUsers.value = data.conversation.users;
        selectedUser.value = null;
    } else {
        selectedUser.value = data.conversation.users[0] ?? null;
        selectedGroupUsers.value = [];
    }

    chatMode.value = 'active-chat';
}

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
                    v-if="conversations.length === 0"
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
                        v-for="conversation in conversations"
                        :key="conversation.id"
                        type="button"
                        class="flex flex-col items-start gap-2"
                        @click="openConversation(conversation)"
                    >
                        <!-- Fila superior -->
                        <div class="flex items-center gap-3">
                            <!-- Avatar -->
                            <div
                                class="flex h-[44px] w-[44px] items-center justify-center rounded-full border-2 border-[#FFEBC9] bg-[#FF7608]"
                            >
                                <img
                                    src="/icons/user2.svg"
                                    alt="Usuario"
                                    class="h-[24px] w-[24px]"
                                />
                            </div>

                            <!-- Nombre -->
                            <p class="font-['Nunito_Sans'] text-[24px] font-bold text-[#442F2F]">
                                {{
                                    conversation.type === 'group'
                                        ? conversation.name
                                        : conversation.users
                                            .filter(u => u.id !== $page.props.auth.user.id)[0]?.first_name
                                }}
                            </p>

                            <!-- Estado activo / última vez -->
                                <div
                                    v-if="conversation.type === 'private'"
                                    class="flex items-center gap-2"
                                >
                                    <span
                                        class="h-[10px] w-[10px] rounded-full"
                                        :class="
                                            isOnline(conversation.users.find(u => u.id !== $page.props.auth.user.id))
                                                ? 'bg-green-500'
                                                : 'bg-gray-400'
                                        "
                                    ></span>

                                    <span class="font-['Nunito_Sans'] text-[12px] text-[#442F2F]">
                                        {{
                                            getLastSeenText(
                                                conversation.users.find(u => u.id !== $page.props.auth.user.id)
                                            )
                                        }}
                                    </span>
                                </div>
                        </div>

                        <!-- Último mensaje -->
                        <p
                            class="w-full text-right font-['Nunito_Sans'] text-[14px] text-[#442F2F] truncate"
                        >
                            {{ conversation.messages?.[0]?.body_encrypted ?? 'Sin mensajes...' }}
                        </p>
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
                @open-chat="openChat"
                @open-group-chat="openGroupChat"
                @toggle-info="showChatInfo = !showChatInfo"
            />

            <ChatInfoPanel
                v-if="showChatInfo"
                :user="selectedUser"
                :group-users="selectedGroupUsers"
                :conversation="selectedConversation"
            />
        </div>
    </section>
</template>