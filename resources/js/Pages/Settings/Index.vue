<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';
</script>

<template>
    <AppLayout title="Settings" back-href="/insurances" hide-settings>
        <div class="space-y-3">
            <Link
                href="/insurance-companies"
                class="flex items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/60 transition-colors active:bg-slate-50"
            >
                <span class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                        <Icon name="building" class="h-5 w-5" />
                    </span>
                    <span class="text-[15px] font-semibold text-slate-900">Insurance Companies</span>
                </span>
                <Icon name="chevron-right" class="h-4 w-4 shrink-0 text-slate-400" />
            </Link>

            <Link
                href="/policy-types"
                class="flex items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/60 transition-colors active:bg-slate-50"
            >
                <span class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                        <Icon name="list" class="h-5 w-5" />
                    </span>
                    <span class="text-[15px] font-semibold text-slate-900">Policy Types</span>
                </span>
                <Icon name="chevron-right" class="h-4 w-4 shrink-0 text-slate-400" />
            </Link>
        </div>
    </AppLayout>
</template>
