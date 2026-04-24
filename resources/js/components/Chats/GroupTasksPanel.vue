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

    if (alreadyExists) return;

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

function createTask() {
    emit('create-task');
}

function openTaskModal() {
    showTaskModal.value = true;
}

function closeTaskModal() {
    showTaskModal.value = false;
}



</script>

<template>
    <div
        class="relative h-[769px] w-[317px] rounded-[15px] bg-[#FDF0D9] shadow-[0px_4px_4px_rgba(0,0,0,0.25)]"
    >
        <!-- Header -->
        <div
            class="absolute left-[17px] top-[20px] flex w-[283px] items-center justify-center gap-[74px]"
        >
            <h2 class="w-[184px] text-[20px] font-bold text-[#FF7608]">
                Tareas grupales
            </h2>

            <!-- Chevron left -->
            <button @click="goBack">
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
            class="absolute left-[17px] top-[77px] flex h-[33px] items-center gap-[13px] whitespace-nowrap transition hover:opacity-80"
        >
            <img
                src="/icons/Plus circle2.svg"
                alt="Añadir tarea"
                class="h-[30px] w-[30px] shrink-0"
            />

            <span class="w-[184px] text-left font-['Nunito_Sans'] text-[20px] font-bold text-[#442F2F]">
                Añadir nueva tarea
            </span>
        </button>

        <div
            v-if="tasks.length > 0"
            class="absolute left-[17px] top-[130px] flex w-[285px] flex-col items-start gap-[28px]"
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

                <span class="w-[236px] font-['Nunito_Sans'] text-[20px] font-bold text-[#EBC119]">
                    {{ task.text }}
                </span>
            </button>
        </div>

        <!-- LISTA DE TAREAS -->
        <div
            v-if="tasks.length > 0"
            class="absolute left-[17px] top-[130px] flex w-[285px] flex-col items-start gap-[28px]"
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
                    class="h-[33px] w-[33px] shrink-0"
                />

                <span class="w-[236px] font-['Nunito_Sans'] text-[20px] font-bold text-[#EBC119]">
                    {{ task.text }}
                </span>
            </button>
        </div>

        <!-- ESTADO VACÍO -->
        <div
            v-else
            class="absolute left-[45px] top-[279px] flex w-[228px] flex-col items-center gap-[17px]"
        >
            <img
                src="/images/nofound2.png"
                class="h-[147px] w-[223px] object-contain"
            />

            <p class="w-[224px] text-center text-[20px] font-bold text-[#B4C5BD]">
                ¡No hay información que mostrar!
            </p>
        </div>

        <TaskOptionsModal
            v-if="showTaskModal"
            @close="showTaskModal = false"
            @select-task="addTask"
        />

    </div>
</template>