<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const busqueda = ref('');

const buscar = () => {
    const q = busqueda.value.trim();

    if (!q) return;

    router.get('/buscar', { q });
};

</script>

<template>
    <nav
        class="fixed left-0 top-0 z-50 h-[125px] w-full bg-[#FF7608] shadow-[0px_3px_5.3px_rgba(0,0,0,0.25)]"
    >
        <div class="mx-auto flex h-full max-w-[1817px] items-center justify-between gap-[63px] px-6">
            <!-- Logos -->
            <Link href="/dashboard" class="flex shrink-0 items-center">
                <img 
                    src="/images/destinariologo3.png" 
                    class="relative left-[-16px] h-[60px] w-auto" 
                />
                <img 
                    src="/images/destinariologo1.png" 
                    class="h-[84px] w-auto" 
                />
            </Link>

            <!-- Buscador -->
            <form
                @submit.prevent="buscar"
                class="flex h-[34px] w-[1020px] items-center rounded-[9px] border border-[#FFEBC9] px-[10px]"
            >
                <input
                    v-model="busqueda"
                    type="text"
                    placeholder="Buscar..."
                    class="h-full flex-1 bg-transparent text-white outline-none placeholder:text-[#FFEBC9]"
                />

                <button type="submit" class="flex items-center justify-center">
                    <img src="/icons/search.svg" class="h-4 w-4" />
                </button>
            </form>

            <!-- Iconos -->
            <div class="flex shrink-0 items-center gap-[30px]">

                <Link href="/publicaciones/crear">
                    <img src="/icons/create-post.svg" class="h-[46px] w-[46px]" />
                </Link>

                <Link href="/chats">
                    <img src="/icons/messages.svg" class="h-[46px] w-[46px]" />
                </Link>

                <Link href="/perfil">
                    <img src="/icons/profile.svg" class="h-[56px] w-[56px] rounded-full" />
                </Link>
            </div>
        </div>
    </nav>
</template>