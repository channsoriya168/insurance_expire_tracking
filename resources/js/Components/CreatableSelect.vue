<script setup>
import { computed, ref, watch } from 'vue';
import { useHttp } from '@inertiajs/vue3';
import { PlusIcon } from '@lucide/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectSeparator, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

const CREATE_OPTION = '__create__';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, required: true },
    placeholder: { type: String, default: 'Select an option' },
    label: { type: String, required: true },
    createUrl: { type: String, required: true },
    id: { type: String, default: undefined },
    triggerClass: { type: [String, Array, Object], default: '' },
});

const emit = defineEmits(['update:modelValue']);

const localOptions = ref([...props.options]);

watch(
    () => props.options,
    (options) => {
        localOptions.value = [...options];
    },
);

const dialogOpen = ref(false);
const newName = ref('');
const http = useHttp({ name: '' });

const selectValue = computed({
    get: () => (props.modelValue ? String(props.modelValue) : ''),
    set: (value) => {
        if (value === CREATE_OPTION) {
            newName.value = '';
            http.errors.name = undefined;
            dialogOpen.value = true;
            return;
        }

        emit('update:modelValue', value);
    },
});

function createOption() {
    http.name = newName.value;
    http.post(props.createUrl, {
        onSuccess: (data) => {
            if (!localOptions.value.some((option) => option.id === data.id)) {
                localOptions.value.push(data);
            }

            emit('update:modelValue', String(data.id));
            dialogOpen.value = false;
        },
    });
}
</script>

<template>
    <Select v-model="selectValue">
        <SelectTrigger :id="id" :class="triggerClass">
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem v-for="option in localOptions" :key="option.id" :value="String(option.id)">
                {{ option.name }}
            </SelectItem>
            <SelectSeparator v-if="localOptions.length" />
            <SelectItem :value="CREATE_OPTION" class="text-brand-700 font-medium">
                <PlusIcon class="size-4" />
                Add new {{ label }}
            </SelectItem>
        </SelectContent>
    </Select>

    <Dialog v-model:open="dialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Add new {{ label }}</DialogTitle>
            </DialogHeader>

            <div>
                <Label for="creatable-select-new-name" class="text-sm font-medium text-slate-600">Name</Label>
                <Input
                    id="creatable-select-new-name"
                    v-model="newName"
                    class="mt-1.5"
                    :placeholder="`e.g. New ${label}`"
                    @keyup.enter="createOption"
                />
                <p v-if="http.errors.name" class="mt-1.5 text-sm text-red-500">{{ http.errors.name }}</p>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="dialogOpen = false">Cancel</Button>
                <Button type="button" :disabled="http.processing" @click="createOption">Create</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
