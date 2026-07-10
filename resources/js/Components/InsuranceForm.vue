<script setup>
import { computed } from 'vue';
import { INSURANCE_FIELDS } from '@/insuranceFields';

defineProps({
    form: { type: Object, required: true },
    contactMethods: { type: Array, required: true },
    submitLabel: { type: String, default: 'រក្សាទុក' },
});

const emit = defineEmits(['submit']);

const sections = computed(() => {
    const grouped = [];

    for (const field of INSURANCE_FIELDS) {
        const last = grouped.at(-1);

        if (last?.title === field.section) {
            last.fields.push(field);
        } else {
            grouped.push({ title: field.section, fields: [field] });
        }
    }

    return grouped;
});

const fieldClass =
    'mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-[15px] text-slate-900 outline-none transition-colors focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/15';
</script>

<template>
    <form class="space-y-6" @submit.prevent="emit('submit')">
        <fieldset v-for="section in sections" :key="section.title" class="space-y-4">
            <legend class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                {{ section.title }}
            </legend>

            <div v-for="field in section.fields" :key="field.key">
                <label :for="field.key" class="block text-sm font-medium text-slate-600">
                    {{ field.label }}<span v-if="field.required" class="text-red-500"> *</span>
                </label>

                <select v-if="field.type === 'select'" :id="field.key" v-model="form[field.key]" :class="fieldClass">
                    <option value="">-- ជ្រើសរើស --</option>
                    <option v-for="method in contactMethods" :key="method" :value="method">{{ method }}</option>
                </select>

                <textarea
                    v-else-if="field.type === 'textarea'"
                    :id="field.key"
                    v-model="form[field.key]"
                    rows="3"
                    :class="fieldClass"
                />

                <input
                    v-else
                    :id="field.key"
                    v-model="form[field.key]"
                    :type="field.type"
                    :step="field.type === 'number' ? '0.01' : undefined"
                    :class="fieldClass"
                />

                <p v-if="form.errors[field.key]" class="mt-1.5 text-sm text-red-500">
                    {{ form.errors[field.key] }}
                </p>
            </div>
        </fieldset>

        <button
            type="submit"
            :disabled="form.processing"
            class="flex w-full items-center justify-center gap-2 rounded-full bg-brand-600 px-4 py-3.5 text-[15px] font-bold text-white shadow-md shadow-brand-600/25 transition-transform active:scale-[0.98] disabled:opacity-50"
        >
            <span
                v-if="form.processing"
                class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
            />
            {{ submitLabel }}
        </button>
    </form>
</template>
