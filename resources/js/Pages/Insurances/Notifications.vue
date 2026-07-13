<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';
import { Switch } from '@/Components/ui/switch';

const props = defineProps({
    overdue: { type: Array, required: true },
    today: { type: Array, required: true },
    buckets: { type: Object, required: true },
    notificationTime: { type: String, default: null },
});

const sections = computed(() => [
    {
        key: 'overdue',
        title: 'Already Expired',
        policies: props.overdue,
        colorClass: 'text-red-500',
        accentClass: 'bg-red-500',
        badgeClass: 'bg-red-50 text-red-600',
    },
    {
        key: 'today',
        title: 'Expiring Today',
        policies: props.today,
        colorClass: 'text-red-500',
        accentClass: 'bg-red-500',
        badgeClass: 'bg-red-50 text-red-600',
    },
    ...Object.entries(props.buckets)
        .sort(([a], [b]) => Number(a) - Number(b))
        .map(([days, policies]) => ({
            key: days,
            title: `Expiring in ${days} Days`,
            policies,
            colorClass: Number(days) <= 10 ? 'text-red-500' : 'text-amber-500',
            accentClass: Number(days) <= 10 ? 'bg-red-500' : 'bg-amber-500',
            badgeClass: Number(days) <= 10 ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600',
        })),
]);

const totalCount = computed(() => sections.value.reduce((sum, section) => sum + section.policies.length, 0));
const unreadCount = computed(() =>
    sections.value.reduce((sum, section) => sum + section.policies.filter((policy) => !policy.read).length, 0),
);
const isEmpty = computed(() => totalCount.value === 0);

const tabs = computed(() => [
    { key: 'all', label: 'All' },
    { key: 'today', label: 'Today' },
    ...Object.keys(props.buckets)
        .sort((a, b) => Number(a) - Number(b))
        .map((days) => ({ key: days, label: `${days}d` })),
]);

const activeTab = ref('all');
const unreadOnly = ref(false);

const visibleSections = computed(() => {
    const bySelectedTab = sections.value.filter((section) => activeTab.value === 'all' || section.key === activeTab.value);

    if (!unreadOnly.value) {
        return bySelectedTab;
    }

    return bySelectedTab.map((section) => ({ ...section, policies: section.policies.filter((policy) => !policy.read) }));
});

const isFilteredEmpty = computed(() => visibleSections.value.every((section) => section.policies.length === 0));

const formattedNotificationTime = computed(() => {
    if (!props.notificationTime) {
        return null;
    }

    const [hours, minutes] = props.notificationTime.split(':').map(Number);
    return new Date(2000, 0, 1, hours, minutes).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
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

            <div v-if="!isEmpty" class="flex items-center justify-between">
                <p class="text-xs font-medium text-slate-400">
                    {{ totalCount }} polic{{ totalCount === 1 ? 'y' : 'ies' }} need attention
                </p>
                <p v-if="unreadCount > 0" class="flex items-center gap-1.5 text-xs font-semibold text-brand-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-700" />
                    {{ unreadCount }} unread
                </p>
            </div>

            <div v-if="!isEmpty" class="flex items-center justify-between gap-3">
                <div class="flex flex-1 gap-1.5 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-medium transition-colors"
                        :class="activeTab === tab.key ? 'bg-brand-900 text-white' : 'bg-slate-100 text-slate-500 active:bg-slate-200'"
                        @click="activeTab = tab.key"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <label class="flex shrink-0 items-center gap-2 text-xs font-medium text-slate-500">
                    Unread only
                    <Switch :model-value="unreadOnly" size="sm" @update:model-value="unreadOnly = $event" />
                </label>
            </div>

            <section v-for="section in visibleSections" :key="section.key" v-show="section.policies.length > 0">
                <h2 class="mb-2 flex items-center gap-1.5 text-sm font-semibold" :class="section.colorClass">
                    <Icon name="alert-triangle" class="h-4 w-4 shrink-0" />
                    {{ section.title }}
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="section.badgeClass">{{ section.policies.length }}</span>
                </h2>

                <div class="space-y-2.5">
                    <div
                        v-for="policy in section.policies"
                        :key="policy.id"
                        class="flex overflow-hidden rounded-2xl border bg-white transition-all"
                        :class="
                            policy.read
                                ? 'border-slate-100 shadow-sm shadow-slate-200/40'
                                : 'border-slate-200 shadow-md shadow-slate-200/70 ring-1 ring-inset ring-slate-100'
                        "
                    >
                        <span class="w-1.5 shrink-0" :class="section.accentClass" />

                        <button
                            type="button"
                            class="flex shrink-0 items-center justify-center px-3 transition-transform active:scale-90"
                            :aria-label="policy.read ? 'Mark as unread' : 'Mark as read'"
                            :title="policy.read ? 'Mark as unread' : 'Mark as read'"
                            @click="toggleRead(policy)"
                        >
                            <Icon v-if="policy.read" name="check-circle" class="h-5 w-5 text-emerald-400" />
                            <span v-else class="h-2.5 w-2.5 rounded-full" :class="section.accentClass" />
                        </button>

                        <Link
                            :href="`/insurances/${policy.id}?from=notifications`"
                            class="flex min-w-0 flex-1 items-center gap-3 py-3.5 pr-2 transition-colors active:bg-slate-50"
                            :class="policy.read ? 'opacity-60' : ''"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="tracking-tight" :class="policy.read ? 'font-medium text-slate-600' : 'font-semibold text-slate-900'">
                                        {{ policy.policy_no }}
                                    </p>
                                    <span class="flex shrink-0 items-center gap-1 text-sm" :class="section.colorClass">
                                        <Icon name="calendar" class="h-3.5 w-3.5" />
                                        {{ policy.expiry_date }}
                                    </span>
                                </div>
                                <p class="mt-1 flex items-center gap-1.5 truncate text-sm text-slate-500">
                                    <Icon name="building" class="h-3.5 w-3.5 shrink-0" />
                                    <span class="truncate">{{ policy.insurance_company }} · {{ policy.insured_name }}</span>
                                </p>
                            </div>

                            <Icon name="chevron-right" class="h-4 w-4 shrink-0 text-slate-300" />
                        </Link>
                    </div>
                </div>
            </section>

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
