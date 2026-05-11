<script setup lang="ts">
import CreateGroupPanel from '@/components/Chats/CreateGroupPanel.vue';
import SendMessagePanel from '@/components/Chats/SendMessagePanel.vue';
import ActiveChatPanel from '@/components/Chats/ActiveChatPanel.vue';

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

defineProps<{
    mode: 'empty' | 'create-group' | 'send-message' | 'active-chat';
    selectedUser: User | null;
    selectedGroupUsers: User[];
    selectedConversation: Conversation | null;
    messages: Message[];
    onlineUserIds: number[];
}>();

const emit = defineEmits<{
    (e: 'open-chat', conversation: Conversation): void;
    (e: 'open-group-chat', users: User[]): void;
    (e: 'create-group'): void;
    (e: 'change-group-name', name: string): void;
    (e: 'message-sent', message: Message): void;
    (e: 'toggle-info'): void;
}>();

</script>

<template>
    <main
        class="h-[769px] flex-1 rounded-[15px] bg-[#FDF0D9] shadow-[0px_4px_4px_rgba(0,0,0,0.25)]"
    >
        <!-- Crear grupo -->
        <CreateGroupPanel
            v-if="mode === 'create-group'"
            @open-group-chat="emit('open-group-chat', $event as User[])"
            @create-group="emit('create-group')"
        />

        <!-- Enviar mensaje -->
        <SendMessagePanel
            v-else-if="mode === 'send-message'"
            @open-chat="emit('open-chat', $event as Conversation)"
        />

        <ActiveChatPanel
            v-else-if="mode === 'active-chat'"
            :user="selectedUser"
            :group-users="selectedGroupUsers"
            :conversation="selectedConversation"
            :messages="messages"
            :online-user-ids="onlineUserIds"
            @change-group-name="emit('change-group-name', $event)"
            @message-sent="emit('message-sent', $event)"
            @toggle-info="emit('toggle-info')"
        />

        <div v-else class="flex h-full items-center justify-center">
           
        </div>
    </main>
</template>