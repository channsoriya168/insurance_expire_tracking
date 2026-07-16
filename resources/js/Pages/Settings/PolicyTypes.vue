<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SettingsList from '@/Components/SettingsList.vue';

defineProps({
    policyTypes: { type: Object, required: true },
    filters: { type: Object, required: true },
});
</script>

<template>
    <AppLayout title="Policy Types" back-href="/settings" hide-settings>
        <SettingsList label="Policy Type" :paginator="policyTypes" :initial-search="filters.search ?? ''" url="/policy-types" />
    </AppLayout>
</template>
