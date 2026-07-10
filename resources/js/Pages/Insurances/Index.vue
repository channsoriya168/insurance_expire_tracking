<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    insurances: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(
        '/insurances',
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true },
    );
}

function destroy(insurance) {
    if (!confirm(`តើអ្នកចង់លុបបណ្ណសន្យារ៉ាប់រង ${insurance.policy_no} មែនទេ? សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ។`)) {
        return;
    }

    router.delete(`/insurances/${insurance.id}`);
}

function expiryInfo(dateString) {
    if (!dateString) {
        return { label: '—', colorClass: 'text-slate-400', accentClass: 'bg-slate-200' };
    }

    const days = Math.ceil((new Date(`${dateString}T00:00:00`) - new Date(new Date().toDateString())) / 86400000);

    if (days < 0) {
        return {
            label: `ផុតកំណត់ ${dateString} · ${Math.abs(days)} ថ្ងៃមុន`,
            colorClass: 'text-red-500',
            accentClass: 'bg-red-500',
        };
    }

    if (days === 0) {
        return { label: `ផុតកំណត់ថ្ងៃនេះ (${dateString})`, colorClass: 'text-red-500', accentClass: 'bg-red-500' };
    }

    if (days <= 10) {
        return {
            label: `ផុតកំណត់ក្នុងរយៈពេល ${days} ថ្ងៃ · ${dateString}`,
            colorClass: 'text-red-500',
            accentClass: 'bg-red-500',
        };
    }

    if (days <= 30) {
        return {
            label: `ផុតកំណត់ក្នុងរយៈពេល ${days} ថ្ងៃ · ${dateString}`,
            colorClass: 'text-amber-500',
            accentClass: 'bg-amber-500',
        };
    }

    return { label: dateString, colorClass: 'text-slate-500', accentClass: 'bg-emerald-500' };
}
</script>

<template>
    <AppLayout title="បញ្ជីបណ្ណសន្យារ៉ាប់រង">
        <Link
            href="/insurances/create"
            class="mb-4 flex items-center justify-center gap-1.5 rounded-full bg-brand-600 px-4 py-3.5 text-[15px] font-bold text-white shadow-md shadow-brand-600/25 transition-transform active:scale-[0.98]"
        >
            <Icon name="plus" class="h-5 w-5" />
            បញ្ចូលបណ្ណសន្យារ៉ាប់រងថ្មី
        </Link>

        <form class="mb-4 flex gap-2" @submit.prevent="applyFilters">
            <div class="relative w-full">
                <Icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-slate-400" />
                <input
                    v-model="search"
                    type="search"
                    placeholder="ស្វែងរកលេខបណ្ណ, អ្នកត្រូវបានធានា, ក្រុមហ៊ុន"
                    class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15"
                />
            </div>
            <div class="relative w-24 shrink-0">
                <Icon name="filter" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    v-model="status"
                    type="text"
                    placeholder="ស្ថានភាព"
                    class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-8 pr-2 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15"
                />
            </div>
            <button
                type="submit"
                class="shrink-0 rounded-full bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-700 transition-transform active:scale-95"
            >
                ត្រង
            </button>
        </form>

        <div class="space-y-3">
            <div
                v-for="insurance in insurances.data"
                :key="insurance.id"
                class="flex overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm shadow-slate-200/60"
            >
                <span class="w-1.5 shrink-0" :class="expiryInfo(insurance.expiry_date).accentClass" />

                <div class="min-w-0 flex-1 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-semibold tracking-tight">{{ insurance.policy_no }}</p>
                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">
                            {{ insurance.status || 'Pending' }}
                        </span>
                    </div>

                    <p class="mt-1.5 flex items-center gap-1.5 text-sm text-slate-500">
                        <Icon name="building" class="h-4 w-4 shrink-0" />
                        <span class="truncate">{{ insurance.insurance_company }} · {{ insurance.insured_name }}</span>
                    </p>

                    <p class="mt-2 flex items-center gap-1.5 text-sm font-medium" :class="expiryInfo(insurance.expiry_date).colorClass">
                        <Icon name="calendar" class="h-4 w-4 shrink-0" />
                        {{ expiryInfo(insurance.expiry_date).label }}
                    </p>

                    <div class="mt-3 flex justify-end gap-2 border-t border-slate-100 pt-3 text-sm">
                        <Link
                            :href="`/insurances/${insurance.id}/edit`"
                            class="flex items-center gap-1.5 rounded-full px-3 py-1.5 font-medium text-brand-700 transition-colors active:bg-brand-50"
                        >
                            <Icon name="edit" class="h-4 w-4" />
                            កែសម្រួល
                        </Link>
                        <button
                            type="button"
                            class="flex items-center gap-1.5 rounded-full px-3 py-1.5 font-medium text-red-500 transition-colors active:bg-red-50"
                            @click="destroy(insurance)"
                        >
                            <Icon name="trash" class="h-4 w-4" />
                            លុប
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="insurances.data.length === 0"
                class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-slate-200 py-14 text-center"
            >
                <Icon name="inbox" class="h-8 w-8 text-slate-300" />
                <p class="text-sm text-slate-400">រកមិនឃើញបណ្ណសន្យារ៉ាប់រងទេ។</p>
            </div>
        </div>

        <nav v-if="insurances.links.length > 3" class="mt-5 flex flex-wrap justify-center gap-1.5">
            <template v-for="link in insurances.links" :key="link.label">
                <span
                    v-if="link.url === null"
                    class="flex h-9 min-w-9 items-center justify-center rounded-full px-2 text-sm text-slate-300"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    class="flex h-9 min-w-9 items-center justify-center rounded-full px-2 text-sm font-medium transition-colors"
                    :class="link.active ? 'bg-brand-600 text-white' : 'text-slate-500 active:bg-slate-100'"
                    v-html="link.label"
                />
            </template>
        </nav>
    </AppLayout>
</template>
