<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

interface PostUser {
  id?: number
  username?: string
  profile_photo?: string | null
}

interface PostPhoto {
  id: number
  url: string
}

interface PostRating {
  rating: number
}

interface Post {
  id: number
  title: string
  description: string
  photos?: PostPhoto[]
  user?: PostUser
  ratings?: PostRating[]
  ratings_avg_rating?: number | string | null
  location?: {
    formatted_address: string | null
  } | null
}

const props = defineProps<{
  post: Post
}>()

function averageRating(): string {
  if (props.post.ratings_avg_rating !== undefined && props.post.ratings_avg_rating !== null) {
    return Number(props.post.ratings_avg_rating).toFixed(1)
  }

  if (!props.post.ratings?.length) {
    return '0.0'
  }

  const total = props.post.ratings.reduce((sum, rating) => {
    return sum + Number(rating.rating)
  }, 0)

  return (total / props.post.ratings.length).toFixed(1)
}

function userInitial(): string {
  return props.post.user?.username?.charAt(0).toUpperCase() ?? '?'
}
</script>

<template>
  <Link
    :href="`/publicaciones/${post.id}`"
    class="flex h-full w-full cursor-pointer flex-col gap-4 rounded-[2rem] bg-[#FDF0D9] p-5 shadow-sm transition-transform duration-300 hover:scale-105"
  >
    <!-- FOTO PRINCIPAL -->
    <img
      v-if="post.photos?.length"
      :src="post.photos[0].url"
      :alt="post.title"
      class="h-48 w-full rounded-[1.5rem] object-cover"
    />

    <div
      v-else
      class="flex h-48 w-full items-center justify-center rounded-[1.5rem] bg-[#FF7608]/10 text-sm font-semibold text-[#FF7608]"
    >
      Sin imagen
    </div>

    <div class="flex flex-col gap-2.5">

      <!-- USUARIO -->
      <div class="flex items-center gap-2">
        <img
          v-if="post.user?.profile_photo"
          :src="`/storage/${post.user.profile_photo}`"
          class="h-8 w-8 rounded-full object-cover"
        />

        <div
          v-else
          class="flex h-8 w-8 items-center justify-center rounded-full bg-[#FF7608] text-sm font-bold text-white"
        >
          {{ userInitial() }}
        </div>

        <span class="text-xs text-gray-500">
          Publicado por
          <span class="font-semibold text-[#604646]">
            {{ post.user?.username ?? 'Usuario' }}
          </span>
        </span>
      </div>

      <!-- TÍTULO -->
      <h2 class="line-clamp-2 text-2xl font-bold leading-tight text-[#604646]">
        {{ post.title }}
      </h2>

        <!-- LOCACIONES -->
        <p
        v-if="post.location?.formatted_address"
        class="line-clamp-1 text-xs font-semibold text-[#FF7608]"
        >
        📍 {{ post.location.formatted_address }}
        </p>

      <!-- RATING -->
      <div class="flex items-center gap-1.5 text-gray-700">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="#FF7608"
          class="h-5 w-5"
        >
          <path
            fill-rule="evenodd"
            d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.258 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.628 2.825c-.996.608-2.231-.289-1.96-1.425l1.258-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"
            clip-rule="evenodd"
          />
        </svg>

        <span class="text-lg font-semibold text-[#604646]">
          {{ averageRating() }}
        </span>
      </div>

      <!-- DESCRIPCIÓN -->
      <p class="line-clamp-3 text-sm italic leading-relaxed text-[#442F2F]">
        "{{ post.description }}"
      </p>

    </div>
  </Link>
</template>