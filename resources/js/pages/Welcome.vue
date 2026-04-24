<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Destinario" />

    <div
        class="relative min-h-screen overflow-hidden bg-cover bg-center bg-no-repeat"
        style="background-image: url('/images/fotofondonueva.png')"
    >
        <div class="mx-auto flex min-h-screen max-w-[1440px] items-center px-10 lg:px-24">
            <div class="flex w-full flex-col items-center justify-between gap-16 lg:flex-row">
                
                <!-- Columna izquierda -->
                <div class="flex max-w-[641px] flex-col items-start gap-14">
                    <img
                        src="/images/destinariologo2.png"
                        alt="Logo"
                        class="h-[120px] w-auto object-contain"
                    />

                    <h1 class="max-w-[534px] text-[40px] leading-tight font-normal text-[#604646] font-['Asul']">
                        Las mejores guías turísticas para visitar en México durante el mundial de futbol 2026
                    </h1>

                    <Link
                        :href="login()"
                        class="flex h-[86px] w-[252px] items-center justify-center rounded-full bg-[#00BF63] px-[8px] py-[2px] text-center text-[30px] font-bold text-[#FFEBC9] shadow-md transition hover:scale-105 hover:bg-[#00a857] font-['Nunito_Sans']"
                    >
                        Iniciar Sesión
                    </Link>
                </div>

                <!-- Columna derecha -->
                <div class="flex justify-center">
                    <img
                        src="/images/destinariologo1.png"
                        alt="Ilustración de bienvenida"
                        class="h-auto w-full max-w-[889px] object-contain"
                    />
                </div>
            </div>
        </div>
    </div>
</template>