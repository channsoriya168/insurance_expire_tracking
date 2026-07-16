<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Copy } from '@lucide/vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';
import { INSURANCE_FIELDS } from '@/insuranceFields';

const props = defineProps({
    insurance: { type: Object, required: true },
});

const page = usePage();
const backHref = computed(() => {
    const query = page.url.split('?')[1] ?? '';

    return new URLSearchParams(query).get('from') === 'notifications' ? '/insurances-notifications' : '/insurances';
});

const sections = computed(() => {
    const grouped = [];

    for (const field of INSURANCE_FIELDS) {
        if (field.key === 'policy_no' || field.key === 'status') {
            continue;
        }

        let group = grouped.find((g) => g.title === field.section);

        if (!group) {
            group = { title: field.section, fields: [] };
            grouped.push(group);
        }

        group.fields.push(field);
    }

    return grouped;
});

function displayValue(field) {
    const value =
        field.key === 'insurance_company_id'
            ? props.insurance.insurance_company
            : field.key === 'policy_type_id'
              ? props.insurance.policy_type
              : props.insurance[field.key];

    return value === null || value === undefined || value === '' ? '—' : value;
}

function destroy() {
    if (!confirm(`Delete policy ${props.insurance.policy_no}? This action cannot be undone.`)) {
        return;
    }

    router.delete(`/insurances/${props.insurance.id}`);
}

const expiryInfo = computed(() => {
    const dateString = props.insurance.expiry_date;

    if (!dateString) {
        return { label: '—', colorClass: 'text-slate-400' };
    }

    const days = Math.ceil((new Date(`${dateString}T00:00:00`) - new Date(new Date().toDateString())) / 86400000);

    if (days < 0) {
        return {
            label: `Expired ${dateString} · ${Math.abs(days)} day${Math.abs(days) === 1 ? '' : 's'} ago`,
            colorClass: 'text-red-500',
        };
    }

    if (days === 0) {
        return { label: `Expires today (${dateString})`, colorClass: 'text-red-500' };
    }

    if (days <= 10) {
        return { label: `Expires in ${days} day${days === 1 ? '' : 's'} · ${dateString}`, colorClass: 'text-red-500' };
    }

    if (days <= 30) {
        return { label: `Expires in ${days} days · ${dateString}`, colorClass: 'text-amber-500' };
    }

    return { label: dateString, colorClass: 'text-slate-500' };
});
</script>

<template>
    <AppLayout title="Policy Details" :back-href="backHref">
        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm shadow-slate-200/60">
            <div class="flex items-start justify-between gap-2">
                <p class="text-lg font-bold tracking-tight">{{ insurance.policy_no }}</p>
                <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">
                    {{ insurance.status || 'Pending' }}
                </span>
            </div>

            <p class="mt-1.5 flex items-center gap-1.5 text-sm text-slate-500">
                <Icon name="building" class="h-4 w-4 shrink-0" />
                {{ insurance.insurance_company }}
            </p>

            <p class="mt-2 flex items-center gap-1.5 text-sm font-medium" :class="expiryInfo.colorClass">
                <Icon name="calendar" class="h-4 w-4 shrink-0" />
                {{ expiryInfo.label }}
            </p>
        </div>

        <section
            v-for="section in sections"
            :key="section.title"
            class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/60"
        >
            <h3 class="mb-3 text-sm font-semibold text-slate-700">{{ section.title }}</h3>

            <dl class="space-y-3">
                <div v-for="field in section.fields" :key="field.key">
                    <dt class="text-xs font-medium text-slate-400">{{ field.label }}</dt>
                    <dd class="mt-0.5 text-[15px] text-slate-900" :class="{ 'whitespace-pre-line': field.type === 'textarea' }">
                        {{ displayValue(field) }}
                    </dd>
                </div>
            </dl>
        </section>

        <div class="mt-5 grid grid-cols-3 gap-2">
            <Link
                :href="`/insurances/${insurance.id}/edit`"
                class="flex items-center justify-center gap-1.5 rounded-full bg-brand-900 px-4 py-3 text-sm font-bold text-white shadow-md shadow-brand-900/25 transition-transform active:scale-[0.98]"
            >
                <Icon name="edit" class="h-4 w-4" />
                Edit
            </Link>
            <Link
                :href="`/insurances/${insurance.id}/duplicate`"
                class="flex items-center justify-center gap-1.5 rounded-full border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition-colors active:bg-slate-100"
            >
                <Copy class="h-4 w-4" />
                Duplicate
            </Link>
            <button
                type="button"
                class="flex items-center justify-center gap-1.5 rounded-full border border-red-100 px-4 py-3 text-sm font-semibold text-red-500 transition-colors active:bg-red-50"
                @click="destroy"
            >
                <Icon name="trash" class="h-4 w-4" />
                Delete
            </button>
        </div>
    </AppLayout>
</template>
