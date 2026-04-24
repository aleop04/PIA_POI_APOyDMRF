<script setup lang="ts">

type GroupTask = {
    id: string;
    text: string;
    completed: boolean;
};

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'select-task', task: GroupTask): void;
}>();

function close() {
    emit('close');
}

function selectTask(id: string, text: string) {
    emit('select-task', {
        id,
        text,
        completed: false,
    });
}

</script>

<template>
    <!-- Fondo oscuro -->
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    >
        <!-- Modal -->
        <div
            class="relative h-[664px] w-[683px] rounded-[65px] bg-[#FFFBED] px-[65px] pt-[17px] pb-[57px] shadow-[0px_15px_4px_rgba(0,0,0,0.25)]"
        >
            <!-- Header -->
            <div class="flex items-start justify-center gap-[28px]">
                <h2 class="w-[492px] text-center font-['Nunito_Sans'] text-[24px] font-bold">
                    <span class="text-[#0E6C3F]">
                        ¡Bienvenido/a al panel de tareas!
                    </span>
                    <br /><br />
                    <span class="text-[#442F2F]">
                        Cada miembro del grupo deberá cumplir con las tareas para recibir las recompensas
                    </span>
                </h2>

                <!-- Botón cerrar -->
                <button @click="close">
                    <img
                        src="/icons/X circle.svg"
                        alt="Cerrar"
                        class="h-[35px] w-[35px]"
                    />
                </button>
            </div>

            <!-- Línea -->
            <div class="mt-[21px] h-[1px] w-full bg-[#442F2F]" />

            <!-- Lista de tareas -->
            <div class="mt-[46px] flex max-h-[420px] flex-col gap-[36px] overflow-y-auto pr-2 scrollbar-thin">

                <!-- Tarea 1 -->
                <div class="flex items-center gap-[92px]">
                    <p class="w-[363px] text-[24px] font-bold text-[#AF6123]">
                        10 puntos x 3 reseñas en distintas publicaciones
                    </p>

                    <button
                        @click="selectTask('reseñas-3', '10 puntos x 3 reseñas en distintas publicaciones')"
                        class="h-[42px] w-[99px] rounded-[50px] bg-[#FF0808] text-[16px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:opacity-80"
                    >
                        Agregar
                    </button>
                </div>

                <!-- Tarea 2 -->
                <div class="flex items-center gap-[92px]">
                    <p class="w-[363px] text-[24px] font-bold text-[#AF6123]">
                        20 puntos x 6 reseñas en distintas publicaciones
                    </p>

                    <button
                        @click="selectTask('reseñas-6', '20 puntos x 6 reseñas en distintas publicaciones')"
                        class="h-[42px] w-[99px] rounded-[50px] bg-[#FF0808] text-[16px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:opacity-80"
                    >
                        Agregar
                    </button>
                </div>

                <!-- Tarea 3 -->
                <div class="flex items-center gap-[92px]">
                    <p class="w-[363px] text-[24px] font-bold text-[#AF6123]">
                        30 puntos x 1 publicación nueva
                    </p>

                    <button
                        @click="selectTask('post-1', '30 puntos x 1 publicación nueva')"
                        class="h-[42px] w-[99px] rounded-[50px] bg-[#FF0808] text-[16px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:opacity-80"
                    >
                        Agregar
                    </button>
                </div>

                <!-- Tarea 4 -->
                <div class="flex items-center gap-[92px]">
                    <p class="w-[363px] text-[24px] font-bold text-[#AF6123]">
                        40 puntos x enviar 10 mensajes en el chat grupal
                    </p>

                    <button
                        @click="selectTask('mensajes-10', '40 puntos x enviar 10 mensajes en el chat grupal')"
                        class="h-[42px] w-[99px] rounded-[50px] bg-[#FF0808] text-[16px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:opacity-80"
                    >
                        Agregar
                    </button>
                </div>

                <!-- Tarea 5 -->
                <div class="flex items-center gap-[92px]">
                    <p class="w-[363px] text-[24px] font-bold text-[#AF6123]">
                        50 puntos x 2 publicaciones nuevas
                    </p>

                    <button
                        @click="selectTask('post-2', '50 puntos x 2 publicaciones nuevas')"
                        class="h-[42px] w-[99px] rounded-[50px] bg-[#FF0808] text-[16px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:opacity-80"
                    >
                        Agregar
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