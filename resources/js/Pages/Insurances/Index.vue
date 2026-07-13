<script setup>
import { Link, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
import { ArrowDownNarrowWide, ArrowUpNarrowWide, Plus, Search, X } from '@lucide/vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    insurances: { type: Object, required: true },
    filters: { type: Object, required: true },
    expiryThresholds: { type: Array, default: () => [] },
});

const search = ref(props.filters.search ?? '');
const expiry = ref(props.filters.expiry ?? '');
const sort = ref(props.filters.sort ?? 'asc');
const showSearch = ref(!!search.value);

const expiryTabs = computed(() => [
    { value: '', label: 'All', icon: 'list', activeClass: 'bg-slate-900 shadow-slate-900/30' },
    { value: 'today', label: 'Today', icon: 'alert-triangle', activeClass: 'bg-red-500 shadow-red-500/30' },
    ...props.expiryThresholds.map((days) => ({
        value: String(days),
        label: `${days} Days`,
        icon: 'calendar',
        activeClass: days <= 10 ? 'bg-red-500 shadow-red-500/30' : 'bg-amber-500 shadow-amber-500/30',
    })),
]);

function applyFilters() {
    router.get(
        '/insurances',
        {
            search: search.value || undefined,
            expiry: expiry.value || undefined,
            sort: sort.value === 'desc' ? 'desc' : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function pickExpiry(value) {
    expiry.value = expiry.value === value ? '' : value;
    applyFilters();
}

function toggleSort() {
    sort.value = sort.value === 'asc' ? 'desc' : 'asc';
    applyFilters();
}

function clearSearch() {
    search.value = '';
}

watchDebounced(search, applyFilters, { debounce: 300 });

function destroy(insurance) {
    if (!confirm(`Delete policy ${insurance.policy_no}? This action cannot be undone.`)) {
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
            label: `Expired ${dateString} · ${Math.abs(days)} day${Math.abs(days) === 1 ? '' : 's'} ago`,
            colorClass: 'text-red-500',
            accentClass: 'bg-red-500',
        };
    }

    if (days === 0) {
        return { label: `Expires today (${dateString})`, colorClass: 'text-red-500', accentClass: 'bg-red-500' };
    }

    if (days <= 10) {
        return {
            label: `Expires in ${days} day${days === 1 ? '' : 's'} · ${dateString}`,
            colorClass: 'text-red-500',
            accentClass: 'bg-red-500',
        };
    }

    if (days <= 30) {
        return {
            label: `Expires in ${days} days · ${dateString}`,
            colorClass: 'text-amber-500',
            accentClass: 'bg-amber-500',
        };
    }

    return { label: dateString, colorClass: 'text-slate-500', accentClass: 'bg-emerald-500' };
}

function statusBadgeClass(value) {
    const normalized = (value || 'Pending').toLowerCase();

    if (['active', 'confirmed', 'approved'].includes(normalized)) {
        return 'bg-emerald-50 text-emerald-600';
    }

    if (normalized === 'pending') {
        return 'bg-amber-50 text-amber-600';
    }

    if (['cancelled', 'canceled', 'rejected'].includes(normalized)) {
        return 'bg-red-50 text-red-500';
    }

    return 'bg-slate-100 text-slate-500';
}
</script>

<template>
    <AppLayout title="Insurance Policies">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h1 class="text-lg font-semibold tracking-tight text-slate-900">Insurance Policies</h1>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border transition-colors active:scale-95"
                    :class="
                        showSearch
                            ? 'border-brand-200 bg-brand-50 text-brand-700'
                            : 'border-slate-200 bg-white text-slate-500'
                    "
                    aria-label="Toggle search"
                    @click="showSearch = !showSearch"
                >
                    <Search class="h-4.5 w-4.5" />
                </button>

                <Link
                    href="/insurances/create"
                    aria-label="Add new policy"
                    class="flex h-10 shrink-0 items-center gap-1.5 rounded-full bg-brand-900 pl-3.5 pr-4 text-white shadow-sm transition-transform active:scale-95"
                >
                    <Plus class="h-4.5 w-4.5" />
                    <span class="text-sm font-medium">New Policy</span>
                </Link>
            </div>
        </div>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <form v-if="showSearch" class="mb-3" @submit.prevent="applyFilters">
                <div class="relative w-full">
                    <Icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        type="search"
                        autofocus
                        placeholder="Search policy no, insured name, company"
                        class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15"
                    />
                    <button
                        v-if="search"
                        type="button"
                        aria-label="Clear search"
                        class="absolute right-3 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors active:bg-slate-100"
                        @click="clearSearch"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>
            </form>
        </Transition>

        <div class="mb-4 -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <button
                v-for="tab in expiryTabs"
                :key="tab.value"
                type="button"
                class="flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors active:scale-95"
                :class="
                    expiry === tab.value
                        ? `${tab.activeClass} text-white shadow-sm`
                        : 'border border-slate-200 bg-white text-slate-500'
                "
                @click="pickExpiry(tab.value)"
            >
                <Icon :name="tab.icon" class="h-3.5 w-3.5" />
                {{ tab.label }}
            </button>
        </div>

        <div class="mb-3 flex items-center justify-between gap-2">
            <p class="text-xs font-medium text-slate-400">
                {{ insurances.total }} polic{{ insurances.total === 1 ? 'y' : 'ies' }} found
            </p>

            <button
                type="button"
                class="flex items-center gap-1 text-xs font-medium text-slate-500 transition-colors active:text-slate-700"
                @click="toggleSort"
            >
                <component :is="sort === 'desc' ? ArrowDownNarrowWide : ArrowUpNarrowWide" class="h-3.5 w-3.5" />
                {{ sort === 'desc' ? 'Latest first' : 'Soonest first' }}
            </button>
        </div>

        <div class="space-y-3">
            <div
                v-for="insurance in insurances.data"
                :key="insurance.id"
                class="flex overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm shadow-slate-200/60 transition-shadow hover:shadow-md hover:shadow-slate-200/70"
            >
                <span class="w-1.5 shrink-0" :class="expiryInfo(insurance.expiry_date).accentClass" />

                <div class="min-w-0 flex-1 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-semibold tracking-tight">{{ insurance.policy_no }}</p>
                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusBadgeClass(insurance.status)">
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
                            :href="`/insurances/${insurance.id}`"
                            class="flex items-center gap-1.5 rounded-full px-3 py-1.5 font-medium text-slate-600 transition-colors active:bg-slate-100"
                        >
                            <Icon name="eye" class="h-4 w-4" />
                            View
                        </Link>
                        <Link
                            :href="`/insurances/${insurance.id}/edit`"
                            class="flex items-center gap-1.5 rounded-full px-3 py-1.5 font-medium text-brand-700 transition-colors active:bg-brand-50"
                        >
                            <Icon name="edit" class="h-4 w-4" />
                            Edit
                        </Link>
                        <button
                            type="button"
                            class="flex items-center gap-1.5 rounded-full px-3 py-1.5 font-medium text-red-500 transition-colors active:bg-red-50"
                            @click="destroy(insurance)"
                        >
                            <Icon name="trash" class="h-4 w-4" />
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="insurances.data.length === 0"
                class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-slate-200 py-14 text-center"
            >
                <Icon name="inbox" class="h-8 w-8 text-slate-300" />
                <p class="text-sm text-slate-400">No insurance policies found.</p>
                <Link
                    href="/insurances/create"
                    class="mt-1 flex items-center gap-1.5 rounded-full bg-brand-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-transform active:scale-95"
                >
                    <Plus class="h-4 w-4" />
                    Add your first policy
                </Link>
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
                    :class="link.active ? 'bg-brand-900 text-white' : 'text-slate-500 active:bg-slate-100'"
                    v-html="link.label"
                />
            </template>
        </nav>
    </AppLayout>
</template>
