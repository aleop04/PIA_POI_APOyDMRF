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
    if (val) name.value = props.initialName || '';
  },
  { immediate: true }
);

function close() {
  emit('close');
}

function save() {
  if (!name.value.trim()) return;
  emit('save', name.value.trim());
}
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/30"
  >
    <div
      class="relative h-[360px] w-[683px] rounded-[65px] bg-[#FFFBED] px-[65px] pb-[57px] pt-[183px] shadow-[0px_15px_4px_rgba(0,0,0,0.25)]"
    >
      <!-- Header -->
      <div class="absolute left-[97px] top-[17px] flex items-start justify-center gap-[28px]">
        <div class="h-[99px] w-[492px] text-center font-['Nunito_Sans'] text-[24px] font-bold">
          <p class="text-[#0E6C3F]">Cambiar nombre de chat</p>
          <p class="text-[#442F2F]">
            Si cambias el nombre de un chat en grupo, se modificará para todos los participantes
          </p>
        </div>

        <button @click="close" class="transition hover:scale-105 hover:opacity-80">
          <img src="/icons/X circle.svg" class="h-[35px] w-[35px]" />
        </button>
      </div>

      <div class="absolute left-0 top-[137px] h-[1px] w-full bg-[#442F2F]" />

      <div class="flex w-[555px] flex-col gap-[36px]">
        <input
          v-model="name"
          type="text"
          placeholder="Nombre del grupo"
          class="h-[57px] w-full rounded-[18px] border border-[#D4BCBC] px-[15px] font-['Nunito_Sans'] text-[24px] font-bold text-[#AF6123] outline-none"
        />

        <div class="flex justify-end gap-[35px]">
          <button
            @click="close"
            class="h-[42px] w-[99px] rounded-[37px] bg-[#FF0808] text-white"
          >
            Cancelar
          </button>

          <button
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