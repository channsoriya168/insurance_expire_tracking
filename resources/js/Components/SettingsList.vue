<script setup>
import { ref } from 'vue';
import { Link, router, useHttp } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import Icon from '@/Components/Icon.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

const props = defineProps({
    label: { type: String, required: true },
    paginator: { type: Object, required: true },
    initialSearch: { type: String, default: '' },
    url: { type: String, required: true },
});

const search = ref(props.initialSearch);

watchDebounced(
    search,
    () => {
        router.get(props.url, { search: search.value || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    },
    { debounce: 300 },
);

const dialogOpen = ref(false);
const editingItem = ref(null);
const form = useHttp({ name: '' });
const deleteHttp = useHttp({});

function openCreate() {
    editingItem.value = null;
    form.name = '';
    form.errors.name = undefined;
    dialogOpen.value = true;
}

function openEdit(item) {
    editingItem.value = item;
    form.name = item.name;
    form.errors.name = undefined;
    dialogOpen.value = true;
}

function onSaved() {
    dialogOpen.value = false;
    router.reload();
}

function save() {
    if (editingItem.value) {
        form.patch(`${props.url}/${editingItem.value.id}`, { onSuccess: onSaved });
    } else {
        form.post(props.url, { onSuccess: onSaved });
    }
}

function destroy(item) {
    if (!confirm(`Delete "${item.name}"?`)) {
        return;
    }

    deleteHttp.delete(`${props.url}/${item.id}`, {
        onSuccess: () => router.reload(),
        onHttpException: (response) => alert(response.data?.message ?? `"${item.name}" could not be deleted.`),
    });
}
</script>

<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/60">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-700">{{ label }}s</h3>
            <Button type="button" variant="ghost" size="sm" class="gap-1 text-brand-700" @click="openCreate">
                <Icon name="plus" class="h-4 w-4" />
                Add
            </Button>
        </div>

        <div class="relative mb-3">
            <Icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
                v-model="search"
                type="search"
                :placeholder="`Search ${label.toLowerCase()}s`"
                class="w-full rounded-full border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/15"
            />
        </div>

        <ul class="divide-y divide-slate-100">
            <li v-for="item in paginator.data" :key="item.id" class="flex items-center justify-between gap-2 py-2.5">
                <span class="truncate text-[15px] text-slate-900">{{ item.name }}</span>
                <div class="flex shrink-0 items-center gap-1">
                    <Button type="button" variant="ghost" size="icon-sm" class="text-slate-500" @click="openEdit(item)">
                        <Icon name="edit" class="h-4 w-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon-sm" class="text-red-500" @click="destroy(item)">
                        <Icon name="trash" class="h-4 w-4" />
                    </Button>
                </div>
            </li>

            <li v-if="paginator.data.length === 0" class="py-4 text-center text-sm text-slate-400">
                No {{ label.toLowerCase() }}s found.
            </li>
        </ul>

        <nav v-if="paginator.links.length > 3" class="mt-3 flex flex-wrap justify-center gap-1.5">
            <template v-for="link in paginator.links" :key="link.label">
                <span
                    v-if="link.url === null"
                    class="flex h-8 min-w-8 items-center justify-center rounded-full px-2 text-xs text-slate-300"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    class="flex h-8 min-w-8 items-center justify-center rounded-full px-2 text-xs font-medium transition-colors"
                    :class="link.active ? 'bg-brand-900 text-white' : 'text-slate-500 active:bg-slate-100'"
                    v-html="link.label"
                />
            </template>
        </nav>
    </section>

    <Dialog v-model:open="dialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ editingItem ? `Edit ${label.toLowerCase()}` : `Add new ${label.toLowerCase()}` }}</DialogTitle>
            </DialogHeader>

            <div>
                <Label for="settings-list-name" class="text-sm font-medium text-slate-600">Name</Label>
                <Input id="settings-list-name" v-model="form.name" class="mt-1.5" @keyup.enter="save" />
                <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-500">{{ form.errors.name }}</p>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="dialogOpen = false">Cancel</Button>
                <Button type="button" :disabled="form.processing" @click="save">Save</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
