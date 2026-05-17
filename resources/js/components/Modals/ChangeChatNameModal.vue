<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{
  open: boolean;
  initialName: string | null;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'save', name: string): void;
}>();

const name = ref('');

watch(
  () => props.open,
  (val) => {
    if (val) {
name.value = props.initialName || '';
}
  },
  { immediate: true }
);

function close() {
  emit('close');
}

function save() {
  if (!name.value.trim()) {
return;
}

  emit('save', name.value.trim());
}
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 px-4"
  >
    <div
      class="relative w-full max-w-[683px] rounded-[32px] bg-[#FFFBED] px-6 py-8 shadow-[0px_15px_4px_rgba(0,0,0,0.25)] sm:rounded-[65px] sm:px-[65px] sm:pb-[57px] sm:pt-[183px]"
    >
      <!-- Header -->
      <div
        class="mb-6 flex items-start justify-between gap-4 sm:absolute sm:left-[97px] sm:top-[17px] sm:mb-0 sm:w-[527px]"
      >
        <div class="text-center font-['Nunito_Sans'] text-[18px] font-bold sm:text-[24px]">
          <p class="text-[#0E6C3F]">Cambiar nombre de chat</p>
          <p class="text-[#442F2F]">
            Si cambias el nombre de un chat en grupo, se modificará para todos los participantes
          </p>
        </div>

        <button
          type="button"
          @click="close"
          class="shrink-0 transition hover:scale-105 hover:opacity-80"
        >
          <img src="/icons/X circle.svg" class="h-[35px] w-[35px]" />
        </button>
      </div>

      <div class="mb-6 h-[1px] w-full bg-[#442F2F] sm:absolute sm:left-0 sm:top-[137px] sm:mb-0" />

      <div class="flex w-full flex-col gap-[36px]">
        <input
          v-model="name"
          type="text"
          placeholder="Nombre del grupo"
          class="h-[57px] w-full rounded-[18px] border border-[#D4BCBC] px-[15px] font-['Nunito_Sans'] text-[20px] font-bold text-[#AF6123] outline-none sm:text-[24px]"
        />

        <div class="flex justify-end gap-[20px] sm:gap-[35px]">
          <button
            type="button"
            @click="close"
            class="h-[42px] w-[99px] rounded-[37px] bg-[#FF0808] text-white"
          >
            Cancelar
          </button>

          <button
            type="button"
            @click="save"
            class="h-[42px] w-[99px] rounded-[37px] bg-[#00BF63] text-white"
          >
            Guardar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>