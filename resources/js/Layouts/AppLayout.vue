<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import BottomNav from '@/Components/BottomNav.vue';
import Icon from '@/Components/Icon.vue';

defineProps({
    title: { type: String, default: 'បញ្ជីបណ្ណសន្យារ៉ាប់រង' },
});

const page = usePage();
const flashStatus = computed(() => page.props.flash?.status);
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="sticky top-0 z-10 bg-brand-900 px-4 py-4 shadow-md shadow-brand-950/20">
            <div class="mx-auto flex max-w-2xl items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white">
                    <Icon name="shield" class="h-5 w-5" />
                </span>
                <h1 class="truncate text-[17px] font-bold tracking-tight text-white">{{ title }}</h1>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-4 py-4 pb-28">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div
                    v-if="flashStatus"
                    class="mb-4 flex items-center gap-2 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
                >
                    <Icon name="check-circle" class="h-5 w-5 shrink-0" />
                    {{ flashStatus }}
                </div>
            </Transition>

            <slot />
        </main>

        <BottomNav />
    </div>
</template>
