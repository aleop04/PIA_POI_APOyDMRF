<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Recuperar contraseña',
        description: 'Ingresa tu email para recibir un link de recuperación',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Recuperar contraseña" />

    <div class="min-h-screen overflow-hidden bg-[#FFF1D9]">
        <div class="relative flex min-h-screen items-center justify-center px-4 py-10">
            <!-- fondo -->
            <img
                src="/images/fondopasw.jpeg"
                alt="Fondo"
                class="absolute inset-0 h-full w-full object-cover"
            />

            <!-- tarjeta -->
            <div
                class="relative z-10 w-full max-w-[553px] rounded-[68px] bg-white px-[62px] pt-[64px] pb-[64px] shadow-[0px_10px_30px_rgba(0,0,0,0.10)]"
            >
                <!-- encabezado -->
                <div class="flex flex-col items-center gap-4 text-center">
                    <img
                        src="/images/destinariologo1.png"
                        alt="Recuperar contraseña"
                        class="h-[121px] w-[121px] object-contain"
                    />

                    <div class="flex w-full max-w-[473px] flex-col items-center gap-4">
                        <h1
                            class="text-center font-['Nunito'] text-[29px] leading-none font-bold text-[#FF7608]"
                        >
                            Recuperación de contraseña
                        </h1>

                        <p
                            class="text-center font-['Nunito_Sans'] text-[20px] leading-[22px] font-normal text-[#B8BEB8]"
                        >
                            Ingresa tu email para recibir el link de recuperación
                        </p>
                    </div>
                </div>

                <!-- mensaje de éxito -->
                <div
                    v-if="status"
                    class="mt-8 rounded-[12px] bg-green-50 px-4 py-3 text-center text-sm font-medium text-green-700"
                >
                    {{ status }}
                </div>

                <!-- formulario -->
                <div class="mt-[22px]">
                    <Form v-bind="email.form()" v-slot="{ errors, processing }">
                        <div class="flex flex-col gap-[41px]">
                            <div class="flex flex-col gap-[11px]">
                                <label
                                    for="email"
                                    class="font-['Noto_Serif_Tamil'] text-[16px] font-bold text-black"
                                >
                                    Correo electrónico
                                </label>

                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autocomplete="off"
                                    autofocus
                                    placeholder=""
                                    class="h-[39px] w-full rounded-[9px] border border-black/13 bg-white px-[10px] text-black placeholder:text-gray-400 outline-none transition focus:border-[#FF7608]"
                                />

                                <InputError :message="errors.email" />
                            </div>

                            <div class="flex flex-col items-center gap-7">
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    data-test="email-password-reset-link-button"
                                    class="flex h-[50px] w-full max-w-[296px] items-center justify-center rounded-[50px] bg-[#00BF63] px-4 text-center font-['Noto_Serif_Tamil'] text-[17px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    {{ processing ? 'Enviando...' : 'Enviar link de recuperación' }}
                                </button>

                                <div
                                    class="text-center font-['Nunito_Sans'] text-[20px] font-normal text-[#B8BEB8]"
                                >
                                    Regresar al
                                    <Link :href="login().url" class="text-[#B8BEB8] no-underline hover:underline underline-offset-4">
                                        Inicia sesión aquí
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </Form>
                </div>
            </div>
        </div>
    </div>
</template>