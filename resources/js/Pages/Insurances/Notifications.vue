<script setup>
import { InfiniteScroll, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';
import NotificationCard from '@/Components/NotificationCard.vue';
import NotificationTabs from '@/Components/NotificationTabs.vue';
import { expiryStatus } from '@/expiryStatus';

const props = defineProps({
    notifications: { type: Object, required: true },
    expiryBuckets: { type: Array, required: true },
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
    { key: 'all', label: 'All' },
    { key: 'unread', label: 'Unread' },
    { key: 'today', label: 'Today' },
    ...[...props.expiryBuckets]
        .sort((a, b) => a - b)
        .map((days) => ({ key: String(days), label: `${days}d` })),
]);

const isFilteredEmpty = computed(() => props.notifications.data.length === 0);
const isEmpty = computed(() => isFilteredEmpty.value && activeTab.value === 'all');

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

function dateGroupLabel(dateString) {
    if (expiryStatus(dateString).isToday) {
        return 'Today';
    }

    return dateString;
}

const groupedNotifications = computed(() => {
    const rows = [];
    let lastDate = null;

    for (const policy of props.notifications.data) {
        if (policy.created_at !== lastDate) {
            rows.push({ type: 'header', key: `header-${policy.created_at}`, label: dateGroupLabel(policy.created_at) });
            lastDate = policy.created_at;
        }

        rows.push({ type: 'item', key: policy.id, policy });
    }

    return rows;
});

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

            <NotificationTabs v-if="!isEmpty" :model-value="activeTab" :tabs="tabs" @update:model-value="selectTab" />


            <InfiniteScroll v-if="!isFilteredEmpty" data="notifications">
                <div class="space-y-3">
                    <template v-for="row in groupedNotifications" :key="row.key">
                        <h2
                            v-if="row.type === 'header'"
                            class="pt-1 text-xs font-semibold tracking-wide text-slate-400 uppercase first:pt-0"
                        >
                            {{ row.label }}
                        </h2>

                        <NotificationCard v-else :policy="row.policy" @toggle-read="toggleRead" />
                    </template>
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
