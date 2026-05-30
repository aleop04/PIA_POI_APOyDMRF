<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, onUnmounted } from 'vue';
import AddressAutocomplete from '@/components/AddressAutocomplete.vue'

import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
    DialogClose
} from '@/components/ui/dialog';

interface UploadedImage {
    id: number;
    url: string;
    file: File;
}

const images = ref<UploadedImage[]>([]);
const MAX_IMAGES = 4;
const previewImage = ref<UploadedImage | null>(null);

const imageSlots = computed(() => {
    return Array.from({ length: MAX_IMAGES }, (_, index) => {
        return images.value[index] ?? null;
    });
});

const activityName = ref('');
const description = ref('');
const address = ref('');

const selectedLocation = ref<{
    formatted_address: string | null;
    lat: number | null;
    lng: number | null;
    place_id: string | null;
}>({
    formatted_address: null,
    lat: null,
    lng: null,
    place_id: null,
});

const timeStart = ref('');
const timeEnd = ref('');

const selectedDays = ref<string[]>([]);

const showConfirmDialog = ref(false);
const showSuccessMessage = ref(false);
const showErrorMessage = ref(false);

const errorMessage = ref('');

const isLoading = ref(false);

const days = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

const dayLabels: Record<string, string> = {
    L: 'Lun',
    M: 'Mar',
    X: 'Mié',
    J: 'Jue',
    V: 'Vie',
    S: 'Sáb',
    D: 'Dom',
};

let nextId = 0;
const MAX_IMAGE_SIZE = 10 * 1024 * 1024;

const mainFileInput = ref<HTMLInputElement | null>(null);

function openConfirmDialog() {

    if (!activityName.value.trim()) {
        alert('Debes agregar un título.');
        return;
    }

    if (!description.value.trim()) {
        alert('Debes agregar una descripción.');
        return;
    }

    showConfirmDialog.value = true;
}

function toggleDay(day: string) {

    const index = selectedDays.value.indexOf(day);

    if (index === -1) {
        selectedDays.value.push(day);
    } else {
        selectedDays.value.splice(index, 1);
    }
}

function handleLocationSelected(location: {
    formatted_address: string | null;
    lat: number | null;
    lng: number | null;
    place_id: string | null;
}) {
    selectedLocation.value = location;
    address.value = location.formatted_address ?? '';
}

function handleImageUpload(event: Event) {

    const input = event.target as HTMLInputElement;

    if (!input.files) return;

    const files = Array.from(input.files);

    const remaining = MAX_IMAGES - images.value.length;

    if (remaining <= 0) {
        errorMessage.value = `Solo puedes subir máximo ${MAX_IMAGES} fotos.`;
        showErrorMessage.value = true;

        setTimeout(() => {
            showErrorMessage.value = false;
        }, 3000);

        input.value = '';
        return;
    }

    if (files.length > remaining) {
        errorMessage.value = `Solo puedes subir ${MAX_IMAGES} fotos. Se agregaron únicamente las permitidas.`;
        showErrorMessage.value = true;

        setTimeout(() => {
            showErrorMessage.value = false;
        }, 3000);
    }

    const filesToAdd = files.slice(0, remaining);

    for (const file of filesToAdd) {

        if (file.size > MAX_IMAGE_SIZE) {

            showConfirmDialog.value = false;

            errorMessage.value =
                'No se permiten fotos de más de 10 MB.';

            showErrorMessage.value = true;

            setTimeout(() => {

                showErrorMessage.value = false;

            }, 3000);

            continue;
        }

        const url = URL.createObjectURL(file);

        images.value.push({
            id: nextId++,
            url,
            file,
        });
    }

    input.value = '';
}

function removeImage(id: number) {

    const index = images.value.findIndex((img) => img.id === id);

    if (index === -1) return;

    URL.revokeObjectURL(images.value[index].url);

    images.value.splice(index, 1);
}

function openImagePreview(img: UploadedImage) {
    previewImage.value = img;
}

function closeImagePreview() {
    previewImage.value = null;
}

