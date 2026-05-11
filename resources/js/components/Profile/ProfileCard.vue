<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AddressAutocomplete from '@/components/AddressAutocomplete.vue';

type User = {
    first_name: string;
    last_name: string;
    username: string;
    email: string;
    bio: string | null;
    profile_photo: string | null;
    total_points: number;
    location: string | null;
};

const props = defineProps<{
    user: User;
}>();

const editing = ref(false);

const form = ref({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    username: props.user.username,
    bio: props.user.bio ?? '',

    formatted_address: props.user.location ?? '',
    lat: null,
    lng: null,
    place_id: null,

    password: '',
    password_confirmation: '',
});

const logout = async () => {
    await fetch('/logout', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '',
            Accept: 'application/json',
        },
    });

    window.location.href = '/login';
};

async function handleProfilePhoto(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) return;

    const formData = new FormData();
    formData.append('profile_photo', file);

    await fetch('/perfil/profile-photo', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '',
            Accept: 'application/json',
        },
        body: formData,
    });

    window.location.reload();
}

async function updateProfile() {
    const res = await fetch('/perfil', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '',
            Accept: 'application/json',
        },
        body: JSON.stringify(form.value),
    });

    if (!res.ok) {
        const error = await res.json();
        console.error('ERROR AL ACTUALIZAR PERFIL:', error);
        alert(Object.values(error.errors ?? {}).flat().join('\n'));
        return;
    }

    editing.value = false;
    window.location.reload();
}

async function deleteProfile() {
    if (!confirm('¿Seguro que quieres eliminar tu perfil?')) return;

    await fetch('/perfil', {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '',
            Accept: 'application/json',
        },
    });

    window.location.href = '/';
}

function setLocation(location: any) {
    form.value.formatted_address = location.formatted_address ?? '';
    form.value.lat = location.lat;
    form.value.lng = location.lng;
    form.value.place_id = location.place_id;
}

</script>

