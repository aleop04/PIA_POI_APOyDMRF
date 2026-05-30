<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type RewardType = 'badge' | 'discount';

type Reward = {
    id: number;
    name: string;
    description: string | null;
    type: RewardType;
    cost_points: number;
    stock: number | null;
    image: string | null;
    discount_value: number | null;
    is_active: boolean;
};

type RewardRedemption = {
    id: number;
    user_id: number;
    reward_id: number;
    points_spent: number;
    redeemed_at: string;
    reward: Reward;
};

const props = defineProps<{
    userPoints: number;
    rewards: Reward[];
    redemptions: RewardRedemption[];
}>();

const currentPoints = ref(props.userPoints);
const availableRewards = ref<Reward[]>([...props.rewards]);
const userRedemptions = ref<RewardRedemption[]>([...props.redemptions]);

const redeemingRewardId = ref<number | null>(null);
const successMessage = ref('');
const errorMessage = ref('');

const badgeRewards = computed(() => {
    return availableRewards.value.filter((reward) => reward.type === 'badge');
});

const discountRewards = computed(() => {
    return availableRewards.value.filter((reward) => reward.type === 'discount');
});

const acquiredBadges = computed(() => {
    return userRedemptions.value
        .filter((redemption) => redemption.reward?.type === 'badge')
        .map((redemption) => redemption.reward);
});

const acquiredDiscounts = computed(() => {
    return userRedemptions.value
        .filter((redemption) => redemption.reward?.type === 'discount');
});

function csrfToken() {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';
}

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

function canAfford(reward: Reward) {
    return currentPoints.value >= reward.cost_points;
}

async function redeemReward(reward: Reward) {
    if (redeemingRewardId.value !== null) {
        return;
    }

    if (!canAfford(reward)) {
        errorMessage.value = 'No tienes suficientes puntos para canjear esta recompensa.';
        successMessage.value = '';

        setTimeout(() => {
            errorMessage.value = '';
        }, 3000);

        return;
    }

    const confirmed = confirm(`¿Canjear "${reward.name}" por ${reward.cost_points} puntos?`);

    if (!confirmed) {
        return;
    }

    redeemingRewardId.value = reward.id;
    successMessage.value = '';
    errorMessage.value = '';

    try {
        const res = await fetch(`/recompensas/${reward.id}/canjear`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
            },
        });

        const data = await res.json();

        if (!res.ok || !data.ok) {
            throw new Error(data.message ?? 'No se pudo canjear la recompensa.');
        }

        currentPoints.value = data.userPoints;

        if (reward.type === 'badge') {
            availableRewards.value = availableRewards.value.filter(
                (item) => item.id !== reward.id,
            );
        }

        successMessage.value = data.message ?? 'Recompensa canjeada correctamente.';
        setTimeout(() => {
            successMessage.value = '';
        }, 3000);

        router.reload({
            only: ['redemptions', 'rewards', 'userPoints'],
            onSuccess: () => {
                userRedemptions.value = [...props.redemptions];
                availableRewards.value = [...props.rewards];
                currentPoints.value = props.userPoints;
            },
        });
    } catch (error) {
        console.error(error);
        errorMessage.value = error instanceof Error
            ? error.message
            : 'No se pudo canjear la recompensa.';
        
        setTimeout(() => {
            errorMessage.value = '';
        }, 3000);

    } finally {
        redeemingRewardId.value = null;
    }
}

function goBackToProfile() {
    router.visit('/perfil');
}
</script>

