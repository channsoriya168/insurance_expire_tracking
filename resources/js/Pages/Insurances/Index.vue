 <script setup>
import { Link, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import { ArrowDownNarrowWide, ArrowUpNarrowWide, Plus, Search, X } from '@lucide/vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';
import ExpiryTabs from '@/Components/ExpiryTabs.vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { expiryStatus as expiryInfo } from '@/expiryStatus';

const PAYMENT_STATUSES = ['Unpaid', 'Paid', '30% Self Insured'];

const props = defineProps({
    insurances: { type: Object, required: true },
    filters: { type: Object, required: true },
    expiryThresholds: { type: Array, default: () => [] },
});

const search = ref(props.filters.search ?? '');
const expiry = ref(props.filters.expiry ?? '');
const sort = ref(props.filters.sort ?? 'desc');
const showSearch = ref(!!search.value);

function applyFilters() {
    router.get(
        '/insurances',
        {
            search: search.value || undefined,
            expiry: expiry.value || undefined,
            sort: sort.value === 'asc' ? 'asc' : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function pickExpiry(value) {
    expiry.value = value;
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

function paymentStatusBadgeClass(value) {
    const normalized = value || 'Unpaid';

    if (normalized === 'Paid') {
        return 'bg-emerald-50 text-emerald-600';
    }

    if (normalized === 'Unpaid') {
        return 'bg-red-50 text-red-500';
    }

    return 'bg-amber-50 text-amber-600';
}

const openPaymentStatusId = ref(null);

function updatePaymentStatus(insurance, paymentStatus) {
    openPaymentStatusId.value = null;

    if (paymentStatus === (insurance.payment_status || 'Unpaid')) {
        return;
    }

    router.patch(
        `/insurances/${insurance.id}/payment-status`,
        { payment_status: paymentStatus },
        { preserveScroll: true, preserveState: true, only: ['insurances'] },
    );
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
                        class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-9 text-base text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 md:text-sm"
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

        <ExpiryTabs :model-value="expiry" :expiry-thresholds="props.expiryThresholds" @update:model-value="pickExpiry" />

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
                {{ sort === 'desc' ? 'Newest first' : 'Oldest first' }}
            </button>
        </div>

        <div class="space-y-3">
            <div
                v-for="insurance in insurances.data"
                :key="insurance.id"
                class="relative overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm shadow-slate-200/60 transition-all hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-200/70"
            >
                <span class="absolute inset-y-0 left-0 w-1" :class="expiryInfo(insurance.expiry_date).accentClass" />

                <div class="p-4 pl-5">
                    <div class="flex items-start justify-between gap-2">
                        <p class="truncate text-base font-semibold tracking-tight text-slate-900">{{ insurance.policy_no }}</p>
                        <Popover
                            :open="openPaymentStatusId === insurance.id"
                            @update:open="(value) => (openPaymentStatusId = value ? insurance.id : null)"
                        >
                            <PopoverTrigger as-child>
                                <button
                                    type="button"
                                    class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium transition-transform active:scale-95"
                                    :class="paymentStatusBadgeClass(insurance.payment_status)"
                                >
                                    {{ insurance.payment_status || 'Unpaid' }}
                                </button>
                            </PopoverTrigger>
                            <PopoverContent class="w-44 gap-0.5 p-1.5" align="end">
                                <button
                                    v-for="option in PAYMENT_STATUSES"
                                    :key="option"
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 transition-colors active:bg-slate-100"
                                    @click="updatePaymentStatus(insurance, option)"
                                >
                                    {{ option }}
                                    <Icon
                                        v-if="option === (insurance.payment_status || 'Unpaid')"
                                        name="check-circle"
                                        class="h-4 w-4 shrink-0 text-brand-700"
                                    />
                                </button>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                        <Icon name="building" class="h-4 w-4 shrink-0" />
                        <span class="truncate">{{ insurance.insurance_company }} · {{ insurance.insured_name }}</span>
                    </p>

                    <div
                        class="mt-3 flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold"
                        :class="expiryInfo(insurance.expiry_date).chipClass"
                    >
                        <Icon name="calendar" class="h-3.5 w-3.5 shrink-0" />
                        {{ expiryInfo(insurance.expiry_date).label }}
                    </div>

                    <div class="mt-3 grid grid-cols-3 gap-1.5 border-slate-100 text-sm">
                        <Link
                            :href="`/insurances/${insurance.id}`"
                            class="flex items-center justify-center gap-1.5 rounded-lg py-2 font-medium text-slate-600 transition-colors active:bg-slate-100"
                        >
                            <Icon name="eye" class="h-4 w-4" />
                            View
                        </Link>
                        <Link
                            :href="`/insurances/${insurance.id}/edit`"
                            class="flex items-center justify-center gap-1.5 rounded-lg py-2 font-medium text-brand-700 transition-colors active:bg-brand-50"
                        >
                            <Icon name="edit" class="h-4 w-4" />
                            Edit
                        </Link>
                        <button
                            type="button"
                            class="flex items-center justify-center gap-1.5 rounded-lg py-2 font-medium text-red-500 transition-colors active:bg-red-50"
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
