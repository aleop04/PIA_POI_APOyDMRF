<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CardPublicacion from '@/components/Posts/CardPublicacion.vue';
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

type Reward = {
    id: number;
    name: string;
    description: string | null;
    type: 'badge' | 'discount';
    cost_points: number;
    image: string | null;
    discount_value: number | null;
};

type RewardRedemption = {
    id: number;
    reward_id: number;
    points_spent: number;
    redeemed_at: string;
    reward: Reward;
};

defineProps<{
    user: User;
    posts: any[];
    badges: RewardRedemption[];
    discounts: RewardRedemption[];
}>();

function rewardImageUrl(image: string | null) {
    if (!image) {
        return null;
    }

    if (image.startsWith('http')) {
        return image;
    }

    if (image.startsWith('/storage/')) {
        return image;
    }

    return `/storage/${image}`;
}

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
                <ProfileSection
                    title="Tus insignias"
                    image-width="w-[180px] md:w-[245px]"
                    :has-content="badges.length > 0"
                >
                    <div class="grid w-full grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        <div
                            v-for="badge in badges"
                            :key="badge.id"
                            class="flex flex-col items-center gap-3 text-center"
                        >
                            <div class="h-[92px] w-[92px] overflow-hidden rounded-full border-[4px] border-[#FF7608] bg-white shadow-md">
                                <img
                                    v-if="rewardImageUrl(badge.reward.image)"
                                    :src="rewardImageUrl(badge.reward.image)!"
                                    :alt="badge.reward.name"
                                    class="h-full w-full object-cover"
                                />

                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-[36px]"
                                >
                                    🏅
                                </div>
                            </div>

                            <p class="max-w-[130px] text-[15px] font-bold text-[#FF7608]">
                                {{ badge.reward.name }}
                            </p>
                        </div>
                    </div>
                </ProfileSection>

                <ProfileSection
                    title="Tus descuentos"
                    class="mt-8 md:mt-[50px]"
                    image-height="h-[190px] md:h-[250px]"
                    image-width="w-[180px] md:w-[240px]"
                    :has-content="discounts.length > 0"
                >
                    <div class="grid w-full grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        <div
                            v-for="discount in discounts"
                            :key="discount.id"
                            class="rounded-[28px] bg-[#FFF5C2] px-6 py-5 shadow-md"
                        >
                            <div class="flex items-center gap-4">
                                <div class="flex h-[65px] w-[65px] shrink-0 items-center justify-center overflow-hidden rounded-full border-[3px] border-[#00BF63] bg-white">
                                    <img
                                        v-if="rewardImageUrl(discount.reward.image)"
                                        :src="rewardImageUrl(discount.reward.image)!"
                                        :alt="discount.reward.name"
                                        class="h-full w-full object-cover"
                                    />

                                    <span v-else class="text-[30px]">
                                        🎟️
                                    </span>
                                </div>

                                <div>
                                    <h3 class="text-[18px] font-extrabold text-[#0E6C3F]">
                                        {{ discount.reward.name }}
                                    </h3>

                                    <p class="text-[13px] font-semibold text-[#8A7474]">
                                        Canjeado por {{ discount.points_spent }} puntos
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </ProfileSection>
            </main>
        </div>

        <div class="px-4 pb-16 md:px-[55px] md:pb-[140px]">
            <ProfileSection
                title="Tus post publicados"
                image-height="h-[180px] md:h-[220px]"
                image-width="w-[180px] md:w-[240px]"
                :has-content="posts.length > 0"
            >
                <div class="grid w-full grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    <CardPublicacion
                        v-for="post in posts"
                        :key="post.id"
                        :post="post"
                    />
                </div>
            </ProfileSection>
        </div>
    </section>
</template>