<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InsuranceForm from '@/Components/InsuranceForm.vue';
import { INSURANCE_FIELDS } from '@/insuranceFields';

const props = defineProps({
    insurance: { type: Object, required: true },
    contactMethods: { type: Array, required: true },
});

const form = useForm(
    Object.fromEntries(INSURANCE_FIELDS.map((field) => [field.key, props.insurance[field.key] ?? ''])),
);

function submit() {
    form.put(`/insurances/${props.insurance.id}`);
}
</script>

<template>
    <AppLayout title="Edit Insurance Policy">
        <InsuranceForm
            :form="form"
            :contact-methods="contactMethods"
            mode="edit"
            submit-label="Update Policy"
            @submit="submit"
        />
    </AppLayout>
</template>
