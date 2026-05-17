<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3'
import { Spinner } from '@/components/ui/spinner'
import AuthSplitLayoutLoginRegistro from '@/layouts/AuthSplitLayoutLoginRegistro.vue'
import { logout } from '@/routes'
import { send } from '@/routes/verification'

const props = defineProps<{
    status?: string;
}>()
</script>

<template>
    <Head title="Verificar correo" />

    <AuthSplitLayoutLoginRegistro
        title="Verifica tu correo"
        image-src="/images/prueba.png"
        logo-src="/images/destinariologo1.png"
    >
        <div class="flex w-full flex-col items-center">
            <div class="flex w-full max-w-[530px] flex-col gap-8">
                <!-- subtítulo -->
                <div class="flex flex-col items-center gap-2 text-center -mt-9">
                    <p class="max-w-[430px] text-center font-[Nunito] text-[20px] font-normal text-[#B8BEB8]">
                        Te enviamos un enlace de verificación a tu correo electrónico.
                        Da clic en ese enlace para activar tu cuenta.
                    </p>
                </div>

                <!-- mensaje de reenvío -->
                <div
                    v-if="props.status === 'verification-link-sent'"
                    class="rounded-[12px] bg-green-50 px-4 py-3 text-center text-sm font-medium text-green-600"
                >
                    Se ha enviado un nuevo enlace de verificación al correo que proporcionaste durante tu registro.
                </div>

                <!-- formulario -->
                <Form
                    v-bind="send.form()"
                    v-slot="{ processing }"
                    class="flex w-full flex-col items-center"
                >
                    <div class="flex w-full flex-col items-center gap-8">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="h-[50px] w-[260px] rounded-[50px] bg-[#00BF63] font-['Noto_Serif_Tamil'] text-[20px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:brightness-95 disabled:opacity-70"
                        >
                            <span v-if="!processing">Reenviar correo</span>
                            <span v-else class="inline-flex items-center gap-2">
                                <Spinner />
                                Enviando...
                            </span>
                        </button>

                        <Link
                            :href="logout()"
                            method="post"
                            as="button"
                            class="text-center font-['Nunito_Sans'] text-[20px] text-[#B8BEB8] no-underline hover:underline underline-offset-4"
                        >
                            Cerrar sesión
                        </Link>
                    </div>
                </Form>
            </div>
        </div>
    </AuthSplitLayoutLoginRegistro>
</template>