<script setup lang="ts">
import { computed, ref } from 'vue';
import GroupTasksPanel from '@/components/Chats/GroupTasksPanel.vue';
import ChangeChatNameModal from '@/components/Modals/ChangeChatNameModal.vue';

type ChatUser = {
    id: number;
    first_name: string;
    last_name: string;
    username: string;
    last_seen_at: string | null;
};

const props = defineProps<{
    user: ChatUser | null;
    groupUsers?: ChatUser[];

    conversation: {
        id: number;
        type: 'private' | 'group';
        name: string | null;
    } | null;
}>();

const showMembers = ref(false);
const showCustomize = ref(false);
const showTasksPanel = ref(false);

const chatTitle = computed(() => {
    // chat grupal
    if (props.groupUsers && props.groupUsers.length > 0) {
        return props.conversation?.name
            || props.groupUsers.map(user => user.username).join(', ');
    }

    // chat privado
    return props.user ? props.user.username : '';
});

function toggleMembers() {
    showMembers.value = !showMembers.value;
}

function toggleCustomize() {
    showCustomize.value = !showCustomize.value;
}

function openTasksPanel() {
    showTasksPanel.value = true;
}

const showRenameModal = ref(false);
const newGroupName = ref('');

const fileInputRef = ref<HTMLInputElement | null>(null);

function goToProfile(userId: number) {
    window.location.href = `/profile/${userId}`;
}

function openRenameModal() {
  showRenameModal.value = true;
}

function closeRenameModal() {
    showRenameModal.value = false;
}

async function saveGroupName(name: string) {
  if (!props.conversation) return;

  await fetch(`/conversations/${props.conversation.id}/name`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '',
    },
    body: JSON.stringify({ name }),
  });

  if (props.conversation) {
    props.conversation.name = name;
  }

  showRenameModal.value = false;
}

function openPhotoSelector() {
    fileInputRef.value?.click();
}

async function changeGroupPhoto(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file || !props.conversation) return;

    const formData = new FormData();
    formData.append('photo', file);

    await fetch(`/conversations/${props.conversation.id}/photo`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '',
        },
        body: formData,
    });
}

</script>

<template>
    <aside
        class="relative h-[769px] w-[317px] shrink-0 rounded-[15px] bg-[#FDF0D9] shadow-[0px_4px_4px_rgba(0,0,0,0.25)]"
    >
        <!-- Panel principal -->
        <div
            v-if="!showTasksPanel"
            class="absolute left-[18px] top-[31px] flex w-[281px] flex-col items-center gap-[70px]"
        >
            <!-- Header -->
            <button
                type="button"
                class="flex w-[220px] flex-col items-center justify-center gap-[9px]"
                @click="user && goToProfile(user.id)"
            >
                <div
                    class="flex h-[71px] w-[71px] items-center justify-center rounded-full bg-[#FF7608] outline outline-2 outline-[#FFEBC9]"
                >
                    <img src="/icons/User.svg" alt="Usuario" class="h-[48px] w-[48px]" />
                </div>

                <h2
                    class="w-[220px] truncate text-center font-['Nunito_Sans'] text-[24px] font-bold text-[#442F2F]"
                >
                    {{ chatTitle }}
                </h2>
            </button>

            <!-- Opciones -->
            <div class="flex w-full flex-col items-start gap-5">
                <!-- Miembros -->
                <button
                    type="button"
                    class="flex h-[25px] w-full items-start justify-center gap-[74px]"
                    @click="toggleMembers"
                >
                    <span class="h-[25px] w-[184px] text-left font-['Nunito_Sans'] text-[20px] font-bold text-[#FF7608]">
                        Miembros del chat
                    </span>

                    <img
                        :src="showMembers ? '/icons/Chevron up.svg' : '/icons/Chevron down.svg'"
                        alt="Abrir miembros"
                        class="h-[25px] w-[25px]"
                    />
                </button>

                <div v-if="showMembers" class="flex flex-col gap-[17px]">

                    <<button
                        v-for="member in groupUsers"
                        :key="member.id"
                        type="button"
                        class="flex items-center gap-[13px] text-left"
                        @click="goToProfile(member.id)"
                    >
                        <div
                            class="flex h-[32px] w-[32px] items-center justify-center rounded-full bg-[#FF7608] outline outline-2 outline-[#FFEBC9]"
                        >
                            <img
                                src="/icons/User2.svg"
                                alt="Usuario"
                                class="h-[20px] w-[20px]"
                            />
                        </div>

                        <span class="text-[20px] font-bold text-[#442F2F]">
                            {{ member.username }}
                        </span>
                    </button>

                </div>

                <!-- Personalizar -->
                <button
                    type="button"
                    class="flex h-[25px] w-full items-center justify-center gap-[74px]"
                    @click="toggleCustomize"
                >
                    <span class="h-[25px] w-[184px] text-left font-['Nunito_Sans'] text-[20px] font-bold text-[#FF7608]">
                        Personalizar el chat
                    </span>

                    <img
                        :src="showCustomize ? '/icons/Chevron up.svg' : '/icons/Chevron down.svg'"
                        alt="Abrir personalización"
                        class="h-[25px] w-[25px]"
                    />
                </button>

                <div v-if="showCustomize" class="mt-8 flex w-full flex-col gap-[7px]">
                    <!-- Cambiar nombre -->
                    <button @click="openRenameModal">
                        <img
                            src="/icons/Edit2.svg"
                            alt="Cambiar nombre"
                            class="h-[32px] w-[32px] shrink-0"
                        />

                        <span class="font-['Nunito_Sans'] text-[20px] font-bold text-[#442F2F]">
                            Cambiar nombre del chat
                        </span>
                    </button>

                    <!-- Cambiar foto -->
                    <button
                        type="button"
                        class="flex items-center gap-[7px] text-left transition hover:opacity-80"
                        @click="openPhotoSelector"
                    >

                    <input
                        ref="fileInputRef"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="changeGroupPhoto"
                    />

                        <img
                            src="/icons/Image.svg"
                            alt="Cambiar foto"
                            class="h-[32px] w-[32px] shrink-0"
                        />

                        <span class="font-['Nunito_Sans'] text-[20px] font-bold text-[#442F2F]">
                            Cambiar foto de grupo
                        </span>
                    </button>
                </div>

                <!-- Tareas grupales -->
                <button
                    type="button"
                    class="flex h-[25px] w-full pt-[34px] items-center justify-center gap-[74px]"
                    @click="openTasksPanel"
                >
                    <span class="h-[25px] w-[184px] text-left font-['Nunito_Sans'] text-[20px] font-bold text-[#FF7608]">
                        Tareas grupales
                    </span>

                    <img
                        src="/icons/Chevron right.svg"
                        alt="Abrir tareas"
                        class="h-[25px] w-[25px]"
                    />
                </button>
            </div>
        </div>

        <!-- Panel de tareas -->
        <GroupTasksPanel
            v-else
            @back="showTasksPanel = false"
        />

        <ChangeChatNameModal
            :open="showRenameModal"
            :initial-name="conversation?.name ?? null"
            @close="closeRenameModal"
            @save="saveGroupName"
        />
    </aside>
</template>