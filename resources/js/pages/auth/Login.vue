<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import AuthSplitLayoutLoginRegistro from '@/layouts/AuthSplitLayoutLoginRegistro.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="Iniciar sesión" />

    <AuthSplitLayoutLoginRegistro
        title="¡Bienvenido/a de vuelta!"
        image-src="/images/prueba.png"
        logo-src="/images/destinariologo1.png"
    >
        <div
            v-if="status"
            class="mb-6 rounded-md bg-green-50 px-4 py-3 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col"
        >
            <div class="flex flex-col gap-[40px]">
                <div class="flex flex-col gap-[11px]">
                    <Label
                        for="email"
                        class="text-[16px] text-black font-bold"
                        style="font-family: 'Noto Serif Tamil', serif;"
                    >
                        Correo electrónico
                    </Label>

                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        autocomplete="email"
                        class="h-[39px] rounded-[9px] border border-black/15 bg-transparent px-[10px] py-[10px] text-black shadow-none focus-visible:ring-0 focus-visible:border-black/15"
                    />

                    <InputError :message="errors.email" />
                </div>

                <div class="flex flex-col gap-[11px]">
                    <div class="flex items-center justify-between gap-4">
                        <Label
                            for="password"
                            class="text-[16px] text-black font-bold"
                            style="font-family: 'Noto Serif Tamil', serif;"
                        >
                            Contraseña
                        </Label>

                        <Link
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-[14px] md:text-[16px] text-black no-underline hover:underline underline-offset-4"
                            style="font-family: 'Nunito Sans', sans-serif;"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>

                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="h-[39px] rounded-[9px] border border-black/15 bg-transparent px-[10px] py-[10px] text-black shadow-none focus-visible:ring-0 focus-visible:border-black/15"
                    />

                    <InputError :message="errors.password" />
                </div>

                <div class="flex justify-center pt-4">
                    <button
                        type="submit"
                        :disabled="processing"
                        class="flex h-[50px] w-[183px] items-center justify-center rounded-full bg-[#604646] px-6 text-white shadow-md transition duration-200 hover:scale-105 hover:bg-[#4a3636]"
                        style="font-family: 'Noto Serif Tamil', serif;"
                    >
                        <Spinner v-if="processing" class="mr-2" />
                        <span class="text-[19px] font-semibold">
                            Iniciar Sesión
                        </span>
                    </button>
                </div>
            </div>

            <div
                v-if="canRegister"
                class="mt-20 text-center text-[18px] md:text-[20px] text-[#B8BEB8]"
                style="font-family: 'Nunito Sans', sans-serif;"
            >
                ¿No tienes una cuenta?
                <Link
                    :href="register()"
                    class="text-[#B8BEB8] no-underline hover:underline underline-offset-4"
                >
                    Regístrate aquí
                </Link>
            </div>
        </Form>
    </AuthSplitLayoutLoginRegistro>
</template>