<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3'
import InputError from '@/components/InputError.vue'
import AuthSplitLayoutLoginRegistro from '@/layouts/AuthSplitLayoutLoginRegistro.vue'
import { login } from '@/routes'
import { store } from '@/routes/register'
import AddressAutocomplete from '@/components/AddressAutocomplete.vue';
import { ref } from 'vue';


type LocationForm = {
    formatted_address: string | null;
    lat: number | null;
    lng: number | null;
    place_id: string | null;
};

const selectedLocation = ref<LocationForm>({
    formatted_address: null,
    lat: null,
    lng: null,
    place_id: null,
});

function handleLocationSelected(location: LocationForm) {
    selectedLocation.value = location;
}

</script>

<template>
    <Head title="Register" />

    <AuthSplitLayoutLoginRegistro
    title="Crea una cuenta"
    image-src="/images/prueba.png"
    logo-src="/images/destinariologo1.png"
>
    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex w-full flex-col items-center"
    >
        <div class="flex w-full max-w-[530px] flex-col gap-5">
            <!-- solo subtítulo -->
            <div class="flex flex-col items-center gap-2 text-center -mt-9">
                <p class="max-w-[405px] text-center font-[Nunito] text-[20px] font-normal text-[#B8BEB8]">
                    ¡Regístrate y obtendrás guías turísticas para visitar en México durante el mundial de fútbol 2026!
                </p>
            </div>

            <!-- Nombre/s -->
            <div class="flex flex-col gap-[11px]">
                <label
                    for="first_name"
                    class="font-['Noto_Serif_Tamil'] text-[15px] font-bold text-black"
                >
                    Nombre/s
                </label>
                <input
                    id="first_name"
                    name="first_name"
                    type="text"
                    required
                    autofocus
                    autocomplete="given-name"
                    class="h-[39px] w-full rounded-[9px] border border-black/13 px-[10px] text-black outline-none transition focus:border-[#FF7608]"
                />
                <InputError :message="errors.first_name" />
            </div>

            <!-- Apellidos -->
            <div class="flex flex-col gap-[11px]">
                <label
                    for="last_name"
                    class="font-['Noto_Serif_Tamil'] text-[15px] font-bold text-black"
                >
                    Apellidos
                </label>
                <input
                    id="last_name"
                    name="last_name"
                    type="text"
                    class="h-[39px] w-full rounded-[9px] border border-black/13 px-[10px] text-black outline-none transition focus:border-[#FF7608]"
                />
                <InputError :message="errors.last_name" />
            </div>

            <!-- Nombre de usuario -->
            <div class="flex flex-col gap-[11px]">
                <label
                    for="username"
                    class="font-['Noto_Serif_Tamil'] text-[15px] font-bold text-black"
                >
                    Nombre de usuario
                </label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    class="h-[39px] w-full rounded-[9px] border border-black/13 px-[10px] text-black outline-none transition focus:border-[#FF7608]"
                />
                <InputError :message="errors.username" />
            </div>

            <!-- Correo -->
            <div class="flex flex-col gap-[11px]">
                <label
                    for="email"
                    class="font-['Noto_Serif_Tamil'] text-[15px] font-bold text-black"
                >
                    Correo electrónico
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                    class="h-[39px] w-full rounded-[9px] border border-black/13 px-[10px] text-black outline-none transition focus:border-[#FF7608]"
                />
                <InputError :message="errors.email" />
            </div>

            <!-- Contraseña -->
            <div class="flex flex-col gap-[11px]">
                <label
                    for="password"
                    class="font-['Noto_Serif_Tamil'] text-[15px] font-bold text-black"
                >
                    Contraseña
                </label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="h-[39px] w-full rounded-[9px] border border-black/13 px-[10px] text-black outline-none transition focus:border-[#FF7608]"
                />
                <InputError :message="errors.password" />
            </div>

            <!-- Confirmar contraseña -->
            <div class="flex flex-col gap-[11px]">
                <label
                    for="password_confirmation"
                    class="font-['Noto_Serif_Tamil'] text-[15px] font-bold text-black"
                >
                    Confirmar contraseña
                </label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="h-[39px] w-full rounded-[9px] border border-black/13 px-[10px] text-black outline-none transition focus:border-[#FF7608]"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <!-- Ubicación -->
            <div class="flex flex-col gap-[11px]">
                <label
                    for="location"
                    class="font-['Noto_Serif_Tamil'] text-[15px] font-bold text-black"
                >
                    Ubicación
                </label>

                <div
                    class="relative flex h-[39px] w-full items-center overflow-hidden rounded-[9px] border border-black/13 bg-white px-[10px] pr-[36px] transition focus-within:border-[#FF7608]"
                >
                    <AddressAutocomplete
                        id="location"
                        class="h-full w-full bg-transparent text-black placeholder:text-black/45 focus:outline-none"
                        @location-selected="handleLocationSelected"
                    />

                    <div class="pointer-events-none absolute inset-y-0 right-[10px] flex items-center">
                        <svg
                            class="h-4 w-4 text-[#F6C7A2]"
                            viewBox="0 0 19 19"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M17.5 17.5L13.6333 13.6333M15.7222 8.61111C15.7222 12.5385 12.5385 15.7222 8.61111 15.7222C4.68375 15.7222 1.5 12.5385 1.5 8.61111C1.5 4.68375 4.68375 1.5 8.61111 1.5C12.5385 1.5 15.7222 4.68375 15.7222 8.61111Z"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>

                    <input type="hidden" name="formatted_address" :value="selectedLocation.formatted_address ?? ''" />
                    <input type="hidden" name="lat" :value="selectedLocation.lat ?? ''" />
                    <input type="hidden" name="lng" :value="selectedLocation.lng ?? ''" />
                    <input type="hidden" name="place_id" :value="selectedLocation.place_id ?? ''" />
                </div>

                <InputError :message="errors.formatted_address" />
            </div>

            <!-- botón -->
            <div class="flex justify-center pt-4">
                <button
                    type="submit"
                    :disabled="processing"
                    class="h-[50px] w-[161px] rounded-[50px] bg-[#00BF63] font-['Noto_Serif_Tamil'] text-[20px] font-semibold text-white shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:brightness-95 disabled:opacity-70"
                    data-test="register-user-button"
                >
                    {{ processing ? 'Registrando...' : 'Registrar' }}
                </button>
            </div>

            <!-- link -->
            <div class="pt-2 text-center font-['Nunito_Sans'] text-[20px] text-[#B8BEB8]">
                ¿Ya tienes una cuenta?
                <Link :href="login().url" class="text-[#B8BEB8] no-underline hover:underline underline-offset-4">
                    Inicia sesión aquí
                </Link>
            </div>
        </div>
    </Form>
</AuthSplitLayoutLoginRegistro>
</template>