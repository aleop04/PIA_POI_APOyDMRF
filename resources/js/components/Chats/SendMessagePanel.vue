<script setup lang="ts">
import { ref, watch } from 'vue';
import axios from 'axios';

type User = {
    id: number;
    first_name: string;
    last_name: string;
    username: string;
    last_seen_at: string | null;
};

type Conversation = {
    id: number;
    type: 'private' | 'group';
    name: string | null;
    photo: string | null;
    created_by: number;
    users: User[];
};

const search = ref('');
const users = ref<User[]>([]);

const emit = defineEmits<{
    (e: 'open-chat', conversation: Conversation): void;
}>();

watch(search, async (value) => {
    const query = value.trim();

    if (query.length < 1) {
        users.value = [];
        return;
    }

    try {
        const res = await axios.get(`/users/search?q=${encodeURIComponent(query)}`);
        users.value = res.data;
    } catch (error) {
        console.error(error);
    }
});

async function openChat(user: User) {
    try {
        const res = await axios.post('/conversations/private', {
            user_id: user.id,
        });

        emit('open-chat', res.data.conversation);

        search.value = '';
        users.value = [];
    } catch (error) {
        console.error(error);
    }
}
</script>

<template>
    <div class="relative h-full w-full">
        <!-- Header -->
        <div class="flex h-[120px] w-full flex-col justify-center gap-4">
            <div class="flex items-center gap-[15px] px-[23px]">
                <label
                    for="user-search"
                    class="text-center font-['Nunito_Sans'] text-[24px] font-bold text-[#442F2F]"
                >
                    Usuario:
                </label>

                <input
                    id="user-search"
                    v-model="search"
                    type="text"
                    placeholder="Escribe un usuario..."
                    autocomplete="off"
                    class="h-[41px] w-[320px] rounded-[14px] border border-transparent bg-transparent px-4 font-['Nunito_Sans'] text-[18px] font-bold text-[#442F2F] outline-none transition placeholder:text-[#B4C5BD]"
                />
            </div>

            <div class="h-[1px] w-full bg-[#FF7608]" />
        </div>

        <!-- Recomendaciones -->
        <div
            v-if="users.length > 0"
            class="absolute left-[121px] top-[86px] z-10 flex w-[202px] flex-col gap-[10px] rounded-[11px] bg-[#FFEDA5] px-[16px] py-[15px]"
        >
            <button
                v-for="user in users"
                :key="user.id"
                type="button"
                class="flex items-center gap-[16px] text-left transition hover:opacity-75"
                @click="openChat(user)"
            >
                <div
                    class="flex h-[32px] w-[32px] shrink-0 items-center justify-center rounded-full bg-[#FF7608] outline outline-2 outline-[#FFEBC9]"
                >
                    <span class="font-['Nunito_Sans'] text-[14px] font-bold text-white">
                        {{ user.username.charAt(0) }}
                    </span>
                </div>

                <span
                    class="w-[108px] truncate font-['Nunito_Sans'] text-[15px] font-bold text-[#442F2F]"
                >
                    {{ user.username }}
                </span>
            </button>
        </div>
    </div>
</template>