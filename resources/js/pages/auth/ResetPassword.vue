<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { login } from '@/routes';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Restablecer contraseña',
        description: 'Ingresa tu nueva contraseña',
    },
});

const props = defineProps<{
    token: string;
    email: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <Head title="Restablecer contraseña" />

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
                class="relative z-10 w-full max-w-[553px] rounded-[68px] bg-[#FDFDFD] px-[62px] pt-[63px] pb-[63px] shadow-[0px_10px_30px_rgba(0,0,0,0.10)]"
            >
                <!-- encabezado -->
                <div class="flex flex-col items-center gap-4 text-center">
                    <img
                        src="/images/destinariologo1.png"
                        alt="Crear nueva contraseña"
                        class="h-[121px] w-[121px] object-contain"
                    />

                    <div class="flex w-full max-w-[473px] flex-col items-center gap-4">
                        <h1
                            class="text-center font-['Nunito'] text-[29px] leading-none font-bold text-[#FF7608]"
                        >
                            Crea una nueva contraseña
                        </h1>
                    </div>
                </div>

                <!-- formulario -->
                <div class="mt-[50px]">
                    <Form
                        v-bind="update.form()"
                        :transform="(data) => ({ ...data, token, email })"
                        :reset-on-success="['password', 'password_confirmation']"
                        @success="router.visit(login().url)"
                        v-slot="{ errors, processing }"
                    >
                        <div class="flex flex-col gap-[31px]">
                            <!-- Email oculto para mantener la lógica -->
                            <input
                                type="hidden"
                                name="email"
                                :value="inputEmail"
                            />

                            <div class="flex flex-col gap-[11px]">
                                <label
                                    for="password"
                                    class="font-['Noto_Serif_Tamil'] text-[16px] font-bold text-black"
                                >
                                    Nueva contraseña
                                </label>

                                <PasswordInput
                                    id="password"
                                    name="password"
                                    autocomplete="new-password"
                                    autofocus
                                    placeholder=""
                                    class="h-[39px] w-full rounded-[9px] border border-black/13 bg-white px-[10px] text-black outline-none transition focus:border-[#FF7608]"
                                />

                                <InputError :message="errors.password" />
                            </div>

                            <div class="flex flex-col gap-[11px]">
                                <label
                                    for="password_confirmation"
                                    class="font-['Noto_Serif_Tamil'] text-[16px] font-bold text-black"
                                >
                                    Confirmar nueva contraseña
                                </label>

                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    autocomplete="new-password"
                                    placeholder=""
                                    class="h-[39px] w-full rounded-[9px] border border-black/13 bg-white px-[10px] text-black outline-none transition focus:border-[#FF7608]"
                                />

                                <InputError :message="errors.password_confirmation" />
                            </div>

                            <div class="flex flex-col items-center gap-7 pt-[10px]">
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    data-test="reset-password-button"
                                    class="flex h-[50px] w-[143px] items-center justify-center rounded-[50px] bg-[#00BF63] px-4 text-center font-['Noto_Serif_Tamil'] text-[20px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    {{ processing ? 'Enviando...' : 'Confirmar' }}
                                </button>

                                <div
                                    class="text-center font-['Nunito_Sans'] text-[20px] font-normal text-[#B8BEB8]"
                                >
                                    Regresar al
                                    <Link
                                        :href="login().url"
                                        class="text-[#B8BEB8] no-underline hover:underline underline-offset-4"
                                    >
                                        Inicio de sesión
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