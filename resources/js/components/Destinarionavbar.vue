<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AddressAutocomplete from '@/components/AddressAutocomplete.vue';
import UserAvatar from '@/components/UserAvatar.vue';

const page = usePage();

const authUser = page.props.auth.user as unknown as {
    profile_photo: string | null;
};

watch(
    () => page.url,
    (newUrl, oldUrl) => {
        const antesEraBusqueda = oldUrl?.startsWith('/buscar');
        const ahoraEsBusqueda = newUrl.startsWith('/buscar');

        // Solo limpiar cuando SALES de la pantalla de búsqueda
        if (antesEraBusqueda && !ahoraEsBusqueda) {
            busqueda.value = '';
        }
    }
);

const busqueda = ref('');

const manejarSeleccion = (location: any) => {
    const direccion = location.formatted_address ?? '';

    busqueda.value = direccion;

    router.get('/buscar', {
        q: direccion,
        lat: location.lat,
        lng: location.lng,
        place_id: location.place_id,
    });
};

const buscarManual = () => {
    const q = busqueda.value.trim();

    if (!q) {
        return;
    }

    router.get('/buscar', {
        q,
        lat: null,
        lng: null,
        place_id: null,
    });
};
</script>

<template>
    <nav class="fixed left-0 top-0 z-50 w-full bg-[#FF7608] shadow-[0px_3px_5.3px_rgba(0,0,0,0.25)]">
        <div
            class="mx-auto flex min-h-[90px] w-full max-w-[1817px] flex-col gap-3 px-4 py-3 md:h-[125px] md:flex-row md:items-center md:justify-between md:gap-[63px] md:px-6 md:py-0"
        >
            <!-- Logo -->
            <Link href="/dashboard" class="flex shrink-0 items-center justify-center md:justify-start">
                <img
                    src="/images/destinariologo3.png"
                    class="relative left-[-8px] h-[38px] w-auto md:left-[-16px] md:h-[60px]"
                />
                <img
                    src="/images/destinariologo1.png"
                    class="h-[52px] w-auto md:h-[84px]"
                />
            </Link>

            <!-- Buscador -->
            <form
                @submit.prevent="buscarManual"
                class="flex h-[34px] w-full min-w-0 items-center rounded-[9px] border border-[#FFEBC9] px-[10px] md:w-[1020px]"
            >
                <AddressAutocomplete
                    v-model="busqueda"
                    @location-selected="manejarSeleccion"
                    class="h-full min-w-0 flex-1 bg-transparent text-white outline-none placeholder:text-[#FFEBC9]"
                    placeholder="Buscar publicaciones o direcciones..."
                />

                <button type="submit" class="ml-2 flex shrink-0 items-center justify-center">
                    <img src="/icons/search.svg" class="h-4 w-4" />
                </button>
            </form>

            <!-- Iconos -->
            <div class="flex shrink-0 items-center justify-center gap-6 md:gap-[30px]">
                <Link href="/publicaciones/crear">
                    <img src="/icons/create-post.svg" class="h-[32px] w-[32px] md:h-[40px] md:w-[40px]" />
                </Link>

                <Link href="/chats">
                    <img src="/icons/messages.svg" class="h-[32px] w-[32px] md:h-[40px] md:w-[40px]" />
                </Link>

                <Link href="/perfil">
                    <UserAvatar
                        :photo="authUser.profile_photo"
                        className="h-[40px] w-[40px] md:h-[50px] md:w-[50px]"
                    />
                </Link>
            </div>
        </div>
    </nav>
</template>

<style>
.pac-container {
    z-index: 10000 !important;
    border-radius: 8px;
    border-top: none;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

</style>