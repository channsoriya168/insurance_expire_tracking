<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    overdue: { type: Array, required: true },
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
    ...Object.entries(props.buckets)
        .sort(([a], [b]) => Number(a) - Number(b))
        .map(([days, policies]) => ({
            key: `bucket-${days}`,
            title: `Expiring in ${days} Days`,
            policies,
            colorClass: Number(days) <= 10 ? 'text-red-500' : 'text-amber-500',
            accentClass: Number(days) <= 10 ? 'bg-red-500' : 'bg-amber-500',
            badgeClass: Number(days) <= 10 ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600',
        })),
]);

const totalCount = computed(() => sections.value.reduce((sum, section) => sum + section.policies.length, 0));
const isEmpty = computed(() => totalCount.value === 0);

const formattedNotificationTime = computed(() => {
    if (!props.notificationTime) {
        return null;
    }

    const [hours, minutes] = props.notificationTime.split(':').map(Number);
    return new Date(2000, 0, 1, hours, minutes).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
});
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

            <p v-if="!isEmpty" class="text-xs font-medium text-slate-400">
                {{ totalCount }} polic{{ totalCount === 1 ? 'y' : 'ies' }} need attention
            </p>

            <section v-for="section in sections" :key="section.key" v-show="section.policies.length > 0">
                <h2 class="mb-2 flex items-center gap-1.5 text-sm font-semibold" :class="section.colorClass">
                    <Icon name="alert-triangle" class="h-4 w-4 shrink-0" />
                    {{ section.title }}
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="section.badgeClass">{{ section.policies.length }}</span>
                </h2>

                <div class="space-y-2.5">
                    <Link
                        v-for="policy in section.policies"
                        :key="policy.id"
                        :href="`/insurances/${policy.id}/edit`"
                        class="flex items-center gap-3 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm shadow-slate-200/60 transition-shadow active:bg-slate-50 hover:shadow-md hover:shadow-slate-200/70"
                    >
                        <span class="h-full w-1.5 self-stretch" :class="section.accentClass" />

                        <div class="min-w-0 flex-1 py-3.5 pr-2">
                            <div class="flex items-start justify-between gap-2">
                                <p class="font-semibold tracking-tight">{{ policy.policy_no }}</p>
                                <span class="shrink-0 flex items-center gap-1 text-sm" :class="section.colorClass">
                                    <Icon name="calendar" class="h-3.5 w-3.5" />
                                    {{ policy.expiry_date }}
                                </span>
                            </div>
                            <p class="mt-1 flex items-center gap-1.5 truncate text-sm text-slate-500">
                                <Icon name="building" class="h-3.5 w-3.5 shrink-0" />
                                <span class="truncate">{{ policy.insurance_company }} · {{ policy.insured_name }}</span>
                            </p>
                        </div>

                        <Icon name="chevron-right" class="mr-3 h-4 w-4 shrink-0 text-slate-300" />
                    </Link>
                </div>
            </section>

            <div
                v-if="isEmpty"
                class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-slate-200 py-14 text-center"
            >
                <Icon name="check-circle" class="h-8 w-8 text-emerald-500" />
                <p class="text-sm text-slate-400">No policies are expiring soon.</p>
            </div>
        </div>
    </AppLayout>
</template>
