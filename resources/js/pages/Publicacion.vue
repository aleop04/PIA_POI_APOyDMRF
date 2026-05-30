<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import { ref, computed } from 'vue'
import Comentario from '@/components/Posts/Comentario.vue'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
  DialogClose,
} from '@/components/ui/dialog'

interface Post {
  id: number
  title: string
  location?: {
    formatted_address: string | null
    lat: number | null
    lng: number | null
    place_id: string | null
  } | null
  description: string
  available_from: string | null
  available_to: string | null
  opening_days: string[]
  photos: { id: number; url: string }[]
  comments: PostComment[]
  user?: {
    id: number
    username: string
    }
}

interface CommentUser {
  id: number
  username: string
  profile_photo: string | null
}

interface PostComment {
  id: number
  comment: string
  created_at: string
  is_edited?: boolean
  user: CommentUser
}

const props = defineProps<{
  post: Post
  avgRating: number
  userRating: number | null
  isOwner: boolean
  authUserId: number
}>()

// para fotos
const desktopPhotoGridClass = computed(() => {
    const count = props.post.photos.length;

    if (count === 1) {
        return 'md:grid-cols-1 md:grid-rows-1';
    }

    if (count === 2) {
        return 'md:grid-cols-2 md:grid-rows-1';
    }

    if (count === 3) {
        return 'md:grid-cols-4 md:grid-rows-2';
    }

    return 'md:grid-cols-4 md:grid-rows-2';
});

function desktopPhotoItemClass(index: number) {
    const count = props.post.photos.length;

    if (count === 1) {
        return 'md:col-span-1 md:row-span-1 rounded-3xl';
    }

    if (count === 2) {
        return index === 0
            ? 'rounded-l-3xl'
            : 'rounded-r-3xl';
    }

    if (count === 3) {
        if (index === 0) {
            return 'md:col-span-2 md:row-span-2 rounded-l-3xl';
        }

        if (index === 1) {
            return 'md:col-span-2 md:row-span-1 rounded-tr-3xl';
        }

        return 'md:col-span-2 md:row-span-1 rounded-br-3xl';
    }

    if (index === 0) {
        return 'md:col-span-2 md:row-span-2 rounded-l-3xl';
    }

    if (index === 1) {
        return 'md:col-span-2 md:row-span-1 rounded-tr-3xl';
    }

    if (index === 2) {
        return 'md:col-span-1 md:row-span-1';
    }

    return 'md:col-span-1 md:row-span-1 rounded-br-3xl';
}

// estado
const comentarios = ref(props.post.comments || [])
const nuevoComentario = ref('')

const rating = ref(props.userRating ?? 0)
const hoverRating = ref(0)
const avgRating = ref(props.avgRating ?? 0)

const editingCommentId = ref<number | null>(null)

const userOwnComment = computed(() => {
  return comentarios.value.find((c) => c.user.id === props.authUserId) ?? null
})

const commentInputDisabled = computed(() => {
  return Boolean(userOwnComment.value) && editingCommentId.value === null
})

const commentPlaceholder = computed(() => {
  if (editingCommentId.value) {
    return 'Edita tu comentario...'
  }

  if (userOwnComment.value) {
    return 'Ya comentaste. Usa los tres puntos para editar o eliminar.'
  }

  return 'Escribe un comentario...'
})

const showDeleteDialog = ref(false)
const deletingPost = ref(false)

const previewPhoto = ref<{ id: number; url: string } | null>(null)

function openPhotoPreview(photo: { id: number; url: string }) {
  previewPhoto.value = photo
}

function closePhotoPreview() {
  previewPhoto.value = null
}

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

async function deletePost() {
    deletingPost.value = true

    try {
        const { data } = await axios.delete(`/publicaciones/${props.post.id}`)

        if (data.ok) {
            router.visit('/perfil')
        }
    } catch (error: any) {
        const message =
            error.response?.data?.message ??
            'Ocurrió un error al eliminar la publicación.'

        alert(message)
    } finally {
        deletingPost.value = false
        showDeleteDialog.value = false
    }
}