<template>
    <div class="relative -mt-[73px] w-[383px] overflow-visible">
        <!-- FOTO PERFIL -->
        <label
            class="group absolute left-1/2 top-[-55px] z-20 flex h-[110px] w-[110px] -translate-x-1/2 cursor-pointer items-center justify-center overflow-hidden rounded-full bg-[#FF7608] outline outline-[6px] outline-[#FFEBC9]"
        >
            <img
                v-if="user.profile_photo"
                :src="`/storage/${user.profile_photo}`"
                class="h-full w-full object-cover"
            />

            <img
                v-else
                src="/icons/User.svg"
                class="h-[78px] w-[78px]"
            />

            <div
                class="absolute inset-0 hidden items-center justify-center bg-black/50 text-center text-[13px] font-bold text-white group-hover:flex"
            >
                Subir<br />
                perfil
            </div>

            <input type="file" accept="image/*" class="hidden" @change="handleProfilePhoto"/>
        </label>

        <!-- CARD PERFIL -->
        <aside class="relative w-[383px] overflow-hidden rounded-[94px] bg-[#00BF63] transition-all duration-300"
        :class="editing ? 'h-[1195px]' : 'h-[1079px]'">
            <h2
                class="absolute left-1/2 top-[77px] w-[260px] -translate-x-1/2 truncate text-center text-[32px] font-bold text-white"
            >
                {{ user.username }}
            </h2>

            <!-- BIO -->
            <div class="absolute left-[38px] top-[138px] w-[307px]">
                <label class="text-[16px] font-bold text-white">Bio</label>

                <textarea
                    v-if="editing"
                    v-model="form.bio"
                    class="mt-[11px] h-[104px] w-full resize-none rounded-[9px] border border-[#F9C49A] bg-transparent px-3 py-2 text-white outline-none focus:border-white"
                />

                <div
                    v-else
                    class="mt-[11px] flex h-[104px] items-start overflow-hidden rounded-[9px] border border-[#F9C49A] px-3 py-2 text-white"
                >
                    {{ user.bio ?? 'Sin biografía' }}
                </div>
            </div>

            <!-- DATOS -->
            <div class="absolute left-[38px] top-[298px] flex w-[307px] flex-col gap-[25px]">
                <div>
                    <label class="text-[16px] font-bold text-white">Usuario</label>

                    <!-- EDITANDO -->
                    <input
                        v-if="editing"
                        v-model="form.username"
                        class="mt-[11px] h-[39px] w-full rounded-[9px] border border-[#F9C49A] bg-transparent px-3 text-white outline-none focus:border-white"
                    />

                    <!-- SOLO MOSTRAR -->
                    <div
                        v-else
                        class="mt-[11px] flex h-[39px] items-center rounded-[9px] border border-[#F9C49A] px-3 text-white"
                    >
                        {{ user.username }}
                    </div>
                </div>

                <div>
                    <label class="text-[16px] font-bold text-white">Nombre</label>

                    <!-- EDITANDO -->
                    <input
                        v-if="editing"
                        v-model="form.first_name"
                        class="mt-[11px] h-[39px] w-full rounded-[9px] border border-[#F9C49A] bg-transparent px-3 text-white outline-none focus:border-white"
                    />

                    <!-- SOLO MOSTRAR -->
                    <div
                        v-else
                        class="mt-[11px] flex h-[39px] items-center rounded-[9px] border border-[#F9C49A] px-3 text-white"
                    >
                        {{ user.first_name }}
                    </div>
                </div>

                <div>
                    <label class="text-[16px] font-bold text-white">Apellidos</label>

                    <!-- EDITANDO -->
                    <input
                        v-if="editing"
                        v-model="form.last_name"
                        class="mt-[11px] h-[39px] w-full rounded-[9px] border border-[#F9C49A] bg-transparent px-3 text-white outline-none focus:border-white"
                    />

                    <!-- SOLO MOSTRAR -->
                    <div
                        v-else
                        class="mt-[11px] flex h-[39px] items-center rounded-[9px] border border-[#F9C49A] px-3 text-white"
                    >
                        {{ user.last_name }}
                    </div>
                </div>

                <div>
                    <label class="text-[16px] font-bold text-white">Email</label>

                    <div class="mt-[11px] flex h-[39px] items-center rounded-[9px] border border-[#F9C49A] px-3 text-white">
                        {{ user.email }}
                    </div>
                </div>

                <div>
                    <label class="text-[16px] font-bold text-white">Contraseña</label>

                    <!-- EDITANDO -->
                    <div v-if="editing" class="flex flex-col gap-3">
                        <input
                            v-model="form.password"
                            type="password"
                            placeholder="Nueva contraseña"
                            class="mt-[11px] h-[39px] w-full rounded-[9px] border border-[#F9C49A] bg-transparent px-3 !text-white caret-white outline-none placeholder:text-white/60 focus:border-white"
                        />

                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Confirmar contraseña"
                            class="h-[39px] w-full rounded-[9px] border border-[#F9C49A] bg-transparent px-3 !text-white caret-white outline-none placeholder:text-white/60 focus:border-white"
                        />
                    </div>

                    <!-- SOLO MOSTRAR -->
                    <div
                        v-else
                        class="mt-[11px] flex h-[39px] items-center rounded-[9px] border border-[#F9C49A] px-3 text-white"
                    >
                        **************
                    </div>

                </div>

                <div>
                    <label class="text-[16px] font-bold text-white">Ubicación</label>

                    <!-- EDITANDO -->
                    <AddressAutocomplete
                        v-if="editing"
                        v-model="form.formatted_address"
                        @location-selected="setLocation"
                        class="mt-[11px] h-[39px] w-full rounded-[9px] border border-[#F9C49A] bg-transparent px-3 text-white outline-none focus:border-white"
                    />

                    <!-- SOLO MOSTRAR -->
                    <div
                        v-else
                        class="mt-[11px] flex h-[39px] items-center rounded-[9px] border border-[#F9C49A] px-3 text-white"
                    >
                        <span class="truncate">
                            {{ user.location ?? 'Sin ubicación' }}
                        </span>
                    </div>
                </div>

                <div v-if="editing" class="mt-4 flex gap-3">
                    <button
                        @click="updateProfile"
                        class="h-[45px] flex-1 cursor-pointer rounded-[50px] bg-white text-black transition-all duration-200 hover:scale-[1.03] hover:shadow-lg active:scale-[0.97]"
                    >
                        Guardar
                    </button>

                    <button
                        @click="editing = false"
                        class="h-[45px] flex-1 cursor-pointer rounded-[50px] bg-white text-black transition-all duration-200 hover:scale-[1.03] hover:shadow-lg active:scale-[0.97]"
                    >
                        Cancelar
                    </button>
                </div>

            </div>

            <!-- BOTONES -->
            <div class="absolute left-1/2 flex w-[300px] -translate-x-1/2 flex-col items-center gap-[38px] transition-all duration-300"
            :class="editing ? 'top-[1030px]' : 'top-[899px]'">
                <div class="flex gap-[15px]">
                    <button
                        @click="editing = true"
                        class="h-[51px] w-[135px] cursor-pointer rounded-[50px] bg-[#FF7608] text-white shadow-md transition-all duration-200 hover:scale-[1.03] hover:bg-[#e86600] hover:shadow-lg active:scale-[0.97]"
                    >
                        Editar perfil
                    </button>

                    <button
                        @click="deleteProfile"
                        class="h-[51px] w-[145px] cursor-pointer rounded-[50px] bg-[#FF0808] text-white shadow-md transition-all duration-200 hover:scale-[1.03] hover:bg-[#d90000] hover:shadow-lg active:scale-[0.97]"
                    >
                        Eliminar perfil
                    </button>
                </div>

                <button
                    @click="logout"
                    class="h-[51px] w-[130px] cursor-pointer rounded-[50px] bg-[#604646] text-white shadow-md transition-all duration-200 hover:scale-[1.03] hover:bg-[#4f3939] hover:shadow-lg active:scale-[0.97]"
                >
                    Cerrar sesión
                </button>
            </div>
        </aside>

        <Link href="/recompensas">
        <button
            class="mt-[40px] h-[99px] w-[383px] cursor-pointer rounded-[58px] bg-[#FFDC51] text-[32px] font-semibold text-[#442F2F] shadow-md transition-all duration-200 hover:scale-[1.02] hover:bg-[#ffd12b] hover:shadow-lg active:scale-[0.98]"
        >
            Ver recompensas
        </button>
        </Link>
    </div>
</template>