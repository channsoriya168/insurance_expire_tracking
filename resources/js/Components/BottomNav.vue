<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const page = usePage();
const path = computed(() => page.url.split('?')[0]);
const expiringCount = computed(() => page.props.expiringCount ?? 0);
const badgeLabel = computed(() => (expiringCount.value > 99 ? '99+' : expiringCount.value));

const tabs = [
    {
        href: '/insurances',
        icon: 'list',
        label: 'បញ្ជី',
        isActive: (current) => current === '/insurances' || /^\/insurances\/\d+\/edit$/.test(current),
    },
    {
        href: '/insurances/create',
        icon: 'plus',
        label: 'បញ្ចូល',
        isActive: (current) => current === '/insurances/create',
    },
    {
        href: '/insurances-notifications',
        icon: 'bell',
        label: 'ជូនដំណឹង',
        isActive: (current) => current === '/insurances-notifications',
        badge: true,
    },
];
</script>

<template>
    <nav class="fixed inset-x-0 bottom-0 z-20 px-3" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))">
        <div
            class="mx-auto flex max-w-2xl justify-around rounded-3xl border border-slate-100 bg-white px-2 py-2 shadow-lg shadow-slate-300/40"
        >
            <Link
                v-for="tab in tabs"
                :key="tab.href"
                :href="tab.href"
                class="flex flex-1 flex-col items-center gap-1 rounded-2xl py-1 text-[11px] font-medium transition-colors active:scale-95"
                :class="tab.isActive(path) ? 'text-brand-700' : 'text-slate-400'"
            >
                <span
                    class="relative flex h-10 w-10 items-center justify-center rounded-full transition-colors"
                    :class="tab.isActive(path) ? 'bg-brand-600 text-white shadow-md shadow-brand-600/30' : ''"
                >
                    <Icon :name="tab.icon" class="h-5 w-5" />

                    <span
                        v-if="tab.badge && expiringCount > 0"
                        class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full border-2 border-white bg-red-500 px-1 text-[10px] font-semibold leading-none text-white"
                    >
                        {{ badgeLabel }}
                    </span>
                </span>

                {{ tab.label }}
            </Link>
        </div>
    </nav>
</template>
