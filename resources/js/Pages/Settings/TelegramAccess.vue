<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';
import { Button } from '@/Components/ui/button';

defineProps({
    pendingRequests: { type: Object, required: true },
});

function approve(request) {
    router.patch(`/telegram-access/${request.id}/approve`, {}, { preserveScroll: true });
}

function reject(request) {
    if (!confirm(`Decline access for ${request.first_name ?? request.chat_id}?`)) {
        return;
    }

    router.patch(`/telegram-access/${request.id}/reject`, {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Telegram Access" back-href="/settings" hide-settings>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/60">
            <h3 class="mb-3 text-sm font-semibold text-slate-700">Pending Requests</h3>

            <ul class="divide-y divide-slate-100">
                <li v-for="request in pendingRequests.data" :key="request.id" class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-[15px] font-medium text-slate-900">{{ request.first_name ?? 'Unknown' }}</p>
                        <p class="truncate text-xs text-slate-400">
                            <span v-if="request.username">@{{ request.username }} &middot; </span>chat id {{ request.chat_id }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <Button type="button" variant="destructive" size="sm" @click="reject(request)">Decline</Button>
                        <Button type="button" size="sm" @click="approve(request)">Approve</Button>
                    </div>
                </li>

                <li v-if="pendingRequests.data.length === 0" class="flex flex-col items-center gap-2 py-14 text-center">
                    <Icon name="users" class="h-8 w-8 text-slate-300" />
                    <p class="text-sm text-slate-400">No pending access requests.</p>
                </li>
            </ul>

            <nav v-if="pendingRequests.links.length > 3" class="mt-3 flex flex-wrap justify-center gap-1.5">
                <template v-for="link in pendingRequests.links" :key="link.label">
                    <span
                        v-if="link.url === null"
                        class="flex h-8 min-w-8 items-center justify-center rounded-full px-2 text-xs text-slate-300"
                        v-html="link.label"
                    />
                    <Link
                        v-else
                        :href="link.url"
                        preserve-scroll
                        class="flex h-8 min-w-8 items-center justify-center rounded-full px-2 text-xs font-medium transition-colors"
                        :class="link.active ? 'bg-brand-900 text-white' : 'text-slate-500 active:bg-slate-100'"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </section>
    </AppLayout>
</template>
