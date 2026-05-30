<script setup lang="ts">
import { onMounted, ref } from 'vue';
import TaskOptionsModal from '@/components/Modals/TaskOptionsModal.vue';

const props = defineProps<{
    conversationId: number;
}>();

const emit = defineEmits<{
    (e: 'back'): void;
}>();

type TaskCreator = {
    id: number;
    username: string;
    profile_photo: string | null;
};

type GroupTask = {
    id: number;
    description: string;
    type: 'comment_and_review' | 'publish' | 'send_messages';
    required_amount: number;
    points: number;
    created_at: string;

    creator: TaskCreator | null;

    progress: number;
    completed_at: string | null;
    claimed_at: string | null;

    members_count: number;
    completed_count: number;
    is_group_completed: boolean;
};

const showTaskModal = ref(false);
const tasks = ref<GroupTask[]>([]);
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

function goBack() {
    emit('back');
}

function csrfToken() {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';
}

async function loadTasks() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const res = await fetch(`/chats/${props.conversationId}/tasks`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!res.ok) {
            throw new Error('No se pudieron cargar las tareas grupales.');
        }

        tasks.value = await res.json();
    } catch (error) {
        console.error(error);
        errorMessage.value = 'No se pudieron cargar las tareas grupales.';
    } finally {
        loading.value = false;
    }
}

async function handleTaskCreated() {
    showTaskModal.value = false;
    successMessage.value = 'Tarea agregada correctamente.';

    await loadTasks();

    setTimeout(() => {
        successMessage.value = '';
    }, 2500);
}

function taskStatusText(task: GroupTask) {
    if (task.is_group_completed) {
        return 'Completada por todo el grupo';
    }

    if (task.completed_at) {
        return 'Tú ya completaste tu parte';
    }

    return `Tu progreso: ${task.progress}/${task.required_amount}`;
}

function taskIcon(task: GroupTask) {
    if (task.is_group_completed || task.completed_at) {
        return '/icons/Check circle.svg';
    }

    return '/icons/Circle.svg';
}

function taskTypeText(type: GroupTask['type']) {
    if (type === 'comment_and_review') {
        return 'Reseñas';
    }

    if (type === 'publish') {
        return 'Publicaciones';
    }

    return 'Mensajes';
}

onMounted(() => {
    loadTasks();
});
</script>

<template>
    <div class="relative flex h-full w-full flex-col items-center px-[28px] py-[38px]">
        <div class="w-full max-w-[420px]">
            <!-- Header -->
            <div class="flex w-full items-center justify-between">
                <h2 class="text-[20px] font-bold text-[#FF7608]">
                    Tareas grupales
                </h2>

                <button type="button" @click="goBack">
                    <img
                        src="/icons/Chevron left.svg"
                        alt="Volver"
                        class="h-[25px] w-[25px]"
                    />
                </button>
            </div>

            <!-- Botón añadir tarea -->
            <button
                type="button"
                @click="showTaskModal = true"
                class="mt-[32px] flex items-center gap-[13px] whitespace-nowrap transition hover:opacity-80"
            >
                <img
                    src="/icons/Plus circle2.svg"
                    alt="Añadir tarea"
                    class="h-[30px] w-[30px] shrink-0"
                />

                <span class="text-left font-['Nunito_Sans'] text-[20px] font-bold text-[#442F2F]">
                    Añadir nueva tarea
                </span>
            </button>

            <!-- Mensajes -->
            <p
                v-if="successMessage"
                class="mt-4 rounded-[14px] bg-[#DFF5E8] px-4 py-2 text-center text-[14px] font-bold text-[#0E6C3F]"
            >
                {{ successMessage }}
            </p>

            <p
                v-if="errorMessage"
                class="mt-4 rounded-[14px] bg-red-100 px-4 py-2 text-center text-[14px] font-bold text-red-700"
            >
                {{ errorMessage }}
            </p>

            <p
                v-if="loading"
                class="mt-[80px] text-center text-[18px] font-bold text-[#B4C5BD]"
            >
                Cargando tareas...
            </p>

            <!-- LISTA DE TAREAS -->
            <div
                v-else-if="tasks.length > 0"
                class="mt-[28px] flex max-h-[500px] w-full flex-col items-start gap-[22px] overflow-y-auto pr-1 scrollbar-thin"
            >
                <article
                    v-for="task in tasks"
                    :key="task.id"
                    class="flex w-full items-start gap-[13px] rounded-[22px] bg-[#FDF0D9] p-3 text-left"
                >
                    <img
                        :src="taskIcon(task)"
                        alt="Estado tarea"
                        class="mt-1 h-[33px] w-[33px] shrink-0"
                    />

                    <div class="min-w-0 flex-1">
                        <p class="break-words font-['Nunito_Sans'] text-[18px] font-bold text-[#EBC119]">
                            {{ task.points }} puntos x {{ task.description }}
                        </p>

                        <p class="mt-1 text-[13px] font-bold text-[#AF6123]">
                            {{ taskTypeText(task.type) }}
                        </p>

                        <p class="mt-2 text-[14px] font-bold text-[#442F2F]">
                            {{ taskStatusText(task) }}
                        </p>

                        <p class="mt-1 text-[13px] font-semibold text-[#6F5757]">
                            Grupo: {{ task.completed_count }}/{{ task.members_count }} miembros completaron
                        </p>
                    </div>
                </article>
            </div>

            <!-- ESTADO VACÍO -->
            <div
                v-else
                class="mt-[160px] flex w-full flex-col items-center gap-[17px]"
            >
                <img
                    src="/images/nofound2.png"
                    class="h-[147px] w-[223px] object-contain"
                />

                <p class="w-[224px] text-center text-[20px] font-bold text-[#B4C5BD]">
                    ¡No hay información que mostrar!
                </p>
            </div>
        </div>

        <TaskOptionsModal
            v-if="showTaskModal"
            :conversation-id="conversationId"
            @close="showTaskModal = false"
            @task-created="handleTaskCreated"
        />
    </div>
</template>

<style scoped>
.scrollbar-thin {
    scrollbar-width: none;
}

.scrollbar-thin::-webkit-scrollbar {
    display: none;
}
</style>