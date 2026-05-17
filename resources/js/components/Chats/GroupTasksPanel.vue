<script setup lang="ts">
import { ref } from 'vue';
import TaskOptionsModal from '@/components/Modals/TaskOptionsModal.vue';

const emit = defineEmits<{
    (e: 'back'): void;
    (e: 'create-task'): void;
}>();

type GroupTask = {
    id: string;
    text: string;
    completed: boolean;
};

const showTaskModal = ref(false);
const tasks = ref<GroupTask[]>([]);

function addTask(task: GroupTask) {
    const alreadyExists = tasks.value.some((item) => item.id === task.id);

    if (alreadyExists) {
return;
}

    tasks.value.push(task);
    showTaskModal.value = false;
}

function toggleTask(taskId: string) {
    const task = tasks.value.find((item) => item.id === taskId);

    if (task) {
        task.completed = !task.completed;
    }
}

function goBack() {
    emit('back');
}

//function createTask() {
    emit('create-task');
//}

//function openTaskModal() {
    showTaskModal.value = true;
//}

//function closeTaskModal() {
    showTaskModal.value = false;
//}



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

            <!-- LISTA DE TAREAS -->
            <div
                v-if="tasks.length > 0"
                class="mt-[28px] flex w-full flex-col items-start gap-[28px]"
            >
                <button
                    v-for="task in tasks"
                    :key="task.id"
                    type="button"
                    class="flex w-full items-start gap-[13px] text-left"
                    @click="toggleTask(task.id)"
                >
                    <img
                        :src="task.completed ? '/icons/Check circle.svg' : '/icons/Circle.svg'"
                        alt="Estado tarea"
                        class="h-[33px] w-[33px] shrink-0"
                    />

                    <span class="min-w-0 flex-1 break-words font-['Nunito_Sans'] text-[20px] font-bold text-[#EBC119]">
                        {{ task.text }}
                    </span>
                </button>
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
            @close="showTaskModal = false"
            @select-task="addTask"
        />
    </div>
</template>