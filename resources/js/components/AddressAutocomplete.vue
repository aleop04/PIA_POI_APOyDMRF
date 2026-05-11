<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import { setOptions, importLibrary } from '@googlemaps/js-api-loader';

type SelectedLocation = {
    formatted_address: string | null;
    lat: number | null;
    lng: number | null;
    place_id: string | null;
};

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<{
    modelValue: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'location-selected', value: SelectedLocation): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
let autocomplete: google.maps.places.Autocomplete | null = null;
let listener: google.maps.MapsEventListener | null = null;

onMounted(async () => {
    if (!inputRef.value) return;

    setOptions({
        key: import.meta.env.VITE_GOOGLE_MAPS_API_KEY,
        v: 'weekly',
    });

    await importLibrary('places');

    autocomplete = new google.maps.places.Autocomplete(inputRef.value, {
        fields: ['formatted_address', 'geometry', 'place_id', 'name'],
        componentRestrictions: { country: 'mx' },
        types: ['address'],
    });

    listener = autocomplete.addListener('place_changed', () => {
        if (!autocomplete) return;

        const place = autocomplete.getPlace();

        if (!place?.geometry?.location) {
            emit('location-selected', {
                formatted_address: place?.formatted_address ?? null,
                lat: null,
                lng: null,
                place_id: place?.place_id ?? null,
            });
            return;
        }

        emit('location-selected', {
            formatted_address: place.formatted_address ?? null,
            lat: place.geometry.location.lat(),
            lng: place.geometry.location.lng(),
            place_id: place.place_id ?? null,
        });
    });
});

onBeforeUnmount(() => {
    if (listener) {
        google.maps.event.removeListener(listener);
    }
});

watch(
    () => props.modelValue,
    (value) => {
        if (inputRef.value && inputRef.value.value !== value) {
            inputRef.value.value = value;
        }
    }
);

</script>

<template>
    <input
    ref="inputRef"
    type="text"
    v-bind="$attrs"
    :value="modelValue"
    placeholder="Escribe tu dirección..."
    autocomplete="off"
    @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    @keydown.enter.prevent
    />
</template>