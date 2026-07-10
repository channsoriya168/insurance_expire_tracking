<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InsuranceForm from '@/Components/InsuranceForm.vue';
import Icon from '@/Components/Icon.vue';
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
    <AppLayout title="កែសម្រួលបណ្ណសន្យារ៉ាប់រង">
        <Link href="/insurances" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-brand-700">
            <Icon name="chevron-left" class="h-4 w-4" />
            ត្រឡប់ទៅបញ្ជី
        </Link>

        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm shadow-slate-200/60 sm:p-5">
            <InsuranceForm :form="form" :contact-methods="contactMethods" submit-label="ធ្វើបច្ចុប្បន្នភាពបណ្ណសន្យារ៉ាប់រង" @submit="submit" />
        </div>
    </AppLayout>
</template>
