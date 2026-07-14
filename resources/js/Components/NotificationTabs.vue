<script setup>
defineProps({
    modelValue: { type: String, required: true },
    tabs: { type: Array, required: true },
});

const emit = defineEmits(['update:modelValue']);

function scrollHorizontally(event) {
    if (event.deltaY === 0) {
        return;
    }

    event.currentTarget.scrollLeft += event.deltaY;
    event.preventDefault();
}
</script>

<template>
    <div class="flex gap-2 overflow-x-auto" @wheel="scrollHorizontally">
        <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            class="flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors active:scale-95"
            :class="
                modelValue === tab.key
                    ? 'bg-brand-900 text-white shadow-sm shadow-brand-900/30'
                    : 'border border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:bg-slate-50'
            "
            @click="emit('update:modelValue', tab.key)"
        >
            {{ tab.label }}
            <span
                v-if="tab.count !== undefined"
                class="flex h-4.5 min-w-4.5 items-center justify-center rounded-full px-1 text-[10px] leading-none"
                :class="modelValue === tab.key ? 'bg-white/20' : 'bg-slate-100 text-slate-500'"
            >
                {{ tab.count }}
            </span>
        </button>
    </div>
</template>
