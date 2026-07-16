<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SettingsList from '@/Components/SettingsList.vue';

defineProps({
    insuranceCompanies: { type: Object, required: true },
    filters: { type: Object, required: true },
});
</script>

<template>
    <AppLayout title="Insurance Companies" back-href="/settings" hide-settings>
        <SettingsList
            label="Insurance Company"
            :paginator="insuranceCompanies"
            :initial-search="filters.search ?? ''"
            url="/insurance-companies"
        />
    </AppLayout>
</template>
