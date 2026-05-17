<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProfileCard from '@/components/Profile/ProfileCard.vue';
import ProfileCover from '@/components/Profile/ProfileCover.vue';
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

        <div
            class="relative z-10 flex flex-col items-center gap-8 px-4 pb-16 md:px-[55px] md:pb-[140px] lg:flex-row lg:items-start lg:gap-[63px]"
        >
            <ProfileCard :user="user" />

            <main class="w-full flex-1 pt-0 lg:pt-[40px]">
                <ProfileSection title="Tus insignias" image-width="w-[180px] md:w-[245px]" />

                <ProfileSection
                    title="Tus descuentos"
                    class="mt-8 md:mt-[50px]"
                    image-height="h-[190px] md:h-[250px]"
                    image-width="w-[180px] md:w-[240px]"
                />
            </main>
        </div>

        <ProfileSection
            title="Tus post publicados"
            class="mx-4 mb-16 md:mx-[55px] md:mb-[140px]"
            image-height="h-[180px] md:h-[220px]"
            image-width="w-[180px] md:w-[240px]"
        />
    </section>
</template>