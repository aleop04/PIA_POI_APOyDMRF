<script setup lang="ts">
const props = defineProps<{
    photo?: string | null;
    className?: string;
    alt?: string;
}>();

function getPhotoUrl(photo: string) {
    if (photo.startsWith('http')) {
        return photo;
    }

    if (photo.startsWith('/storage/')) {
        return photo;
    }

    return `/storage/${photo}`;
}
</script>

<template>
    <div
        :class="[
            'overflow-hidden rounded-full bg-[#FF7608] flex items-center justify-center',
            className
        ]"
    >
        <img
            v-if="photo"
            :src="getPhotoUrl(photo)"
            class="h-full w-full object-cover"
            :alt="alt ?? 'Avatar'"
        />

        <img
            v-else
            src="/icons/User.svg"
            class="h-2/3 w-2/3"
            alt="Default User"
        />
    </div>
</template>