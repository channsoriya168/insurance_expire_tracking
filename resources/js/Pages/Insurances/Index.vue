<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    insurances: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(
        '/insurances',
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true },
    );
}

function destroy(insurance) {
    if (!confirm(`Delete policy ${insurance.policy_no}? This cannot be undone.`)) {
        return;
    }

    router.delete(`/insurances/${insurance.id}`);
}
</script>

<template>
    <AppLayout title="Insurance Policies">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <Link
                href="/insurances/create"
                class="inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
            >
                + Add Policy
            </Link>

            <form class="flex gap-2" @submit.prevent="applyFilters">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search policy, insured, company"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                />
                <input
                    v-model="status"
                    type="text"
                    placeholder="Status"
                    class="w-28 rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                />
                <button
                    type="submit"
                    class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700"
                >
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium">Policy No.</th>
                        <th class="px-3 py-2 text-left font-medium">Company</th>
                        <th class="px-3 py-2 text-left font-medium">Insured</th>
                        <th class="px-3 py-2 text-left font-medium">Expiry</th>
                        <th class="px-3 py-2 text-left font-medium">Status</th>
                        <th class="px-3 py-2 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    <tr v-for="insurance in insurances.data" :key="insurance.id">
                        <td class="px-3 py-2">{{ insurance.policy_no }}</td>
                        <td class="px-3 py-2">{{ insurance.insurance_company }}</td>
                        <td class="px-3 py-2">{{ insurance.insured_name }}</td>
                        <td class="px-3 py-2">{{ insurance.expiry_date }}</td>
                        <td class="px-3 py-2">{{ insurance.status }}</td>
                        <td class="px-3 py-2 text-right">
                            <div class="flex justify-end gap-3">
                                <Link :href="`/insurances/${insurance.id}/edit`" class="text-indigo-600 dark:text-indigo-400">
                                    Edit
                                </Link>
                                <button type="button" class="text-red-600 dark:text-red-400" @click="destroy(insurance)">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="insurances.data.length === 0">
                        <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                            No policies found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="insurances.links.length > 3" class="mt-4 flex flex-wrap gap-1">
            <template v-for="link in insurances.links" :key="link.label">
                <span v-if="link.url === null" class="rounded-md px-3 py-1 text-sm text-gray-400" v-html="link.label" />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    class="rounded-md px-3 py-1 text-sm"
                    :class="
                        link.active
                            ? 'bg-indigo-600 text-white'
                            : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'
                    "
                    v-html="link.label"
                />
            </template>
        </nav>
    </AppLayout>
</template>