function imageSlotClass(index: number): string {

    if (index === 0) {
        return 'md:col-span-2 md:row-span-2';
    }

    if (index === 1) {
        return 'md:col-span-2';
    }

    return '';
}

function triggerImageUpload() {

    if (images.value.length >= MAX_IMAGES) {
        errorMessage.value = `Solo puedes subir máximo ${MAX_IMAGES} fotos.`;
        showErrorMessage.value = true;

        setTimeout(() => {
            showErrorMessage.value = false;
        }, 3000);

        return;
    }

    mainFileInput.value?.click();
}

const duration = computed(() => {

    if (!timeStart.value || !timeEnd.value) {
        return null;
    }

    const [startHour, startMinute] = timeStart.value.split(':').map(Number);

    const [endHour, endMinute] = timeEnd.value.split(':').map(Number);

    const totalMinutes =
        (endHour * 60 + endMinute) -
        (startHour * 60 + startMinute);

    if (totalMinutes <= 0) {
        return null;
    }

    const hours = Math.floor(totalMinutes / 60);

    const minutes = totalMinutes % 60;

    if (hours > 0) {
        return `${hours}h${minutes > 0 ? ` ${minutes}min` : ''}`;
    }

    return `${minutes}min`;
});

async function submitPost() {

    isLoading.value = true;

    try {

        const form = new FormData();

        form.append('title', activityName.value);

        form.append('description', description.value);

        if (address.value.trim()) {
            form.append(
                'formatted_address',
                selectedLocation.value.formatted_address ?? address.value
            );
        }

        if (selectedLocation.value.lat !== null) {
            form.append('lat', String(selectedLocation.value.lat));
        }

        if (selectedLocation.value.lng !== null) {
            form.append('lng', String(selectedLocation.value.lng));
        }

        if (selectedLocation.value.place_id) {
            form.append('place_id', selectedLocation.value.place_id);
        }

        if (timeStart.value) {
            form.append('available_from', timeStart.value);
        }

        if (timeEnd.value) {
            form.append('available_to', timeEnd.value);
        }

        selectedDays.value.forEach((day) => {
            form.append('opening_days[]', day);
        });

        images.value.forEach((image) => {
            form.append('photos[]', image.file);
        });

        const res = await axios.post('/publicaciones', form, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        showConfirmDialog.value = false;

        showSuccessMessage.value = true;

        images.value.forEach((img) => {
            URL.revokeObjectURL(img.url);
        });

        images.value = [];

        if (res.data.ok) {
            router.visit(`/publicaciones/${res.data.post.id}`);
        }

    } catch (error: any) {

        console.error(
            'Error al crear publicación:',
            error.response?.data
        );

    } finally {

        isLoading.value = false;
    }
}

onUnmounted(() => {

    images.value.forEach((img) => {
        URL.revokeObjectURL(img.url);
    });
});
</script>

<template>
    <Head title="Crear publicación" />

    <section class="px-4 py-6 md:px-8 lg:px-10">

        <main class="mx-auto grid w-full max-w-[1500px] gap-6 lg:grid-cols-2">

            <div
                v-if="showSuccessMessage"
                class="fixed top-5 left-1/2 z-50 -translate-x-1/2 rounded-lg bg-green-100 px-4 py-2 font-semibold text-green-700 shadow"
            >
                ¡Publicación creada correctamente!
            </div>

            <div
                v-if="showErrorMessage"
                class="fixed top-20 left-1/2 z-50 -translate-x-1/2 rounded-lg border border-red-300 bg-red-100 px-4 py-2 font-semibold text-red-700 shadow"
            >
                {{ errorMessage }}
            </div>

            <!-- FOTOS -->
            <div class="lg:col-span-2">

                <!-- Desktop / Laptop -->
                <div class="hidden overflow-hidden rounded-3xl md:grid md:h-[360px] md:grid-cols-4 md:grid-rows-2 md:gap-2">

                    <div
                        v-for="(img, index) in imageSlots"
                        :key="index"
                        :class="[
                            'group relative overflow-hidden bg-[#FF7608]',
                            'flex cursor-pointer items-center justify-center transition hover:brightness-105',
                            imageSlotClass(index)
                        ]"
                        @click="img ? openImagePreview(img) : triggerImageUpload()"
                    >

                        <img
                            v-if="img"
                            :src="img.url"
                            class="h-full w-full object-cover"
                        />

                        <div
                            v-else
                            class="flex flex-col items-center gap-2 text-white/90"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-7 w-7"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 16.5V19a1 1 0 001 1h16a1 1 0 001-1v-2.5M12 3v13m0-13L8.5 6.5M12 3l3.5 3.5"
                                />
                            </svg>

                            <span class="text-sm font-semibold tracking-wide">
                                Subir foto
                            </span>
                        </div>

                        <button
                            v-if="img"
                            class="absolute top-3 right-3 flex h-8 w-8 items-center justify-center rounded-full bg-black/50 text-sm text-white opacity-0 transition group-hover:opacity-100 hover:bg-black/70"
                            @click.stop="removeImage(img.id)"
                        >
                            ✕
                        </button>

                    </div>

                </div>

                <!-- Celular -->
                <div class="md:hidden">

                    <div
                        v-if="images.length === 0"
                        class="flex h-72 w-full cursor-pointer flex-col items-center justify-center rounded-3xl bg-[#FF7608] transition hover:brightness-105"
                        @click="triggerImageUpload"
                    >
                        <div class="flex flex-col items-center gap-2 text-white/90">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-8 w-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 16.5V19a1 1 0 001 1h16a1 1 0 001-1v-2.5M12 3v13m0-13L8.5 6.5M12 3l3.5 3.5"
                                />
                            </svg>

                            <span class="text-sm font-semibold tracking-wide">
                                Subir foto
                            </span>

                            <span class="text-xs opacity-80">
                                Puedes elegir hasta {{ MAX_IMAGES }} fotos
                            </span>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2"
                    >

                        <div
                            v-for="img in images"
                            :key="img.id"
                            class="group relative h-72 min-w-full snap-center overflow-hidden rounded-3xl bg-[#FF7608]"
                            @click="openImagePreview(img)"
                        >
                            <img
                                :src="img.url"
                                class="h-full w-full object-cover"
                            />

                            <button
                                class="absolute top-3 right-3 flex h-8 w-8 items-center justify-center rounded-full bg-black/50 text-sm text-white"
                                @click.stop="removeImage(img.id)"
                            >
                                ✕
                            </button>
                        </div>

                        <div
                            v-if="images.length < MAX_IMAGES"
                            class="flex h-72 min-w-full snap-center cursor-pointer flex-col items-center justify-center rounded-3xl bg-[#FF7608] text-white/90"
                            @click="triggerImageUpload"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-8 w-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 16.5V19a1 1 0 001 1h16a1 1 0 001-1v-2.5M12 3v13m0-13L8.5 6.5M12 3l3.5 3.5"
                                />
                            </svg>

                            <span class="mt-2 text-sm font-semibold">
                                Subir foto
                            </span>

                            <span class="mt-1 text-xs opacity-80">
                                {{ images.length }}/{{ MAX_IMAGES }}
                            </span>
                        </div>

                    </div>

                    <p
                        v-if="images.length > 0"
                        class="mt-2 text-center text-xs text-gray-400"
                    >
                        Desliza para ver tus fotos
                    </p>

                </div>

            </div>

            <div class="rounded-2xl border border-[#f0dfc0] bg-[#fff8ee] p-5 shadow-md">

                <div class="mb-4 flex flex-wrap items-center gap-3">

                    <input
                        v-model="activityName"
                        type="text"
                        placeholder="Actividad por hacer"
                        class="min-w-0 flex-1 bg-transparent text-xl font-bold text-gray-800 outline-none placeholder:text-gray-400"
                    />
                </div>

                <div class="mb-4">
                    <AddressAutocomplete
                        v-model="address"
                        placeholder="Agregar ubicación..."
                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-[#442F2F] outline-none placeholder:text-gray-400 focus:border-[#FF7608]"
                        @location-selected="handleLocationSelected"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2">

                    <span class="mr-1 text-xs text-gray-400">
                        Días
                    </span>

                    <button
                        v-for="day in days"
                        :key="day"
                        type="button"
                        :title="dayLabels[day]"
                        @click="toggleDay(day)"
                        :class="[
                            'h-7 w-7 rounded-full text-xs font-semibold transition',
                            selectedDays.includes(day)
                                ? 'bg-[#FF7608] text-white shadow'
                                : 'border border-gray-200 bg-white text-gray-500 hover:border-[#FF7608] hover:text-[#FF7608]'
                        ]"
                    >
                        {{ day }}
                    </button>
                </div>

                <div class="mt-4 flex items-center gap-2">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 shrink-0 text-gray-400"
                        fill="currentColor"
                        viewBox="0 0 16 16"
                    >
                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                    </svg>

                    <input
                        v-model="timeStart"
                        type="time"
                        class="cursor-pointer rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs text-gray-600 outline-none focus:border-[#FF7608]"
                    />

                    <span class="text-xs text-gray-400">
                        —
                    </span>

                    <input
                        v-model="timeEnd"
                        type="time"
                        class="cursor-pointer rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs text-gray-600 outline-none focus:border-[#FF7608]"
                    />

                    <span
                        v-if="duration"
                        class="ml-2 text-xs font-medium text-[#FF7608]"
                    >
                        {{ duration }}
                    </span>
                </div>
            </div>

            <div class="rounded-2xl border border-dashed border-[#FF7608]/60 bg-white shadow-sm">
                <textarea
                    v-model="description"
                    placeholder="Agregar descripción..."
                    rows="4"
                    class="min-h-[185px] w-full resize-none rounded-2xl bg-transparent p-4 text-sm text-gray-700 outline-none placeholder:text-gray-400 lg:min-h-full"
                />
            </div>

            <input
                ref="mainFileInput"
                type="file"
                accept="image/*"
                multiple
                class="hidden"
                @change="handleImageUpload"
            />

            <div class="flex justify-center pt-2 pb-6 lg:col-span-2">

                <button
                    @click="openConfirmDialog"
                    class="rounded-full bg-[#FF7608] px-10 py-3 text-sm font-semibold text-white shadow-md transition hover:scale-105 hover:brightness-105"
                >
                    Subir actividad
                </button>

            </div>

            <Dialog
                :open="showConfirmDialog"
                @update:open="showConfirmDialog = $event"
            >

                <DialogContent>

                    <DialogHeader>

                        <DialogTitle>
                            ¿Publicar actividad?
                        </DialogTitle>

                        <DialogDescription>
                            Tu actividad será visible para todos los usuarios.
                        </DialogDescription>

                    </DialogHeader>

                    <DialogFooter>

                        <DialogClose as-child>

                            <button
                                class="rounded-full border border-gray-300 px-5 py-2 text-sm font-semibold transition hover:bg-gray-100"
                            >
                                Cancelar
                            </button>

                        </DialogClose>

                        <button
                            @click="submitPost"
                            :disabled="isLoading"
                            class="rounded-full bg-[#FF7608] px-5 py-2 text-sm font-semibold text-white transition hover:brightness-105 disabled:opacity-50"
                        >
                            {{ isLoading ? 'Publicando...' : 'Confirmar' }}
                        </button>

                    </DialogFooter>

                </DialogContent>

            </Dialog>

            <Dialog
                :open="previewImage !== null"
                @update:open="previewImage = $event ? previewImage : null"
            >
                <DialogContent class="max-w-5xl border-none bg-transparent p-0 shadow-none">

                    <div class="relative flex max-h-[85vh] items-center justify-center rounded-2xl bg-black/90 p-4">

                        <button
                            type="button"
                            class="absolute top-3 right-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-lg font-bold text-black transition hover:bg-white"
                            @click="closeImagePreview"
                        >
                            ✕
                        </button>

                        <img
                            v-if="previewImage"
                            :src="previewImage.url"
                            class="max-h-[80vh] max-w-full rounded-xl object-contain"
                        />

                    </div>

                </DialogContent>
            </Dialog>

        </main>

    </section>
</template>