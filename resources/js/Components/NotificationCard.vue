<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import { expiryStatus } from '@/expiryStatus';

const props = defineProps({
    policy: { type: Object, required: true },
    selectMode: { type: Boolean, default: false },
    selected: { type: Boolean, default: false },
});

defineEmits(['toggle-read', 'toggle-select']);

function bucketLabel(bucket) {
    if (bucket === 'overdue') {
        return 'Expired';
    }

    if (bucket === 'today') {
        return 'Today';
    }

    return `${bucket}d`;
}
</script>

<template>
    <div
        class="relative overflow-hidden rounded-2xl border transition-all hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-200/70"
        :class="[
            policy.read
                ? 'border-slate-100 bg-white shadow-sm shadow-slate-200/40'
                : 'border-slate-200 bg-brand-50/40 shadow-md shadow-slate-200/60 ring-1 ring-inset ring-slate-100',
            selected ? 'ring-2 ring-inset ring-brand-500' : '',
        ]"
    >
        <div class="flex items-center pl-3">
            <button
                v-if="selectMode"
                type="button"
                class="flex h-11 w-11 shrink-0 items-center justify-center"
                :aria-label="selected ? 'Deselect' : 'Select'"
                @click="$emit('toggle-select', policy)"
            >
                <span
                    class="flex h-5.5 w-5.5 items-center justify-center rounded-full border-2 transition-colors"
                    :class="selected ? 'border-brand-600 bg-brand-600' : 'border-slate-300'"
                >
                    <Icon v-if="selected" name="check-circle" class="h-4 w-4 text-white" />
                </span>
            </button>

            <button
                v-else
                type="button"
                class="group flex h-11 w-11 shrink-0 items-center justify-center rounded-full transition-all active:scale-90 hover:bg-slate-100"
                :aria-label="policy.read ? 'Mark as unread' : 'Mark as read'"
                :title="policy.read ? 'Mark as unread' : 'Mark as read'"
                @click="$emit('toggle-read', policy)"
            >
                <span v-if="policy.read" class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50">
                    <Icon name="check-circle" class="h-5 w-5 text-emerald-500" />
                </span>
                <span
                    v-else
                    class="flex h-7 w-7 items-center justify-center rounded-full border-2 transition-colors"
                    :class="expiryStatus(policy.expiry_date).isToday ? 'border-red-200' : 'border-slate-200 group-hover:border-slate-300'"
                >
                    <span
                        class="h-2.5 w-2.5 rounded-full"
                        :class="[expiryStatus(policy.expiry_date).accentClass, expiryStatus(policy.expiry_date).isToday ? 'animate-pulse' : '']"
                    />
                </span>
            </button>

            <button
                v-if="selectMode"
                type="button"
                class="flex min-w-0 flex-1 items-center gap-3 py-3.5 pr-3 text-left transition-colors active:bg-slate-50"
                :class="policy.read ? 'opacity-60' : ''"
                @click="$emit('toggle-select', policy)"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="truncate text-base font-semibold tracking-tight" :class="policy.read ? 'text-slate-600' : 'text-slate-900'">
                            {{ policy.policy_no }}
                        </p>
                        <span
                            class="flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            :class="expiryStatus(policy.expiry_date).chipClass"
                        >
                            <span
                                v-if="expiryStatus(policy.expiry_date).isToday"
                                class="h-1.5 w-1.5 shrink-0 animate-pulse rounded-full bg-red-500"
                            />
                            {{ bucketLabel(policy.bucket) }}
                        </span>
                    </div>
                    <p class="mt-1 flex items-center gap-1.5 truncate text-sm text-slate-500">
                        <Icon name="building" class="h-4 w-4 shrink-0" />
                        <span class="truncate">{{ policy.insurance_company }} · {{ policy.insured_name }}</span>
                    </p>
                    <div class="mt-2 flex items-center gap-1.5 border-t border-slate-100 pt-2 text-xs font-semibold">
                        <Icon name="calendar" class="h-3.5 w-3.5 shrink-0" :class="expiryStatus(policy.expiry_date).textClass" />
                        <span :class="expiryStatus(policy.expiry_date).textClass">{{ expiryStatus(policy.expiry_date).label }}</span>
                    </div>
                </div>
            </button>

            <Link
                v-else
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
                            class="flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            :class="expiryStatus(policy.expiry_date).chipClass"
                        >
                            <span
                                v-if="expiryStatus(policy.expiry_date).isToday"
                                class="h-1.5 w-1.5 shrink-0 animate-pulse rounded-full bg-red-500"
                            />
                            {{ bucketLabel(policy.bucket) }}
                        </span>
                    </div>
                    <p class="mt-1 flex items-center gap-1.5 truncate text-sm text-slate-500">
                        <Icon name="building" class="h-4 w-4 shrink-0" />
                        <span class="truncate">{{ policy.insurance_company }} · {{ policy.insured_name }}</span>
                    </p>
                    <div class="mt-2 flex items-center gap-1.5 border-t border-slate-100 pt-2 text-xs font-semibold">
                        <Icon name="calendar" class="h-3.5 w-3.5 shrink-0" :class="expiryStatus(policy.expiry_date).textClass" />
                        <span :class="expiryStatus(policy.expiry_date).textClass">{{ expiryStatus(policy.expiry_date).label }}</span>
                    </div>
                </div>

                <Icon name="chevron-right" class="h-4 w-4 shrink-0 text-slate-300" />
            </Link>
        </div>
    </div>
</template>
