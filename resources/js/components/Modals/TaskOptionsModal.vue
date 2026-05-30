<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{
    conversationId: number;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'task-created'): void;
}>();

type TaskOption = {
    label: string;
    description: string;
    type: 'comment_and_review' | 'publish' | 'send_messages';
    required_amount: number;
    points: number;
};

const creating = ref(false);
const errorMessage = ref('');

const taskOptions: TaskOption[] = [
    {
        label: '10 puntos x 3 reseñas en distintas publicaciones',
        description: '3 reseñas en distintas publicaciones',
        type: 'comment_and_review',
        required_amount: 3,
        points: 10,
    },
    {
        label: '20 puntos x 6 reseñas en distintas publicaciones',
        description: '6 reseñas en distintas publicaciones',
        type: 'comment_and_review',
        required_amount: 6,
        points: 20,
    },
    {
        label: '30 puntos x 1 publicación nueva',
        description: '1 publicación nueva',
        type: 'publish',
        required_amount: 1,
        points: 30,
    },
    {
        label: '40 puntos x enviar 10 mensajes en el chat grupal',
        description: 'enviar 10 mensajes en el chat grupal',
        type: 'send_messages',
        required_amount: 10,
        points: 40,
    },
    {
        label: '50 puntos x 2 publicaciones nuevas',
        description: '2 publicaciones nuevas',
        type: 'publish',
        required_amount: 2,
        points: 50,
    },
];

function close() {
    if (creating.value) {
        return;
    }

    emit('close');
}

function csrfToken() {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';
}

async function selectTask(task: TaskOption) {
    if (creating.value) {
        return;
    }

    creating.value = true;
    errorMessage.value = '';

    try {
        const res = await fetch(`/chats/${props.conversationId}/tasks`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({
                description: task.description,
                type: task.type,
                required_amount: task.required_amount,
                points: task.points,
            }),
        });

        if (!res.ok) {
            const data = await res.json().catch(() => null);

            throw new Error(
                data?.message ?? 'No se pudo crear la tarea grupal.',
            );
        }

        emit('task-created');
    } catch (error) {
        console.error(error);
        errorMessage.value = error instanceof Error
            ? error.message
            : 'No se pudo crear la tarea grupal.';
    } finally {
        creating.value = false;
    }
}
</script>

<template>
    <!-- Fondo oscuro -->
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
    >
        <!-- Modal -->
        <div
            class="scrollbar-thin relative max-h-[85vh] w-full max-w-[683px] overflow-y-auto rounded-[32px] bg-[#FFFBED] px-5 py-6 shadow-[0px_15px_4px_rgba(0,0,0,0.25)] md:h-[664px] md:w-[683px] md:rounded-[65px] md:px-[65px] md:pt-[17px] md:pb-[57px]"
        >
            <!-- Header -->
            <div class="flex items-start justify-between gap-3 md:justify-center md:gap-[28px]">
                <h2
                    class="flex-1 text-center font-['Nunito_Sans'] text-[17px] font-bold md:w-[492px] md:flex-none md:text-[24px]"
                >
                    <span class="text-[#0E6C3F]">
                        ¡Bienvenido/a al panel de tareas!
                    </span>

                    <br /><br />

                    <span class="text-[#442F2F]">
                        Cada miembro del grupo deberá cumplir con las tareas para recibir las recompensas
                    </span>
                </h2>

                <!-- Botón cerrar -->
                <button
                    type="button"
                    class="shrink-0"
                    :disabled="creating"
                    @click="close"
                >
                    <img
                        src="/icons/X circle.svg"
                        alt="Cerrar"
                        class="h-[28px] w-[28px] md:h-[35px] md:w-[35px]"
                    />
                </button>
            </div>

            <!-- Línea -->
            <div class="mt-5 h-[1px] w-full bg-[#442F2F] md:mt-[21px]" />

            <p
                v-if="errorMessage"
                class="mt-4 rounded-[14px] bg-red-100 px-4 py-2 text-center text-[14px] font-bold text-red-700"
            >
                {{ errorMessage }}
            </p>

            <!-- Lista de tareas -->
            <div
                class="mt-6 flex max-h-[55vh] flex-col gap-5 overflow-y-auto pr-1 scrollbar-thin md:mt-[46px] md:max-h-[420px] md:gap-[36px] md:pr-2"
            >
                <div
                    v-for="task in taskOptions"
                    :key="task.label"
                    class="flex items-center justify-between gap-4"
                >
                    <p class="flex-1 text-[16px] font-bold text-[#AF6123] md:w-[363px] md:flex-none md:text-[24px]">
                        {{ task.label }}
                    </p>

                    <button
                        type="button"
                        :disabled="creating"
                        @click="selectTask(task)"
                        class="h-[36px] w-[86px] shrink-0 rounded-[50px] bg-[#FF0808] text-[14px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:opacity-80 disabled:cursor-not-allowed disabled:opacity-60 md:h-[42px] md:w-[99px] md:text-[16px]"
                    >
                        {{ creating ? '...' : 'Agregar' }}
                    </button>
                </div>
            </div>
        </div>
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