// dias
const days = ['L', 'M', 'X', 'J', 'V', 'S', 'D']
const dayLabels: Record<string, string> = {
  L: 'Lun',
  M: 'Mar',
  X: 'Mié',
  J: 'Jue',
  V: 'Vie',
  S: 'Sáb',
  D: 'Dom',
}

function formatDuration(from: string | null, to: string | null): string | null {
  if (!from || !to) {
return null
}

  const [sh, sm] = from.split(':').map(Number)
  const [eh, em] = to.split(':').map(Number)

  const mins = eh * 60 + em - (sh * 60 + sm)

  if (mins <= 0) {
return null
}

  const h = Math.floor(mins / 60)
  const m = mins % 60

  return h > 0 ? `${h}h${m ? ` ${m}min` : ''}` : `${m}min`
}

function startEditComment(comment: PostComment) {
    editingCommentId.value = comment.id
    nuevoComentario.value = comment.comment
}

function cancelEditComment() {
    editingCommentId.value = null
    nuevoComentario.value = ''
}

// comentarios
async function enviarComentario() {
    if (!nuevoComentario.value.trim() && rating.value === 0) {
        return
    }

    try {
        if (editingCommentId.value) {
            if (!nuevoComentario.value.trim()) {
                alert('El comentario no puede estar vacío.')

                return
            }

            const { data } = await axios.patch(
                `/publicaciones/comentarios/${editingCommentId.value}`,
                {
                    comment: nuevoComentario.value,
                }
            )

            if (data.ok) {
                const index = comentarios.value.findIndex(
                    (c) => c.id === editingCommentId.value
                )

                if (index !== -1) {
                    comentarios.value[index] = data.comment
                }

                editingCommentId.value = null
                nuevoComentario.value = ''
            }
        } else {
            if (nuevoComentario.value.trim()) {
                if (userOwnComment.value) {
                    alert('Ya comentaste esta publicación. Puedes editar o eliminar tu comentario.')

                    return
                }

                const { data } = await axios.post(
                    `/publicaciones/${props.post.id}/comentarios`,
                    {
                        comment: nuevoComentario.value,
                    }
                )

                if (data.ok) {
                    comentarios.value.unshift(data.comment)
                    nuevoComentario.value = ''
                }
            }
        }

        if (rating.value > 0) {
            await calificarPost(rating.value)
        }

    } catch (error: any) {
        const message =
            error.response?.data?.message ??
            'Ocurrió un error al enviar.'

        alert(message)
    }
}

async function deleteComment(comment: PostComment) {
    const confirmed = confirm('¿Eliminar este comentario?')

    if (!confirmed) {
return
}

    try {
        const { data } = await axios.delete(
            `/publicaciones/comentarios/${comment.id}`
        )

        if (data.ok) {
            comentarios.value = comentarios.value.filter(
                (c) => c.id !== comment.id
            )

            if (editingCommentId.value === comment.id) {
                editingCommentId.value = null
                nuevoComentario.value = ''
            }
        }
    } catch (error: any) {
        const message =
            error.response?.data?.message ??
            'Ocurrió un error al eliminar el comentario.'

        alert(message)
    }
}

async function calificarPost(value: number) {
    try {
        const { data } = await axios.post(
            `/publicaciones/${props.post.id}/valorar`,
            {
                rating: value,
            }
        );

        if (data.ok) {
            rating.value = data.userRating;
            avgRating.value = data.avgRating;
        }
    } catch (error: any) {
        const message =
            error.response?.data?.message ??
            'Ocurrió un error al valorar la publicación.';

        alert(message);
    }
}
</script>

