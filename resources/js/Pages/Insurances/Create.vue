<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InsuranceForm from '@/Components/InsuranceForm.vue';
import { INSURANCE_FIELDS } from '@/insuranceFields';

defineProps({
    contactMethods: { type: Array, required: true },
});

const form = useForm(Object.fromEntries(INSURANCE_FIELDS.map((field) => [field.key, ''])));

function submit() {
    form.post('/insurances');
}
</script>

<template>
    <AppLayout title="Add Policy">
        <Link href="/insurances" class="mb-4 inline-block text-sm text-indigo-600 dark:text-indigo-400">
            &larr; Back to list
        </Link>

        <InsuranceForm :form="form" :contact-methods="contactMethods" submit-label="Save Policy" @submit="submit" />
    </AppLayout>
</template>
