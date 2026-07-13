<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, ChevronLeft } from '@lucide/vue';
import { Button } from '@/Components/ui/button';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    title: { type: String, default: 'Insurance Policies' },
    backHref: { type: String, default: null },
    hideNotifications: { type: Boolean, default: false },
});

const page = usePage();
const flashStatus = computed(() => page.props.flash?.status);
const expiringCount = computed(() => page.props.expiringCount ?? 0);
const badgeLabel = computed(() => (expiringCount.value > 99 ? '99+' : expiringCount.value));

const webApp = typeof window !== 'undefined' ? window.Telegram?.WebApp : null;

function goBack() {
    router.visit(props.backHref);
}

onMounted(() => {
    if (!props.backHref) {
        return;
    }

    webApp?.BackButton?.show();
    webApp?.BackButton?.onClick(goBack);
});

onUnmounted(() => {
    if (!props.backHref) {
        return;
    }

    webApp?.BackButton?.offClick(goBack);
    webApp?.BackButton?.hide();
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="sticky top-0 z-10 bg-brand-900 px-4 py-4 shadow-md shadow-brand-950/20">
            <div class="mx-auto flex max-w-2xl items-center gap-2.5">
                <Button
                    v-if="backHref"
                    as-child
                    variant="ghost"
                    size="icon"
                    class="-ml-2 shrink-0 text-white hover:bg-white/10 hover:text-white"
                >
                    <Link :href="backHref" aria-label="Back">
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                </Button>

                <h1 class="min-w-0 flex-1 truncate text-[17px] font-bold tracking-tight text-white">{{ title }}</h1>

                <Button
                    v-if="!hideNotifications"
                    as-child
                    variant="ghost"
                    size="icon"
                    class="relative shrink-0 text-white hover:bg-white/10 hover:text-white"
                >
                    <Link href="/insurances-notifications" aria-label="Notifications">
                        <Bell class="h-5 w-5" />
                        <span
                            v-if="expiringCount > 0"
                            class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full border-2 border-brand-900 bg-red-500 px-1 text-[10px] font-semibold leading-none text-white"
                        >
                            {{ badgeLabel }}
                        </span>
                    </Link>
                </Button>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-4 py-4">
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
    </div>
</template>