<template>
  <Head :title="post.title" />

    <section class="px-4 py-6 md:px-8 lg:px-10">
        <main class="mx-auto grid w-full max-w-[1500px] items-start gap-6 pb-8 lg:grid-cols-2">

            <!-- FOTOS -->
            <div class="lg:col-span-2">

            <template v-if="post.photos.length">

                <!-- Desktop / Laptop -->
                <div
                    :class="[
                        'hidden overflow-hidden rounded-3xl md:grid md:h-[360px] md:gap-2',
                        desktopPhotoGridClass,
                    ]"
                >
                    <div
                        v-for="(photo, index) in post.photos"
                        :key="photo.id"
                        :class="[
                            'group relative flex cursor-pointer items-center justify-center overflow-hidden bg-[#FF7608] transition hover:brightness-105',
                            desktopPhotoItemClass(index),
                        ]"
                        @click="openPhotoPreview(photo)"
                    >
                        <img
                            :src="photo.url"
                            class="h-full w-full object-cover"
                        />
                    </div>
                </div>

                <!-- Celular -->
                <div class="md:hidden">

                <div class="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2">

                    <div
                    v-for="photo in post.photos"
                    :key="photo.id"
                    class="h-72 min-w-full snap-center overflow-hidden rounded-3xl bg-[#FF7608]"
                    @click="openPhotoPreview(photo)"
                    >
                    <img
                        :src="photo.url"
                        class="h-full w-full object-cover"
                    />
                    </div>

                </div>

                <p class="mt-2 text-center text-xs text-gray-400">
                    Desliza para ver las fotos
                </p>

                </div>

            </template>

            <div
                v-else
                class="flex h-80 items-center justify-center rounded-3xl bg-[#FF7608]/20 text-sm font-semibold text-[#FF7608]/60"
            >
                Sin fotos
            </div>

            </div>

            <!-- INFORMACIÓN -->
            <div class="rounded-2xl border border-[#f0dfc0] bg-[#fff8ee] p-5 shadow-md">

            <div class="mb-4 flex flex-wrap items-center gap-3">

                <div class="min-w-0 flex-1">

                <div class="flex flex-wrap items-center gap-2">

                    <h1 class="text-xl font-bold text-gray-800">
                    {{ post.title }}
                    </h1>

                    <div class="flex items-center gap-1 text-[#FF7608]">
                    ⭐
                    <span class="text-sm font-bold">
                        {{ Number(avgRating).toFixed(1) }}
                    </span>
                    </div>

                </div>

                <p class="text-sm text-gray-500">
                    Publicado por:

                    <button
                        type="button"
                        class="font-medium text-[#FF7608] transition hover:underline"
                        @click="goToUserProfile(post.user?.id)"
                    >
                        {{ post.user?.username }}
                    </button>
                </p>

                <button
                    v-if="isOwner"
                    type="button"
                    @click="showDeleteDialog = true"
                    class="mt-3 rounded-full bg-red-500 px-4 py-2 text-sm font-semibold text-white transition hover:brightness-105"
                >
                    Eliminar publicación
                </button>

                <p
                    v-if="post.location?.formatted_address"
                    class="mt-3 flex items-center gap-1 text-sm font-medium text-[#FF7608]"
                >
                    📍
                    <span class="line-clamp-1">
                    {{ post.location.formatted_address }}
                    </span>
                </p>

                </div>
            </div>

            <!-- DÍAS -->
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-400">
                Días
                </span>

                <span
                v-for="d in days"
                :key="d"
                :title="dayLabels[d]"
                :class="[
                    'flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold',
                    (post.opening_days || []).includes(d)
                    ? 'bg-[#FF7608] text-white'
                    : 'border border-gray-200 bg-white text-gray-300'
                ]"
                >
                {{ d }}
                </span>
            </div>

            <!-- HORARIO -->
            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                ⏰

                <span class="rounded-lg border bg-white px-2 py-1">
                {{ post.available_from }}
                </span>

                <span class="text-gray-400">
                —
                </span>

                <span class="rounded-lg border bg-white px-2 py-1">
                {{ post.available_to }}
                </span>

                <span
                v-if="formatDuration(post.available_from, post.available_to)"
                class="rounded-lg bg-[#FF7608]/10 px-2 py-1 font-semibold text-[#FF7608]"
                >
                Duración: {{ formatDuration(post.available_from, post.available_to) }}
                </span>
            </div>
            </div>

            <!-- DESCRIPCIÓN -->
            <div class="min-w-0 max-w-full overflow-hidden rounded-2xl border border-dashed border-[#FF7608]/60 bg-white p-4 shadow-sm">
                <p class="min-h-[185px] max-w-full whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-700 lg:min-h-full">
                    {{ post.description }}
                </p>
            </div>

        </main>
    </section>

    <!-- COMENTARIOS -->
    <section class="mx-auto w-full max-w-[1500px] px-4 pb-32 md:px-8 lg:px-10">

        <div class="rounded-none bg-transparent px-4 py-4 md:px-8">

            <!-- ENCABEZADO / VALORACIONES -->
            <div class="mb-4 flex flex-col gap-3">

                <div
                    :class="[
                        'pb-2',
                        isOwner ? 'border-b border-[#FF7608]' : ''
                    ]"
                >
                    <h2 class="text-[22px] font-bold text-[#442F2F]">
                        Comentarios
                    </h2>
                </div>

                <!-- SOLO LOS USUARIOS QUE NO SON DUEÑOS PUEDEN VALORAR Y COMENTAR -->
                <template v-if="!isOwner">

                    <div class="flex gap-1">
                        <button
                            v-for="n in 5"
                            :key="n"
                            type="button"
                            @click="rating = n"
                            @mouseenter="hoverRating = n"
                            @mouseleave="hoverRating = 0"
                            class="transition hover:scale-110"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                class="h-7 w-7"
                                :class="(hoverRating || rating) >= n ? 'text-yellow-400' : 'text-gray-300'"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.258 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.628 2.825c-.996.608-2.231-.289-1.96-1.425l1.258-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="flex gap-2">
                        <input
                            v-model="nuevoComentario"
                            type="text"
                            :placeholder="commentPlaceholder"
                            :disabled="commentInputDisabled"
                            class="flex-1 rounded-2xl border border-[#FF7608] bg-[#fff8ee] px-4 py-2 text-sm text-[#442F2F] shadow-md outline-none placeholder:text-gray-400 disabled:cursor-not-allowed disabled:opacity-70"
                            @keydown.enter="enviarComentario"
                        />

                        <button
                            @click="enviarComentario"
                            class="rounded-full bg-[#FF7608] px-4 py-2 text-sm font-semibold text-white"
                        >
                            {{ editingCommentId ? 'Actualizar' : 'Enviar' }}
                        </button>

                        <button
                            v-if="editingCommentId"
                            type="button"
                            @click="cancelEditComment"
                            class="rounded-full bg-red-500 px-4 py-2 text-sm font-semibold text-white"
                        >
                            Cancelar
                        </button>
                    </div>

                </template>

            </div>

            <div class="flex flex-col gap-3">
                <Comentario
                    v-for="c in comentarios"
                    :key="c.id"
                    :comentario="c"
                    :auth-user-id="authUserId"
                    @edit="startEditComment"
                    @delete="deleteComment"
                />
            </div>

        </div>

    </section>

    <Dialog
    :open="previewPhoto !== null"
    @update:open="previewPhoto = $event ? previewPhoto : null"
    >
    <DialogContent class="max-w-5xl border-none bg-transparent p-0 shadow-none">

        <div class="relative flex max-h-[85vh] items-center justify-center rounded-2xl bg-black/90 p-4">

        <button
            type="button"
            class="absolute top-3 right-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-lg font-bold text-black transition hover:bg-white"
            @click="closePhotoPreview"
        >
            ✕
        </button>

        <img
            v-if="previewPhoto"
            :src="previewPhoto.url"
            class="max-h-[80vh] max-w-full rounded-xl object-contain"
        />

        </div>

    </DialogContent>
    </Dialog>

    <Dialog
        :open="showDeleteDialog"
        @update:open="showDeleteDialog = $event"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    ¿Eliminar actividad?
                </DialogTitle>

                <DialogDescription>
                    ¿Seguro que quieres eliminar esta actividad? Esta acción quitará la publicación de tu perfil y de las búsquedas.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <DialogClose as-child>
                    <button
                        type="button"
                        class="rounded-full border border-gray-300 px-5 py-2 text-sm font-semibold transition hover:bg-gray-100"
                    >
                        Cancelar
                    </button>
                </DialogClose>

                <button
                    type="button"
                    @click="deletePost"
                    :disabled="deletingPost"
                    class="rounded-full bg-red-500 px-5 py-2 text-sm font-semibold text-white transition hover:brightness-105 disabled:opacity-50"
                >
                    {{ deletingPost ? 'Eliminando...' : 'Eliminar' }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

</template>