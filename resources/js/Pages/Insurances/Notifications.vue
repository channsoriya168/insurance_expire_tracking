<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    overdue: { type: Array, required: true },
    buckets: { type: Object, required: true },
});

const sections = computed(() => [
    { key: 'overdue', title: 'Already Expired', policies: props.overdue, colorClass: 'text-red-500', accentClass: 'bg-red-500' },
    ...Object.entries(props.buckets)
        .sort(([a], [b]) => Number(a) - Number(b))
        .map(([days, policies]) => ({
            key: `bucket-${days}`,
            title: `Expiring in ${days} Days`,
            policies,
            colorClass: Number(days) <= 10 ? 'text-red-500' : 'text-amber-500',
            accentClass: Number(days) <= 10 ? 'bg-red-500' : 'bg-amber-500',
        })),
]);

const isEmpty = computed(() => sections.value.every((section) => section.policies.length === 0));
</script>

<template>
    <AppLayout title="Notifications">
        <div class="space-y-6">
            <section v-for="section in sections" :key="section.key" v-show="section.policies.length > 0">
                <h2 class="mb-2 flex items-center gap-1.5 text-sm font-semibold" :class="section.colorClass">
                    <Icon name="alert-triangle" class="h-4 w-4 shrink-0" />
                    {{ section.title }}
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">{{ section.policies.length }}</span>
                </h2>

                <div class="space-y-2">
                    <Link
                        v-for="policy in section.policies"
                        :key="policy.id"
                        :href="`/insurances/${policy.id}/edit`"
                        class="flex items-center gap-3 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm shadow-slate-200/60 transition-colors active:bg-slate-50"
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
                            <p class="mt-1 truncate text-sm text-slate-500">
                                {{ policy.insurance_company }} · {{ policy.insured_name }}
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
