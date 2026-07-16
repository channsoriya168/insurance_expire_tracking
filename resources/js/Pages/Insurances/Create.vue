<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InsuranceForm from '@/Components/InsuranceForm.vue';
import { INSURANCE_FIELDS } from '@/insuranceFields';

defineProps({
    contactMethods: { type: Array, required: true },
    statuses: { type: Array, required: true },
    paymentStatuses: { type: Array, required: true },
    insuranceCompanies: { type: Array, required: true },
    policyTypes: { type: Array, required: true },
});

const form = useForm(Object.fromEntries(INSURANCE_FIELDS.map((field) => [field.key, ''])));

function submit() {
    form.post('/insurances');
}
</script>

<template>
    <AppLayout title="Add Insurance Policy" back-href="/insurances">
        <InsuranceForm
            :form="form"
            :contact-methods="contactMethods"
            :statuses="statuses"
            :payment-statuses="paymentStatuses"
            :insurance-companies="insuranceCompanies"
            :policy-types="policyTypes"
            mode="create"
            submit-label="Save Policy"
            @submit="submit"
        />
    </AppLayout>
</template>
