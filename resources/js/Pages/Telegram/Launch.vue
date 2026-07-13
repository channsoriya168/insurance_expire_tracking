<script setup>
import { router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    redirect: { type: String, default: null },
});

const status = ref('authenticating');

function attemptAuth() {
    const initData = window.Telegram?.WebApp?.initData;

    if (!initData) {
        status.value = 'no-telegram';
        return;
    }

    status.value = 'authenticating';

    router.post(
        '/telegram/auth',
        { init_data: initData, redirect: props.redirect },
        {
            onError: () => {
                status.value = 'unauthorized';
            },
        },
    );
}

onMounted(attemptAuth);
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center gap-4 bg-slate-50 px-6 text-center text-slate-900">
        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-md shadow-brand-600/25">
            <Icon name="shield" class="h-7 w-7" />
        </span>

        <template v-if="status === 'authenticating'">
            <div class="h-8 w-8 animate-spin rounded-full border-2 border-brand-600 border-t-transparent" />
            <p class="text-sm text-slate-500">Opening insurance policy list&hellip;</p>
        </template>

        <template v-else-if="status === 'no-telegram'">
            <p class="text-base font-semibold">Please open this app from Telegram</p>
            <p class="text-sm text-slate-500">This app only works inside the Telegram Mini App.</p>
        </template>

        <template v-else>
            <p class="text-base font-semibold">Access denied</p>
            <p class="text-sm text-slate-500">This Telegram account is not authorized to use this app.</p>
            <button
                type="button"
                class="mt-2 flex items-center gap-2 rounded-full bg-brand-900 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-brand-900/25 transition-transform active:scale-[0.98]"
                @click="attemptAuth"
            >
                Try again
            </button>
        </template>
    </div>
</template>
