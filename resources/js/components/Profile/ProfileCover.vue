<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    coverPhoto: string | null;
    readonly?: boolean;
}>();

const coverPreview = ref<string | null>(null);

async function handleCoverPhoto(event: Event) {
    if (props.readonly) {
return;
}

    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
return;
}

    coverPreview.value = URL.createObjectURL(file);

    router.post(
        '/perfil/cover-photo',
        {
            cover_photo: file,
        },
        {
            forceFormData: true,
            onSuccess: () => {
                window.location.reload();
            },
            onError: (errors) => {
                console.error('Error al subir:', errors);
            },
        },
    );
}
</script>

<template>
    <label
        v-if="!readonly"
        class="group relative block h-[351px] w-full cursor-pointer bg-[#604646] bg-cover bg-center"
        :style="{
            backgroundImage: coverPreview
                ? `url(${coverPreview})`
                : coverPhoto
                    ? `url(/storage/${coverPhoto})`
                    : undefined,
        }"
    >
        <div
            class="absolute inset-0 hidden items-center justify-center bg-black/40 group-hover:flex"
        >
            <div
                class="flex h-[43px] w-[145px] items-center justify-center gap-2 rounded-[45px] border-2 border-white text-white"
            >
                <img src="/icons/Upload.svg" class="h-6 w-6" />
                <span class="text-[18px] font-medium">Subir foto</span>
            </div>
        </div>

        <input
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleCoverPhoto"
        />
    </label>

    <div
        v-else
        class="relative h-[351px] w-full bg-[#604646] bg-cover bg-center"
        :style="{
            backgroundImage: coverPhoto ? `url(/storage/${coverPhoto})` : undefined,
        }"
    />
</template>