<script setup lang="ts">
    import {ref, computed, onMounted} from 'vue';
    import { router } from '@inertiajs/vue3';
    import type {Model, FieldType} from '@/types/model';

    const props = defineProps<{
        models: Model[];
        fieldTypes: FieldType[];
    }>();

    const emits = defineEmits<{
        (e: 'create'): void;
        (e: 'view', model: Model): void;
        (e: 'edit', model: Model): void;
        (e: 'delete', model: Model): void;
    }>();

    const searchQuery = ref('');
    const filteredModels = computed(() => {
        if (!searchQuery.value) {
            return props.models;
        }
        return props.models.filter((model: Model) => model.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
    });

    const viewModel = (model: Model) => {
        emits('view', model);
    };

    const editModel = (model: Model) => {
        emits('edit', model);
    };

    const deleteModel = (model: Model) => {
        if (confirm(`¿Estás seguro de que quieres eliminar el modelo "${model.name}"?`)) {
            emits('delete', model);
        }
    };

    const getFieldsCount = (model: Model) => {
        return model.fields ? model.fields.length : 0;
    };

    const getRelationsCount = (model: Model) => {
        try {
            const relations = JSON.parse(model.relations || '[]');
            return Array.isArray(relations) ? relations.length : 0;
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

    onMounted(() => {
        console.log('Models:', props.models);
        console.log('Field Types:', props.fieldTypes);
    });
</script>
<template>
    <div class="flex flex-col p-6 w-full">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Models</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage your database models and their structure</p>
        </div>

        <!-- Search and Actions -->
        <div class="mb-6 flex flex-col sm:flex-row gap-4 bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex-1">
                <input
                    v-model="searchQuery"
                    placeholder="Search models by name..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
            <button
                @click="emits('create')"
                class="sm:w-auto px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors"
            >
                <span class="mr-2">+</span>
                Create Model
            </button>
        </div>

        <!-- Models Grid -->
        <div v-if="filteredModels.length === 0" class="text-center py-12">
            <div class="text-6xl mb-4">📦</div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No models found</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                {{ searchQuery ? 'Try adjusting your search or create a new model.' : 'Create your first model to get started!' }}
            </p>
            <button v-if="!searchQuery" @click="emits('create')" class="px-6 py-3 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                Create Your First Model
            </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <div
                v-for="model in filteredModels"
                :key="model.id"
                class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-lg transition-all duration-200 hover:-translate-y-1 flex flex-col"
            >
                <!-- Model Header -->
                <div class="p-6 border-b border-gray-100 dark:border-gray-700">
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
                </div>

                <!-- Fields Preview -->
                <div class="p-6 flex-1 flex flex-col">
                    <div class="flex-1">
                        <div v-if="model.fields && model.fields.length > 0" class="mb-4">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Fields Preview:</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="field in model.fields.slice(0, 4)"
                                    :key="field.id"
                                    class="flex items-center justify-between text-xs"
                                >
                                    <div class="flex items-center space-x-2">
                                        <span class="font-mono text-gray-900 dark:text-white">{{ field.name }}</span>
                                        <span v-if="!!field.nullable" class="px-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-300 rounded text-[10px]">nullable</span>
                                        <span v-if="!!field.unique" class="px-1 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded text-[10px]">unique</span>
                                        <span v-if="!!field.foreign" class="px-1 bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300 rounded text-[10px]">FK</span>
                                    </div>
                                    <span class="text-gray-400 dark:text-gray-500 font-mono text-[10px]">{{ field.field_type.column_type }}</span>
                                </div>
                                <div v-if="model.fields.length > 4" class="text-xs text-gray-400 dark:text-gray-500 text-center pt-1">
                                    +{{ model.fields.length - 4 }} more fields
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
                            @click="viewModel(model)"
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 rounded hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors text-sm"
                        >
                            View
                        </button>
                        <button
                            @click="editModel(model)"
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 rounded hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors text-sm"
                        >
                            Edit
                        </button>
                        <button
                            @click="deleteModel(model)"
                            class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors text-sm"
                        >
                            🗑️
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div v-if="filteredModels.length > 0" class="mt-8 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Stats</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ filteredModels.length }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Models</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">
                        {{ filteredModels.reduce((acc, model) => acc + getFieldsCount(model), 0) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Fields</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">
                        {{ filteredModels.reduce((acc, model) => acc + (model.fields?.filter(f => !!f.foreign).length || 0), 0) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Foreign Keys</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600">
                        {{ filteredModels.reduce((acc, model) => acc + getRelationsCount(model), 0) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Relations</div>
                </div>
            </div>
        </div>
    </div>
</template>
