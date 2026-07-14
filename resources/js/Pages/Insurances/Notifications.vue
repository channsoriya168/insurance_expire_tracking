<script setup>
import { InfiniteScroll, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';
import NotificationTabs from '@/Components/NotificationTabs.vue';
import { expiryStatus } from '@/expiryStatus';

const props = defineProps({
    notifications: { type: Object, required: true },
    tabCounts: { type: Object, required: true },
    filters: { type: Object, required: true },
    notificationTime: { type: String, default: null },
});

const activeTab = computed(() => {
    if (props.filters.unread) {
        return 'unread';
    }

    return props.filters.expiry ?? 'all';
});

const tabs = computed(() => [
    { key: 'all', label: 'All', count: props.tabCounts.all },
    { key: 'unread', label: 'Unread', count: props.tabCounts.unread },
    { key: 'today', label: 'Today', count: props.tabCounts.today },
    ...Object.entries(props.tabCounts.buckets)
        .sort(([a], [b]) => Number(a) - Number(b))
        .map(([days, count]) => ({ key: days, label: `${days}d`, count })),
]);

const totalCount = computed(() => props.tabCounts.all);
const isEmpty = computed(() => totalCount.value === 0);
const isFilteredEmpty = computed(() => props.notifications.data.length === 0);

const formattedNotificationTime = computed(() => {
    if (!props.notificationTime) {
        return null;
    }

    const [hours, minutes] = props.notificationTime.split(':').map(Number);
    return new Date(2000, 0, 1, hours, minutes).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
});

function selectTab(tab) {
    router.get(
        '/insurances-notifications',
        {
            expiry: tab === 'all' || tab === 'unread' ? undefined : tab,
            unread: tab === 'unread' ? 1 : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function toggleRead(policy) {
    router.patch(
        `/insurances-notifications/${policy.id}/read`,
        {},
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <AppLayout title="Notifications" back-href="/insurances" hide-notifications>
        <div class="space-y-6">
            <div
                v-if="formattedNotificationTime"
                class="flex items-center gap-2.5 rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-700"
            >
                <Icon name="bell" class="h-4.5 w-4.5 shrink-0" />
                <p>This list matches the daily Telegram alert sent at <span class="font-semibold">{{ formattedNotificationTime }}</span>.</p>
            </div>

            <div v-if="!isEmpty" class="flex items-center justify-between">
                <p class="text-xs font-medium text-slate-400">
                    {{ totalCount }} polic{{ totalCount === 1 ? 'y' : 'ies' }} need attention
                </p>
                <p v-if="tabCounts.unread > 0" class="flex items-center gap-1.5 text-xs font-semibold text-brand-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-700" />
                    {{ tabCounts.unread }} unread
                </p>
            </div>

            <NotificationTabs v-if="!isEmpty" :model-value="activeTab" :tabs="tabs" @update:model-value="selectTab" />

            <InfiniteScroll v-if="!isFilteredEmpty" data="notifications">
                <div class="space-y-3">
                    <div
                        v-for="policy in notifications.data"
                        :key="policy.id"
                        class="relative overflow-hidden rounded-2xl border bg-white shadow-sm shadow-slate-200/60 transition-all hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-200/70"
                        :class="policy.read ? 'border-slate-100' : 'border-slate-200 ring-1 ring-inset ring-slate-100'"
                    >
                        <span class="absolute inset-y-0 left-0 w-1" :class="expiryStatus(policy.expiry_date).accentClass" />

                        <div class="flex items-center pl-5">
                            <button
                                type="button"
                                class="flex h-11 w-11 shrink-0 items-center justify-center transition-transform active:scale-90"
                                :aria-label="policy.read ? 'Mark as unread' : 'Mark as read'"
                                :title="policy.read ? 'Mark as unread' : 'Mark as read'"
                                @click="toggleRead(policy)"
                            >
                                <Icon v-if="policy.read" name="check-circle" class="h-5 w-5 text-emerald-400" />
                                <span v-else class="h-2.5 w-2.5 rounded-full" :class="expiryStatus(policy.expiry_date).accentClass" />
                            </button>

                            <Link
                                :href="`/insurances/${policy.id}?from=notifications`"
                                class="flex min-w-0 flex-1 items-center gap-3 py-3.5 pr-3 transition-colors active:bg-slate-50"
                                :class="policy.read ? 'opacity-60' : ''"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="truncate text-base font-semibold tracking-tight" :class="policy.read ? 'text-slate-600' : 'text-slate-900'">
                                            {{ policy.policy_no }}
                                        </p>
                                        <span
                                            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="expiryStatus(policy.expiry_date).chipClass"
                                        >
                                            {{ policy.bucket }}
                                        </span>
                                    </div>
                                    <p class="mt-1 flex items-center gap-1.5 truncate text-sm text-slate-500">
                                        <Icon name="building" class="h-4 w-4 shrink-0" />
                                        <span class="truncate">{{ policy.insurance_company }} · {{ policy.insured_name }}</span>
                                    </p>
                                    <div
                                        class="mt-2.5 flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold"
                                        :class="expiryStatus(policy.expiry_date).chipClass"
                                    >
                                        <Icon name="calendar" class="h-3.5 w-3.5 shrink-0" />
                                        {{ expiryStatus(policy.expiry_date).label }}
                                    </div>
                                </div>

                                <Icon name="chevron-right" class="h-4 w-4 shrink-0 text-slate-300" />
                            </Link>
                        </div>
                    </div>
                </div>
            </InfiniteScroll>

            <div
                v-if="isEmpty"
                class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-slate-200 py-14 text-center"
            >
                <Icon name="check-circle" class="h-8 w-8 text-emerald-500" />
                <p class="text-sm text-slate-400">No policies are expiring soon.</p>
            </div>

            <div
                v-else-if="isFilteredEmpty"
                class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-slate-200 py-14 text-center"
            >
                <Icon name="check-circle" class="h-8 w-8 text-emerald-500" />
                <p class="text-sm text-slate-400">No policies match this filter.</p>
            </div>
        </div>
    </AppLayout>
</template>
