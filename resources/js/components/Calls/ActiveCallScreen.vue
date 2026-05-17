<script setup lang="ts">
import UserAvatar from '@/components/UserAvatar.vue';

type CallType = 'voice' | 'video';

type CallUser = {
    id: number;
    username?: string;
    first_name?: string;
    last_name?: string;
    profile_photo?: string | null;
};

type GroupCallInfo = {
    id: number;
    name: string | null;
    photo: string | null;
};

defineProps<{
    callStatus: 'calling' | 'connected';
    currentCallType: CallType;
    groupCallActive: boolean;
    localMicEnabled: boolean;
    activeCallUser: CallUser | null;
    activeGroupCallInfo: GroupCallInfo | null;
    groupMembers: CallUser[];
}>();

const emit = defineEmits<{
    (e: 'toggle-mic'): void;
    (e: 'end-call'): void;
}>();

const localVideo = defineModel<HTMLVideoElement | null>('localVideo');
const remoteVideo = defineModel<HTMLVideoElement | null>('remoteVideo');

function setLocalVideoRef(el: unknown) {
    localVideo.value = el as HTMLVideoElement | null;
}

function setRemoteVideoRef(el: unknown) {
    remoteVideo.value = el as HTMLVideoElement | null;
}
</script>

<template>
    <div class="fixed inset-0 z-[99999] flex flex-col bg-[#111111]">
        <div
            v-if="currentCallType === 'video'"
            class="relative flex-1 overflow-hidden bg-black"
        >
            <video
                :ref="setRemoteVideoRef"
                autoplay
                playsinline
                class="h-full w-full object-cover"
            ></video>

            <video
                :ref="setLocalVideoRef"
                autoplay
                playsinline
                muted
                class="absolute right-4 top-4 h-[120px] w-[90px] rounded-[16px] bg-black object-cover shadow-lg md:right-6 md:top-6 md:h-[190px] md:w-[145px]"
            ></video>
        </div>

        <div
            v-else-if="groupCallActive"
            class="flex flex-1 flex-col overflow-hidden bg-[#FFF1D9] px-4 pb-4 pt-5 text-[#442F2F] sm:px-6 sm:pt-6"
        >
            <div class="mb-4 flex shrink-0 flex-col items-center text-center">
                <UserAvatar
                    :photo="activeGroupCallInfo?.photo ?? null"
                    className="h-[58px] w-[58px] border-2 border-[#FF7608] sm:h-[72px] sm:w-[72px]"
                    :alt="activeGroupCallInfo?.name ?? 'Grupo'"
                />

                <h2 class="mt-3 max-w-[280px] truncate text-[22px] font-bold sm:max-w-[520px] sm:text-[28px]">
                    {{ activeGroupCallInfo?.name ?? 'Grupo' }}
                </h2>

                <p class="mt-1 text-[13px] font-semibold text-[#604646]/70 sm:text-[15px]">
                    {{ groupMembers.length + 1 }} participante{{ groupMembers.length + 1 === 1 ? '' : 's' }} en llamada
                </p>
            </div>

            <div
                class="grid flex-1 auto-rows-fr gap-3 overflow-y-auto pb-2"
                :class="[
                    groupMembers.length <= 1
                        ? 'grid-cols-1'
                        : groupMembers.length === 2
                            ? 'grid-cols-1 sm:grid-cols-2'
                            : 'grid-cols-2 lg:grid-cols-3'
                ]"
            >
                <div
                    v-for="member in groupMembers"
                    :key="member.id"
                    class="relative flex min-h-[135px] flex-col items-center justify-center rounded-[22px] bg-white px-3 py-4 shadow-[0_8px_24px_rgba(0,0,0,0.12)] sm:min-h-[190px]"
                >
                    <UserAvatar
                        :photo="member.profile_photo ?? null"
                        className="h-[62px] w-[62px] border-2 border-white/15 sm:h-[86px] sm:w-[86px]"
                        :alt="member.username ?? 'Usuario'"
                    />

                    <p class="mt-3 max-w-full truncate text-center text-[13px] font-bold text-[#442F2F] sm:text-[16px]">
                        {{ member.username ?? 'Usuario' }}
                    </p>

                    <div class="absolute bottom-3 right-3 rounded-full bg-[#FFE3BF] px-2 py-1 text-[11px] font-bold text-[#604646]">
                        Voz
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else
            class="flex flex-1 flex-col items-center justify-center bg-[#FFF1D9] px-6"
        >
            <UserAvatar
                :photo="activeCallUser?.profile_photo ?? null"
                className="h-[110px] w-[110px]"
                :alt="activeCallUser?.username ?? 'Usuario'"
            />

            <h2 class="mt-5 text-center text-[24px] font-bold text-[#442F2F]">
                {{ activeCallUser?.username ?? 'Usuario' }}
            </h2>
        </div>

        <div
            class="flex shrink-0 flex-col items-center gap-4 bg-white px-4 pb-[calc(env(safe-area-inset-bottom)+28px)] pt-6 shadow-[0_-8px_24px_rgba(0,0,0,0.22)]"
        >
            <p class="text-center text-[16px] font-bold text-[#604646]">
                {{ groupCallActive ? 'Llamada grupal en curso' : callStatus === 'calling' ? 'Llamando...' : 'Llamada en curso' }}
            </p>

            <div class="flex flex-wrap justify-center gap-4">
                <button
                    type="button"
                    class="rounded-full bg-[#FF7608] px-6 py-3 text-[15px] font-bold text-white shadow-md transition hover:scale-105 sm:px-8 sm:text-[16px]"
                    @click="emit('toggle-mic')"
                >
                    {{ localMicEnabled ? 'Silenciar' : 'Activar audio' }}
                </button>

                <button
                    type="button"
                    class="rounded-full bg-red-500 px-8 py-3 text-[15px] font-bold text-white shadow-md transition hover:scale-105 sm:px-10 sm:text-[16px]"
                    @click="emit('end-call')"
                >
                    Colgar
                </button>
            </div>
        </div>
    </div>
</template>