<script setup lang="ts">
import { setOptions, importLibrary } from '@googlemaps/js-api-loader';
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';

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

function updateValue() {
    if (!inputRef.value) {
        return;
    }

    emit('update:modelValue', inputRef.value.value);
}

onMounted(async () => {
    if (!inputRef.value) {
        return;
    }

    setOptions({
        key: import.meta.env.VITE_GOOGLE_MAPS_API_KEY,
        v: 'weekly',
    });

    await importLibrary('places');

    autocomplete = new google.maps.places.Autocomplete(inputRef.value, {
        fields: ['formatted_address', 'geometry', 'place_id', 'name'],
        componentRestrictions: { country: 'mx' },
        types: ['geocode'],
    });

    listener = autocomplete.addListener('place_changed', () => {
        if (!autocomplete) {
            return;
        }

        const place = autocomplete.getPlace();
        const address = place.formatted_address ?? place.name ?? '';

        emit('update:modelValue', address);

        if (!place?.geometry?.location) {
            emit('location-selected', {
                formatted_address: address || null,
                lat: null,
                lng: null,
                place_id: place?.place_id ?? null,
            });

            return;
        }

        emit('location-selected', {
            formatted_address: address,
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
        autocomplete="off"
        autocorrect="off"
        autocapitalize="off"
        spellcheck="false"
        @input="updateValue"
        @change="updateValue"
        @keydown.enter="updateValue"
    />
</template>