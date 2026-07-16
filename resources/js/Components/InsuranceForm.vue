<script setup>
import { computed, watch } from 'vue';
import { INSURANCE_FIELDS } from '@/insuranceFields';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import CreatableSelect from '@/Components/CreatableSelect.vue';

const props = defineProps({
    form: { type: Object, required: true },
    contactMethods: { type: Array, required: true },
    statuses: { type: Array, required: true },
    paymentStatuses: { type: Array, default: () => [] },
    insuranceCompanies: { type: Array, default: () => [] },
    policyTypes: { type: Array, default: () => [] },
    submitLabel: { type: String, default: 'Save' },
    mode: { type: String, default: 'create' },
});

function selectOptions(field) {
    if (field.key === 'status') {
        return props.statuses;
    }

    if (field.key === 'payment_status') {
        return props.paymentStatuses;
    }

    return props.contactMethods;
}

function creatableOptions(field) {
    return field.optionsKey === 'policyTypes' ? props.policyTypes : props.insuranceCompanies;
}

const emit = defineEmits(['submit']);

const visibleFields = computed(() =>
    INSURANCE_FIELDS.filter((field) => !(field.hideOnCreate && props.mode === 'create')),
);

const sections = computed(() => {
    const grouped = [];

    for (const field of visibleFields.value) {
        let group = grouped.find((g) => g.title === field.section);

        if (!group) {
            group = { title: field.section, fields: [] };
            grouped.push(group);
        }

        group.fields.push(field);
    }

    return grouped;
});

const fieldClass =
    'mt-1.5 h-auto w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-[15px] text-slate-900 focus-visible:border-brand-500 focus-visible:bg-white focus-visible:ring-brand-500/15';

function inputClass(field) {
    return field.type === 'date' ? `${fieldClass} appearance-none` : fieldClass;
}

function roundTo(value, decimals) {
    const factor = 10 ** decimals;

    return Math.round(value * factor) / factor;
}

watch(
    () => [props.form.revised_sum_insured, props.form.revised_premium],
    ([revisedSumInsured, revisedPremium]) => {
        const sumInsured = parseFloat(revisedSumInsured);
        const premium = parseFloat(revisedPremium);

        if (!Number.isFinite(sumInsured) || sumInsured === 0 || !Number.isFinite(premium)) {
            return;
        }

        props.form.revised_premium_rate = ((premium / sumInsured) * 100).toFixed(3);
    },
);

watch(
    () => props.form.revised_premium,
    (revisedPremium) => {
        const premium = parseFloat(revisedPremium);

        if (!Number.isFinite(premium)) {
            return;
        }

        props.form.net_premium = roundTo(premium * (85 / 100), 2);
    },
);
</script>

<template>
    <form class="space-y-5" @submit.prevent="emit('submit')">
        <section
            v-for="section in sections"
            :key="section.title"
            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/60"
        >
            <h3 class="mb-3 text-sm font-semibold text-slate-700">{{ section.title }}</h3>

            <div class="space-y-4">
                <div v-for="field in section.fields" :key="field.key">
                    <Label :for="field.key" class="text-sm font-medium text-slate-600">
                        {{ field.label }}<span v-if="field.required" class="text-red-500"> *</span>
                    </Label>

                    <CreatableSelect
                        v-if="field.type === 'creatable-select'"
                        v-model="form[field.key]"
                        :id="field.key"
                        :options="creatableOptions(field)"
                        :placeholder="field.placeholder"
                        :label="field.label.toLowerCase()"
                        :create-url="field.createUrl"
                        :trigger-class="fieldClass"
                    />

                    <Select v-else-if="field.type === 'select'" v-model="form[field.key]">
                        <SelectTrigger :id="field.key" :class="fieldClass">
                            <SelectValue :placeholder="`Select ${field.label.toLowerCase()}`" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="option in selectOptions(field)" :key="option" :value="option">
                                {{ option }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Textarea
                        v-else-if="field.type === 'textarea'"
                        :id="field.key"
                        v-model="form[field.key]"
                        rows="3"
                        :placeholder="field.placeholder"
                        :class="fieldClass"
                    />

                    <Input
                        v-else
                        :id="field.key"
                        v-model="form[field.key]"
                        :type="field.type"
                        :step="field.type === 'number' ? (field.step ?? '0.01') : undefined"
                        :placeholder="field.placeholder"
                        :class="inputClass(field)"
                    />

                    <p v-if="form.errors[field.key]" class="mt-1.5 text-sm text-red-500">
                        {{ form.errors[field.key] }}
                    </p>
                </div>
            </div>
        </section>

        <Button
            type="submit"
            :disabled="form.processing"
            class="h-auto w-full gap-2 rounded-full bg-brand-900 px-4 py-3.5 text-[15px] font-bold text-white shadow-md shadow-brand-900/25 hover:bg-brand-900 active:scale-[0.98] disabled:opacity-50"
        >
            <span
                v-if="form.processing"
                class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
            />
            {{ submitLabel }}
        </Button>
    </form>
</template>
