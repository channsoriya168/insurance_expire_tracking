<script setup>
import { INSURANCE_FIELDS } from '@/insuranceFields';

defineProps({
    form: { type: Object, required: true },
    contactMethods: { type: Array, required: true },
    submitLabel: { type: String, default: 'Save' },
});

const emit = defineEmits(['submit']);

const fieldClass =
    'mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100';
</script>

<template>
    <form class="space-y-4" @submit.prevent="emit('submit')">
        <div v-for="field in INSURANCE_FIELDS" :key="field.key">
            <label :for="field.key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ field.label }}<span v-if="field.required" class="text-red-500"> *</span>
            </label>

            <select v-if="field.type === 'select'" :id="field.key" v-model="form[field.key]" :class="fieldClass">
                <option value="">-- select --</option>
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

            <p v-if="form.errors[field.key]" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ form.errors[field.key] }}
            </p>
        </div>

        <button
            type="submit"
            :disabled="form.processing"
            class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
        >
            {{ submitLabel }}
        </button>
    </form>
</template>
