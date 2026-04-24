<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

type User = {
    id: number;
    first_name: string;
    last_name: string;
    username: string;
    last_seen_at: string | null;
};

type PageProps = {
    auth: {
        user: User;
    };
};

const page = usePage<PageProps>();
const authUser = computed(() => page.props.auth.user);

const search = ref('');
const users = ref<User[]>([]);
const selectedUsers = ref<User[]>([]);

const canCreateGroup = computed(() => selectedUsers.value.length >= 2);
const emit = defineEmits<{
    (e: 'open-group-chat', users: User[]): void;
}>();

watch(search, async (value) => {
    const query = value.trim();

    if (query.length < 1) {
        users.value = [];
        return;
    }

    const response = await fetch(`/users/search?q=${encodeURIComponent(query)}`);
    const data: User[] = await response.json();

    users.value = data.filter((user) => {
        const isMe = user.id === authUser.value.id;

        const alreadySelected = selectedUsers.value.some(
            (selected) => selected.id === user.id,
        );

        return !isMe && !alreadySelected;
    });
});

function selectUser(user: User) {
    if (user.id === authUser.value.id) return;

    const alreadySelected = selectedUsers.value.some(
        (selected) => selected.id === user.id,
    );

    if (alreadySelected) return;

    selectedUsers.value.push(user);
    search.value = '';
    users.value = [];
}

function removeUser(userId: number) {
    selectedUsers.value = selectedUsers.value.filter((user) => user.id !== userId);
}

async function createGroup() {
    if (!canCreateGroup.value) return;

    const response = await fetch('/conversations/group', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '',
        },
        body: JSON.stringify({
            user_ids: selectedUsers.value.map(user => user.id),
        }),
    });

    const data = await response.json();

    emit('open-group-chat', data.conversation.users);
}
</script>

<template>
    <div class="relative h-full w-full">
        <!-- Header -->
        <div class="flex h-[120px] w-full flex-col justify-center gap-4">
            <div class="flex flex-wrap items-center gap-[15px] px-[23px]">
                <label class="text-[24px] font-bold text-[#442F2F]">
                    Usuarios:
                </label>

                <!-- Usuarios seleccionados -->
                <button
                    v-for="user in selectedUsers"
                    :key="user.id"
                    type="button"
                    class="flex h-[41px] w-[146px] items-center justify-center rounded-[14px] bg-[#7BEFB7] px-[13px]"
                >
                    <span class="w-[108px] truncate text-[20px] font-bold text-[#442F2F]">
                        {{ user.username }}
                    </span>

                    <img
                        src="/icons/X.svg"
                        alt="Quitar usuario"
                        class="h-[27px] w-[27px] cursor-pointer"
                        @click.stop="removeUser(user.id)"
                    />
                </button>

                <!-- Input para buscar -->
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar usuario..."
                    autocomplete="off"
                    class="h-[41px] w-[220px] bg-transparent px-2 text-[20px] font-bold text-[#442F2F] outline-none placeholder:text-[#B4C5BD]"
                />

                <!-- Botón crear grupo -->
                <button
                    v-if="canCreateGroup"
                    type="button"
                    class="flex h-[41px] w-[185px] items-center justify-center gap-2 rounded-[14px] bg-[#FF7608] px-[13px] text-[20px] font-bold text-white"
                    @click="createGroup"
                >
                    Crear grupo

                    <span class="flex h-[28px] w-[28px] items-center justify-center rounded-full bg-white text-[#FF7608]">
                        ➜
                    </span>
                </button>
            </div>

            <div class="h-[1px] w-full bg-[#FF7608]" />
        </div>

        <!-- Recomendaciones -->
        <div
            v-if="users.length > 0"
            class="absolute left-[143px] top-[88px] z-10 flex w-[202px] flex-col gap-[10px] rounded-[11px] bg-[#FFEDA5] px-[16px] py-[15px]"
        >
            <button
                v-for="user in users"
                :key="user.id"
                type="button"
                class="flex items-center gap-[16px] text-left transition hover:opacity-75"
                @click="selectUser(user)"
            >
                <div
                    class="flex h-[32px] w-[32px] shrink-0 items-center justify-center rounded-full bg-[#FF7608] outline outline-2 outline-[#FFEBC9]"
                >
                    <span class="text-[14px] font-bold text-white">
                        {{ user.username.charAt(0) }}
                    </span>
                </div>

                <span class="w-[108px] truncate text-[15px] font-bold text-[#442F2F]">
                    {{ user.username }}
                </span>
            </button>
        </div>
    </div>
</template>