<template>
    <Head title="Recompensas" />

    <section class="min-h-screen px-4 py-8 md:px-8 lg:px-10">
        <main class="mx-auto w-full max-w-[1300px]">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-[34px] font-extrabold text-[#FF7608]">
                        Recompensas
                    </h1>

                    <p class="mt-2 max-w-[650px] text-[16px] font-semibold text-[#442F2F]">
                        Canjea tus puntos por insignias para tu perfil o descuentos especiales.
                    </p>
                </div>

                <button
                    type="button"
                    @click="goBackToProfile"
                    class="h-[45px] rounded-full bg-[#604646] px-6 text-[15px] font-bold text-white shadow-md transition hover:scale-[1.03] active:scale-[0.97]"
                >
                    Volver al perfil
                </button>
            </div>

            <!-- Puntos -->
            <div
                class="mt-8 rounded-[35px] bg-cover bg-center bg-no-repeat px-7 py-6 text-white shadow-md"
                style="background-image: url('/images/gradient.jpg');"
            >
                <p class="text-[18px] font-bold">
                    Tus puntos actuales
                </p>

                <p class="mt-1 text-[46px] font-extrabold leading-none">
                    {{ currentPoints }}
                </p>

                <p class="mt-2 text-[15px] font-semibold text-white/90">
                    Gana más puntos completando tareas grupales con todos los miembros del chat.
                </p>
            </div>

            <!-- Mensajes -->
            <p
                v-if="successMessage"
                class="mt-6 rounded-[18px] bg-[#DFF5E8] px-5 py-3 text-center text-[15px] font-bold text-[#0E6C3F]"
            >
                {{ successMessage }}
            </p>

            <p
                v-if="errorMessage"
                class="mt-6 rounded-[18px] bg-red-100 px-5 py-3 text-center text-[15px] font-bold text-red-700"
            >
                {{ errorMessage }}
            </p>

            <!-- Insignias disponibles -->
            <section class="mt-10">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-[26px] font-extrabold text-[#442F2F]">
                            Insignias disponibles
                        </h2>

                        <p class="mt-1 text-[14px] font-semibold text-[#8A7474]">
                            Solo puedes adquirir cada insignia una vez.
                        </p>
                    </div>
                </div>

                <div
                    v-if="badgeRewards.length > 0"
                    class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <article
                        v-for="reward in badgeRewards"
                        :key="reward.id"
                        class="flex min-h-[245px] flex-col rounded-[30px] bg-[#FFFBED] p-5 shadow-md"
                    >
                        <div class="flex items-start gap-4">
                            <div class="flex h-[80px] w-[80px] shrink-0 items-center justify-center overflow-hidden rounded-full border-[3px] border-[#FF7608] bg-white">
                                <img
                                    v-if="rewardImageUrl(reward.image)"
                                    :src="rewardImageUrl(reward.image)!"
                                    :alt="reward.name"
                                    class="h-full w-full object-cover"
                                />

                                <span v-else class="text-[34px]">
                                    🏅
                                </span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <h3 class="break-words text-[18px] font-extrabold text-[#FF7608]">
                                    {{ reward.name }}
                                </h3>

                                <p class="mt-1 text-[13px] font-semibold text-[#8A7474]">
                                    {{ reward.description ?? 'Insignia coleccionable.' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-auto pt-5">
                            <p class="mb-3 text-[18px] font-extrabold text-[#442F2F]">
                                {{ reward.cost_points }} puntos
                            </p>

                            <button
                                type="button"
                                :disabled="redeemingRewardId === reward.id || !canAfford(reward)"
                                @click="redeemReward(reward)"
                                class="h-[43px] w-full rounded-full bg-[#FF7608] text-[15px] font-bold text-white shadow-md transition hover:scale-[1.02] disabled:cursor-not-allowed disabled:bg-[#B4C5BD] disabled:hover:scale-100"
                            >
                                <span v-if="redeemingRewardId === reward.id">
                                    Canjeando...
                                </span>

                                <span v-else-if="!canAfford(reward)">
                                    Puntos insuficientes
                                </span>

                                <span v-else>
                                    Canjear
                                </span>
                            </button>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="mt-5 rounded-[30px] bg-[#FFFBED] px-6 py-10 text-center shadow-sm"
                >
                    <p class="text-[18px] font-bold text-[#B4C5BD]">
                        Ya adquiriste todas las insignias disponibles.
                    </p>
                </div>
            </section>

            <!-- Descuentos disponibles -->
            <section class="mt-12">
                <h2 class="text-[26px] font-extrabold text-[#442F2F]">
                    Descuentos disponibles
                </h2>

                <p class="mt-1 text-[14px] font-semibold text-[#8A7474]">
                    Puedes adquirir el mismo descuento varias veces mientras tengas puntos suficientes.
                </p>

                <div
                    v-if="discountRewards.length > 0"
                    class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <article
                        v-for="reward in discountRewards"
                        :key="reward.id"
                        class="flex min-h-[230px] flex-col rounded-[30px] bg-[#FFF5C2] p-5 shadow-md"
                    >
                        <div class="flex items-center gap-4">
                            <div class="flex h-[70px] w-[70px] shrink-0 items-center justify-center overflow-hidden rounded-full border-[3px] border-[#00BF63] bg-white">
                                <img
                                    v-if="rewardImageUrl(reward.image)"
                                    :src="rewardImageUrl(reward.image)!"
                                    :alt="reward.name"
                                    class="h-full w-full object-cover"
                                />

                                <span v-else class="text-[32px]">
                                    🎟️
                                </span>
                            </div>

                            <div>
                                <h3 class="text-[18px] font-extrabold text-[#0E6C3F]">
                                    {{ reward.name }}
                                </h3>

                                <p
                                    v-if="reward.discount_value"
                                    class="mt-1 text-[13px] font-bold text-[#442F2F]"
                                >
                                    {{ reward.discount_value }}% de descuento
                                </p>
                            </div>
                        </div>

                        <p class="mt-4 text-[13px] font-semibold text-[#8A7474]">
                            {{ reward.description ?? 'Descuento canjeable.' }}
                        </p>

                        <div class="mt-auto pt-5">
                            <p class="mb-3 text-[18px] font-extrabold text-[#442F2F]">
                                {{ reward.cost_points }} puntos
                            </p>

                            <button
                                type="button"
                                :disabled="redeemingRewardId === reward.id || !canAfford(reward)"
                                @click="redeemReward(reward)"
                                class="h-[43px] w-full rounded-full bg-[#00BF63] text-[15px] font-bold text-white shadow-md transition hover:scale-[1.02] disabled:cursor-not-allowed disabled:bg-[#B4C5BD] disabled:hover:scale-100"
                            >
                                <span v-if="redeemingRewardId === reward.id">
                                    Canjeando...
                                </span>

                                <span v-else-if="!canAfford(reward)">
                                    Puntos insuficientes
                                </span>

                                <span v-else>
                                    Canjear
                                </span>
                            </button>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Recompensas adquiridas -->
            <section class="mt-12 grid items-start gap-6 lg:grid-cols-2">
                <div class="rounded-[30px] bg-[#FFFBED] p-6 shadow-md">
                    <h2 class="text-[22px] font-extrabold text-[#442F2F]">
                        Tus insignias adquiridas
                    </h2>

                    <div
                        v-if="acquiredBadges.length > 0"
                        class="mt-5 grid grid-cols-3 gap-4 sm:grid-cols-4"
                    >
                        <div
                            v-for="badge in acquiredBadges"
                            :key="badge.id"
                            class="flex flex-col items-center gap-2 text-center"
                        >
                            <div class="h-[70px] w-[70px] overflow-hidden rounded-full border-[3px] border-[#FF7608] bg-white">
                                <img
                                    v-if="rewardImageUrl(badge.image)"
                                    :src="rewardImageUrl(badge.image)!"
                                    :alt="badge.name"
                                    class="h-full w-full object-cover"
                                />

                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-[28px]"
                                >
                                    🏅
                                </div>
                            </div>

                            <p class="text-[12px] font-bold text-[#FF7608]">
                                {{ badge.name }}
                            </p>
                        </div>
                    </div>

                    <p
                        v-else
                        class="mt-5 text-[15px] font-bold text-[#B4C5BD]"
                    >
                        Todavía no tienes insignias adquiridas.
                    </p>
                </div>

                <div class="rounded-[30px] bg-[#FFFBED] p-6 shadow-md">
                    <h2 class="text-[22px] font-extrabold text-[#442F2F]">
                        Tus descuentos adquiridos
                    </h2>

                    <div
                        v-if="acquiredDiscounts.length > 0"
                        class="mt-5 flex max-h-[280px] flex-col gap-3 overflow-y-auto pr-1 scrollbar-thin"
                    >
                        <div
                            v-for="redemption in acquiredDiscounts"
                            :key="redemption.id"
                            class="rounded-[20px] bg-[#FFFBED] px-4 py-3"
                        >
                            <p class="font-bold text-[#0E6C3F]">
                                {{ redemption.reward.name }}
                            </p>

                            <p class="text-[13px] font-semibold text-[#8A7474]">
                                Canjeado por {{ redemption.points_spent }} puntos
                            </p>
                        </div>
                    </div>

                    <p
                        v-else
                        class="mt-5 text-[15px] font-bold text-[#B4C5BD]"
                    >
                        Todavía no tienes descuentos adquiridos.
                    </p>
                </div>
            </section>
        </main>
    </section>
</template>

<style scoped>
.scrollbar-thin {
    scrollbar-width: none;
}

.scrollbar-thin::-webkit-scrollbar {
    display: none;
}
</style>