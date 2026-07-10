<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Insurance Policies' },
});

const page = usePage();
const flashStatus = computed(() => page.props.flash?.status);
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
        <header class="border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
            <h1 class="text-lg font-semibold">{{ title }}</h1>
        </header>

        <main class="mx-auto max-w-2xl px-4 py-4">
            <div
                v-if="flashStatus"
                class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-300"
            >
                {{ flashStatus }}
            </div>

            <slot />
        </main>
    </div>
</template>
