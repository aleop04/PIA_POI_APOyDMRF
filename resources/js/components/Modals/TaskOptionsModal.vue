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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
    >
        <!-- Modal -->
        <div
            class="relative max-h-[85vh] w-full max-w-[683px] overflow-y-auto rounded-[32px] bg-[#FFFBED] px-5 py-6 shadow-[0px_15px_4px_rgba(0,0,0,0.25)] md:h-[664px] md:w-[683px] md:rounded-[65px] md:px-[65px] md:pt-[17px] md:pb-[57px]"
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
                <button @click="close" class="shrink-0">
                    <img
                        src="/icons/X circle.svg"
                        alt="Cerrar"
                        class="h-[28px] w-[28px] md:h-[35px] md:w-[35px]"
                    />
                </button>
            </div>

            <!-- Línea -->
            <div class="mt-5 h-[1px] w-full bg-[#442F2F] md:mt-[21px]" />

            <!-- Lista de tareas -->
            <div
                class="mt-6 flex max-h-[55vh] flex-col gap-5 overflow-y-auto pr-1 scrollbar-thin md:mt-[46px] md:max-h-[420px] md:gap-[36px] md:pr-2"
            >

                <!-- Tarea 1 -->
                <div class="flex items-center justify-between gap-4">
                    <p class="flex-1 text-[16px] font-bold text-[#AF6123] md:w-[363px] md:flex-none md:text-[24px]">
                        10 puntos x 3 reseñas en distintas publicaciones
                    </p>

                    <button
                        @click="selectTask('reseñas-3', '10 puntos x 3 reseñas en distintas publicaciones')"
                        class="h-[36px] w-[86px] shrink-0 rounded-[50px] bg-[#FF0808] text-[14px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:opacity-80 md:h-[42px] md:w-[99px] md:text-[16px]"
                    >
                        Agregar
                    </button>
                </div>

                <!-- Tarea 2 -->
                <div class="flex items-center justify-between gap-4">
                    <p class="flex-1 text-[16px] font-bold text-[#AF6123] md:w-[363px] md:flex-none md:text-[24px]">
                        20 puntos x 6 reseñas en distintas publicaciones
                    </p>

                    <button
                        @click="selectTask('reseñas-6', '20 puntos x 6 reseñas en distintas publicaciones')"
                        class="h-[36px] w-[86px] shrink-0 rounded-[50px] bg-[#FF0808] text-[14px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:opacity-80 md:h-[42px] md:w-[99px] md:text-[16px]"
                    >
                        Agregar
                    </button>
                </div>

                <!-- Tarea 3 -->
                <div class="flex items-center justify-between gap-4">
                    <p class="flex-1 text-[16px] font-bold text-[#AF6123] md:w-[363px] md:flex-none md:text-[24px]">
                        30 puntos x 1 publicación nueva
                    </p>

                    <button
                        @click="selectTask('post-1', '30 puntos x 1 publicación nueva')"
                        class="h-[36px] w-[86px] shrink-0 rounded-[50px] bg-[#FF0808] text-[14px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:opacity-80 md:h-[42px] md:w-[99px] md:text-[16px]"
                    >
                        Agregar
                    </button>
                </div>

                <!-- Tarea 4 -->
                <div class="flex items-center justify-between gap-4">
                    <p class="flex-1 text-[16px] font-bold text-[#AF6123] md:w-[363px] md:flex-none md:text-[24px]">
                        40 puntos x enviar 10 mensajes en el chat grupal
                    </p>

                    <button
                        @click="selectTask('mensajes-10', '40 puntos x enviar 10 mensajes en el chat grupal')"
                        class="h-[36px] w-[86px] shrink-0 rounded-[50px] bg-[#FF0808] text-[14px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:opacity-80 md:h-[42px] md:w-[99px] md:text-[16px]"
                    >
                        Agregar
                    </button>
                </div>

                <!-- Tarea 5 -->
                <div class="flex items-center justify-between gap-4">
                    <p class="flex-1 text-[16px] font-bold text-[#AF6123] md:w-[363px] md:flex-none md:text-[24px]">
                        50 puntos x 2 publicaciones nuevas
                    </p>

                    <button
                        @click="selectTask('post-2', '50 puntos x 2 publicaciones nuevas')"
                        class="h-[36px] w-[86px] shrink-0 rounded-[50px] bg-[#FF0808] text-[14px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:opacity-80 md:h-[42px] md:w-[99px] md:text-[16px]"
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