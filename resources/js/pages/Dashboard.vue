<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';

const slides = [
    {
        src: '/images/1.jpg',
        title: 'Bienvenido a Destinario',
        text: 'Explora nuevos lugares, experiencias y comunidades.',
    },
    {
        src: '/images/2.jpg',
        title: 'Planea tus aventuras',
        text: 'Organiza destinos y actividades fácilmente.',
    },
    {
        src: '/images/3.jpg',
        title: 'Conecta con otros viajeros',
        text: 'Comparte experiencias únicas.',
    },
    {
        src: '/images/4.jpg',
        title: 'Descubre nuevos destinos',
        text: 'Encuentra lugares ideales para tu próxima aventura.',
    },
    {
        src: '/images/5.jpg',
        title: 'Comparte tus experiencias',
        text: 'Publica momentos y recomendaciones con la comunidad.',
    },
    {
        src: '/images/6.jpg',
        title: 'Viaja mejor acompañado',
        text: 'Organiza planes y mantente conectado con otros viajeros.',
    },
];

const currentSlide = ref(0);
let interval: number | undefined;

function nextSlide() {
    currentSlide.value = (currentSlide.value + 1) % slides.length;
}

function prevSlide() {
    currentSlide.value =
        currentSlide.value === 0 ? slides.length - 1 : currentSlide.value - 1;
}

onMounted(() => {
    interval = window.setInterval(nextSlide, 6000);
});

onBeforeUnmount(() => {
    clearInterval(interval);
});
</script>

<template>
    <Head title="Destinario" />

    <section class="min-h-screen bg-[#FFF7EA] p-8">
        <div class="relative h-[430px] w-full overflow-hidden rounded-[28px] shadow-[0px_4px_12px_rgba(0,0,0,0.25)]">
            <div
                v-for="(slide, index) in slides"
                :key="slide.src"
                class="absolute inset-0 transition-opacity duration-700"
                :class="index === currentSlide ? 'opacity-100 z-10' : 'opacity-0 z-0'"
            >
                <img
                    :src="slide.src"
                    :alt="slide.title"
                    class="h-full w-full object-cover"
                    loading="eager"
                />

                <div class="absolute inset-0 bg-black/35"></div>

                <div class="absolute bottom-12 left-12 max-w-[560px]">
                    <h1 class="font-['Nunito_Sans'] text-[44px] font-bold text-[#FF7608]">
                        {{ slide.title }}
                    </h1>

                    <p class="mt-3 font-['Nunito_Sans'] text-[20px] font-semibold text-white/90">
                        {{ slide.text }}
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="prevSlide"
                class="absolute left-5 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full text-[32px] font-bold leading-none text-[#442F2F] hover:bg-white"
            >
                ‹
            </button>

            <button
                type="button"
                @click="nextSlide"
                class="absolute right-5 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full text-[32px] font-bold leading-none text-[#442F2F] hover:bg-white"
            >
                ›
            </button>

            <div class="absolute bottom-5 left-1/2 z-10 flex -translate-x-1/2 gap-2">
                <button
                    v-for="(_, index) in slides"
                    :key="index"
                    type="button"
                    @click="currentSlide = index"
                    class="h-2 w-2 rounded-full"
                    :class="index === currentSlide ? 'bg-[#FF7608]' : 'bg-white/70'"
                ></button>
            </div>
        </div>

        <div class="mt-8">
            <!-- aquí después puedes poner publicaciones, tarjetas, recomendaciones, etc. -->
        </div>
    </section>
</template>