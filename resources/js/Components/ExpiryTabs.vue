<script setup>
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    expiryThresholds: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const tabs = computed(() => [
    { value: '', label: 'All', icon: 'list' },
    { value: 'expired', label: 'Expired', icon: 'alert-circle' },
    { value: 'today', label: 'Today', icon: 'alert-triangle' },
    ...props.expiryThresholds.map((days) => ({
        value: String(days),
        label: `${days} Days`,
        icon: 'calendar',
    })),
]);

function pick(value) {
    emit('update:modelValue', props.modelValue === value ? '' : value);
}

function scrollHorizontally(event) {
    if (event.deltaY === 0) {
        return;
    }

    event.currentTarget.scrollLeft += event.deltaY;
    event.preventDefault();
}
</script>

<template>
    <div class="mb-4 -mx-4 flex gap-2 overflow-x-auto px-4 pb-1" @wheel="scrollHorizontally">
        <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            class="flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors active:scale-95"
            :class="
                modelValue === tab.value
                    ? 'bg-brand-900 text-white shadow-sm shadow-brand-900/30'
                    : 'border border-slate-200 bg-white text-slate-500'
            "
            @click="pick(tab.value)"
        >
            <Icon :name="tab.icon" class="h-3.5 w-3.5" />
            {{ tab.label }}
        </button>
    </div>
</template>