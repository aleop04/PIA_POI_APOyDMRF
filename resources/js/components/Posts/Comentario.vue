<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'

interface CommentUser {
    id: number;
    username: string;
    profile_photo: string | null;
}

interface PostComment {
    id: number;
    comment: string;
    created_at: string;
    is_edited?: boolean;
    user: CommentUser;
    user_rating?: number | null;
}

const props = defineProps<{
    comentario: PostComment;
    authUserId: number;
}>();

const emit = defineEmits<{
    edit: [comment: PostComment];
    delete: [comment: PostComment];
}>();

const menuOpen = ref(false);

function goToUserProfile(userId?: number) {
    if (!userId) {
return
}

    if (userId === props.authUserId) {
        router.visit('/perfil')

        return
    }

    router.visit(`/usuarios/${userId}`)
}

function timeAgo(date: string): string {

    const diff = Math.floor(
        (Date.now() - new Date(date).getTime()) / 1000
    );

    if (diff < 60) {
        return 'ahora';
    }

    if (diff < 3600) {
        return `${Math.floor(diff / 60)}min`;
    }

    if (diff < 86400) {
        return `${Math.floor(diff / 3600)}h`;
    }

    return `${Math.floor(diff / 86400)}d`;
}

function editComment() {
    menuOpen.value = false;
    emit('edit', props.comentario);
}

function deleteComment() {
    menuOpen.value = false;
    emit('delete', props.comentario);
}
</script>

<template>

    <div class="group relative flex gap-3 py-3 pr-10">

        <!-- FOTO DE PERFIL -->
        <img
            v-if="comentario.user.profile_photo"
            :src="`/storage/${comentario.user.profile_photo}`"
            class="h-9 w-9 shrink-0 rounded-full object-cover"
        />

        <!-- INICIAL SI NO HAY FOTO -->
        <div
            v-else
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#FF7608] text-sm font-bold text-white"
        >
            {{ comentario.user.username.charAt(0).toUpperCase() }}
        </div>

        <!-- CONTENIDO -->
        <div class="flex flex-col gap-1">

            <!-- HEADER -->
            <div class="flex flex-wrap items-center gap-2">

                <!-- USERNAME -->
                <button
                    type="button"
                    class="font-semibold text-[#442F2F] transition hover:text-[#FF7608] hover:underline"
                    @click="goToUserProfile(comentario.user.id)"
                >
                    {{ comentario.user.username }}
                </button>

                <!-- TIEMPO -->
                <span class="text-xs text-gray-400">
                    {{ timeAgo(comentario.created_at) }}
                </span>

                <span
                    v-if="comentario.is_edited"
                    class="text-xs text-gray-400"
                >
                    editado
                </span>

            </div>

            <!-- COMENTARIO -->
            <p class="break-words text-sm font-medium text-[#442F2F]">
                {{ comentario.comment }}
            </p>

        </div>

        <div
            v-if="comentario.user.id === authUserId"
            class="absolute top-3 right-0"
        >
            <button
                type="button"
                @click="menuOpen = !menuOpen"
                class="hidden h-7 w-7 items-center justify-center rounded-full text-[#442F2F] transition hover:bg-[#FF7608]/10 group-hover:flex"
                title="Opciones"
            >
                ⋯
            </button>

            <div
                v-if="menuOpen"
                class="absolute right-0 z-20 mt-1 w-32 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg"
            >
                <button
                    type="button"
                    @click="editComment"
                    class="block w-full px-4 py-2 text-left text-sm text-[#442F2F] hover:bg-[#fff8ee]"
                >
                    Editar
                </button>

                <button
                    type="button"
                    @click="deleteComment"
                    class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                >
                    Eliminar
                </button>
            </div>
        </div>

    </div>

</template>