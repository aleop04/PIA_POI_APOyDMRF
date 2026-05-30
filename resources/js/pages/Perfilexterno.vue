<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import ProfileCover from '@/components/Profile/ProfileCover.vue';
import ProfileSection from '@/components/Profile/ProfileSection.vue';
import CardPublicacion from '@/components/Posts/CardPublicacion.vue';

type User = {
    id: number;
    first_name: string;
    last_name: string;
    username: string;
    bio: string | null;
    profile_photo: string | null;
    cover_photo: string | null;
    total_points: number;
};

type Badge = {
    id: number;
    name: string;
    image: string | null;
};

type Post = {
    id: number;
    title: string;
    description: string;
    photos?: { id: number; url: string }[];
    user?: {
        id?: number;
        username?: string;
        profile_photo?: string | null;
    };
    ratings_avg_rating?: number | string | null;
    location?: {
        formatted_address: string | null;
    } | null;
};

const props = defineProps<{
    user: User;
    badges: Badge[];
    posts: Post[];
}>();

async function sendMessage() {
    try {
        const res = await axios.post('/conversations/private', {
            user_id: props.user.id,
        });

        router.visit(`/chats?conversation=${res.data.conversation.id}`);
    } catch (error) {
        console.error(error);
        alert('No se pudo abrir el chat.');
    }
}
</script>

<template>
    <Head :title="`Perfil de ${user.username}`" />

    <section class="relative min-h-screen bg-[#FDF0D9]">
        <ProfileCover :cover-photo="user.cover_photo" readonly />

        <div class="relative z-10 flex flex-col items-center gap-8 px-4 pb-16 md:px-[55px] md:pb-[140px] lg:flex-row lg:items-start lg:gap-[68px]">
            <!-- CARD PERFIL EXTERNO -->
            <div class="relative -mt-[73px] w-full max-w-[333px] shrink-0 overflow-visible">
                <!-- FOTO PERFIL EXTERNO -->
                <div
                    class="absolute left-1/2 top-[-55px] z-20 flex h-[110px] w-[110px] -translate-x-1/2 items-center justify-center overflow-hidden rounded-full bg-[#FF7608] outline outline-[6px] outline-[#FFEBC9]"
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
                </div>

                <!-- CARD -->
                <aside
                    class="relative flex h-[416px] w-full flex-col items-center rounded-[94px] bg-[#00BF63] px-[38px] pt-[102px] shadow-[0px_4px_4px_rgba(0,0,0,0.25)]"
                >
                    <h1
                        class="w-full mt-[-28px] truncate text-center font-['Nunito_Sans'] text-[32px] font-bold text-white"
                    >
                        {{ user.username }}
                    </h1>

                    <div
                        class="mt-[28px] h-[104px] w-[258px] rounded-[9px] border border-[#F9C49A] p-[10px]"
                    >
                        <p
                            v-if="user.bio"
                            class="line-clamp-4 text-center font-['Nunito_Sans'] text-[16px] font-semibold text-white"
                        >
                            {{ user.bio }}
                        </p>

                        <p
                            v-else
                            class="text-center font-['Nunito_Sans'] text-[16px] font-semibold text-white/70"
                        >
                            Sin biografía
                        </p>
                    </div>

                    <button
                        type="button"
                        class="mt-[36px] flex h-[70px] w-[179px] items-center justify-center rounded-[50px] bg-[#604646] px-[12px] py-[10px] shadow-[0px_4px_4px_rgba(0,0,0,0.25)] transition hover:scale-105 hover:opacity-90"
                        @click="sendMessage"
                    >
                        <span
                            class="cursor-pointer text-center font-['Nunito_Sans'] text-[20px] font-semibold text-white"
                        >
                            Enviar mensaje
                        </span>
                    </button>
                </aside>
            </div>

            <!-- CONTENIDO DERECHA -->
            <main class="w-full flex-1 pt-0 lg:pt-[40px]">
                <!-- INSIGNIAS -->
                <section>
                    <h2 class="font-['Nunito_Sans'] text-[48px] font-bold text-[#FF7608]">
                        Insignias
                    </h2>

                    <div class="mt-[16px] h-px w-full bg-[#FF7608]"></div>

                    <div
                        v-if="badges.length > 0"
                        class="mt-[39px] grid w-full grid-cols-2 gap-x-10 gap-y-8 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
                    >
                        <div
                            v-for="badge in badges"
                            :key="badge.id"
                            class="flex flex-col items-center gap-3 text-center"
                        >
                            <div class="h-[92px] w-[92px] overflow-hidden rounded-full border-[4px] border-[#FF7608] bg-white shadow-md">
                                <img
                                    v-if="badge.image"
                                    :src="badge.image"
                                    :alt="badge.name"
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
                                {{ badge.name }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="mt-[39px] flex w-full flex-col items-center justify-center gap-[12px]">
                        <img src="/images/nofound2.png" class="h-[250px] w-[240px]" />

                        <p class="text-center text-[22px] font-bold text-[#B4C5BD]">
                            ¡No hay información que mostrar!
                        </p>
                    </div>
                </section>

                <!-- POSTS -->
                <ProfileSection
                    title="Post publicados"
                    class="mt-[70px]"
                    :has-content="posts.length > 0"
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        <CardPublicacion
                            v-for="post in posts"
                            :key="post.id"
                            :post="post"
                        />
                    </div>
                </ProfileSection>
            </main>
        </div>
    </section>
</template>