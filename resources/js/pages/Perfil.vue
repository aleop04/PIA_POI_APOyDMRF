<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProfileCover from '@/components/Profile/ProfileCover.vue';
import ProfileCard from '@/components/Profile/ProfileCard.vue';
import ProfileSection from '@/components/Profile/ProfileSection.vue';

type User = {
    id: number;
    first_name: string;
    last_name: string;
    username: string;
    email: string;
    bio: string | null;
    profile_photo: string | null;
    cover_photo: string | null;
    total_points: number;
    location: string | null;

};

defineProps<{
    user: User;
}>();

</script>

<template>
    <Head title="Perfil" />

    <section class="relative min-h-screen bg-[#FDF0D9]">
        <ProfileCover :cover-photo="user.cover_photo" />

        <div class="relative z-10 flex items-start gap-[63px] px-[55px] pb-[140px]">
            <ProfileCard :user="user" />

            <main class="flex-1 pt-[40px]">
                <ProfileSection title="Tus insignias" image-width="w-[245px]" />

                <ProfileSection
                    title="Tus descuentos"
                    class="mt-[50px]"
                    image-height="h-[250px]"
                    image-width="w-[240px]"
                />
            </main>
        </div>

        <ProfileSection
            title="Tus post publicados"
            class="mx-[55px] mb-[140px]"
            image-height="h-[220px]"
            image-width="w-[240px]"
        />
    </section>
</template>