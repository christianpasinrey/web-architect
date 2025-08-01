<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Model } from '@/types/model';
import { Card, CardHeader, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import FieldsPopover from './FieldsPopover.vue';

const props = defineProps<{
    model: Model;
}>();

const emits = defineEmits<{
    (e: 'view', model: Model): void;
    (e: 'edit', model: Model): void;
    (e: 'delete', model: Model): void;
}>();

const showPopover = ref(false);

const getFieldsCount = (model: Model) => {
    return model.fields ? model.fields.length : 0;
};

const getRelationsCount = (model: Model) => {
    if (!model.relations) return 0;

    // Si es un objeto (Proxy de Laravel con las relaciones de Eloquent)
    if (typeof model.relations === 'object' && !Array.isArray(model.relations)) {
        return Object.keys(model.relations).length;
    }

    // Si ya es un array, usar directamente
    if (Array.isArray(model.relations)) {
        return model.relations.length;
    }

    // Si es una cadena, intentar parsear como JSON
    try {
        const relations = JSON.parse(model.relations);
        return Array.isArray(relations) ? relations.length : Object.keys(relations).length;
    } catch {
        return 0;
    }
};

const getModelIcon = (model: Model) => {
    const hasRelations = getRelationsCount(model) > 0;
    const hasAppends = model.appends && model.appends !== '[]';
    const hasCasts = model.casts && model.casts !== '{}';

    if (hasRelations && hasAppends) return '🔗';
    if (hasRelations) return '🗂️';
    if (hasAppends || hasCasts) return '⚡';
    return '📄';
};

const visibleFields = computed(() => {
    return props.model.fields ? props.model.fields.slice(0, 4) : [];
});

const remainingFields = computed(() => {
    return props.model.fields ? props.model.fields.slice(4) : [];
});

const handleView = () => emits('view', props.model);
const handleEdit = () => emits('edit', props.model);
const handleDelete = () => {
    if (confirm(`¿Estás seguro de que quieres eliminar el modelo "${props.model.name}"?`)) {
        emits('delete', props.model);
    }
};

const showPopoverOnHover = () => {
    showPopover.value = true;
};

const hidePopover = () => {
    showPopover.value = false;
};
</script>

<template>
    <Card class="group hover:shadow-lg transition-all duration-200 hover:-translate-y-1 flex flex-col h-full relative">
        <CardHeader class="border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                    <span class="text-2xl">{{ getModelIcon(model) }}</span>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors">
                            {{ model.name }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ model.table }}</p>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                <div class="flex items-center space-x-1">
                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                    <span>{{ getFieldsCount(model) }} fields</span>
                </div>
                <div class="flex items-center space-x-1">
                    <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                    <span>{{ model.fields?.filter(f => !!f.foreign).length || 0 }} foreign keys</span>
                </div>
            </div>
        </CardHeader>

        <CardContent class="flex-1 flex flex-col">
            <div class="flex-1">
                <div v-if="model.fields && model.fields.length > 0" class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Fields Preview:</h4>
                    <div class="space-y-2">
                        <div
                            v-for="field in visibleFields"
                            :key="field.id"
                            class="flex items-center justify-between text-xs"
                        >
                            <div class="flex items-center space-x-2">
                                <span class="font-mono text-gray-900 dark:text-white">{{ field.name }}</span>
                                <Badge v-if="!!field.nullable" variant="secondary" class="text-[10px] px-1 py-0">
                                    nullable
                                </Badge>
                                <Badge v-if="!!field.unique" variant="default" class="text-[10px] px-1 py-0">
                                    unique
                                </Badge>
                                <Badge v-if="!!field.foreign" variant="outline" class="text-[10px] px-1 py-0">
                                    FK
                                </Badge>
                            </div>
                            <span class="text-gray-400 dark:text-gray-500 font-mono text-[10px]">{{ field.field_type.column_type }}</span>
                        </div>

                        <div v-if="remainingFields.length > 0" class="relative">
                            <div
                                @mouseenter="showPopoverOnHover"
                                @mouseleave="hidePopover"
                                class="text-xs text-gray-400 dark:text-gray-500 text-center pt-1 cursor-help hover:text-blue-500 transition-colors"
                            >
                                +{{ remainingFields.length }} more fields
                            </div>

                            <!-- Popover -->
                            <FieldsPopover
                                v-if="showPopover"
                                :fields="remainingFields"
                                @mouseenter="showPopover = true"
                                @mouseleave="hidePopover"
                            />
                        </div>
                    </div>
                </div>
                <div v-else class="mb-4 text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                    No fields defined
                </div>
            </div>

            <!-- Actions -->
            <div class="flex space-x-2 pt-4 mt-auto">
                <button
                    @click="handleView"
                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 rounded hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors text-sm"
                >
                    View
                </button>
                <button
                    @click="handleEdit"
                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 rounded hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors text-sm"
                >
                    Edit
                </button>
                <button
                    @click="handleDelete"
                    class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors text-sm"
                >
                    🗑️
                </button>
            </div>
        </CardContent>
    </Card>
</template>
