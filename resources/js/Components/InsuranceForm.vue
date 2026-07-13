<script setup>
import { computed } from 'vue';
import { INSURANCE_FIELDS } from '@/insuranceFields';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

const props = defineProps({
    form: { type: Object, required: true },
    contactMethods: { type: Array, required: true },
    submitLabel: { type: String, default: 'Save' },
    mode: { type: String, default: 'create' },
});

const emit = defineEmits(['submit']);

const visibleFields = computed(() =>
    INSURANCE_FIELDS.filter((field) => !(field.hideOnCreate && props.mode === 'create')),
);

const sections = computed(() => {
    const grouped = [];

    for (const field of visibleFields.value) {
        let group = grouped.find((g) => g.title === field.section);

        if (!group) {
            group = { title: field.section, fields: [], advancedFields: [] };
            grouped.push(group);
        }

        (field.advanced ? group.advancedFields : group.fields).push(field);
    }

    return grouped;
});

const fieldClass =
    'mt-1.5 h-auto w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-[15px] text-slate-900 focus-visible:border-brand-500 focus-visible:bg-white focus-visible:ring-brand-500/15';

function inputClass(field) {
    return field.type === 'date' ? `${fieldClass} appearance-none` : fieldClass;
}
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

                    <Select v-if="field.type === 'select'" v-model="form[field.key]">
                        <SelectTrigger :id="field.key" :class="fieldClass">
                            <SelectValue :placeholder="`Select ${field.label.toLowerCase()}`" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="method in contactMethods" :key="method" :value="method">
                                {{ method }}
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
                        :step="field.type === 'number' ? '0.01' : undefined"
                        :placeholder="field.placeholder"
                        :class="inputClass(field)"
                    />

                    <p v-if="form.errors[field.key]" class="mt-1.5 text-sm text-red-500">
                        {{ form.errors[field.key] }}
                    </p>
                </div>
            </div>

            <details v-if="section.advancedFields.length" class="group mt-4">
                <summary
                    class="flex cursor-pointer list-none items-center gap-1.5 text-sm font-medium text-brand-700 select-none"
                >
                    <span class="transition-transform group-open:rotate-90">›</span>
                    Optional details
                </summary>

                <div class="mt-3 space-y-4 border-t border-slate-200 pt-4">
                    <div v-for="field in section.advancedFields" :key="field.key">
                        <Label :for="field.key" class="text-sm font-medium text-slate-600">
                            {{ field.label }}
                        </Label>

                        <Input
                            :id="field.key"
                            v-model="form[field.key]"
                            :type="field.type"
                            :step="field.type === 'number' ? '0.01' : undefined"
                            :placeholder="field.placeholder"
                            :class="inputClass(field)"
                        />

                        <p v-if="form.errors[field.key]" class="mt-1.5 text-sm text-red-500">
                            {{ form.errors[field.key] }}
                        </p>
                    </div>
                </div>
            </details>
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
