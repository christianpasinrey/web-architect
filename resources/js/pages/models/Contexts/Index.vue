<script setup lang="ts">
    import {ref, computed, onMounted} from 'vue';
    import type {Model, FieldType} from '@/types/model';
    import { ModelCard } from '@/components/ui/model-card';
import Input from '@/components/ui/input/Input.vue';

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
        emits('delete', model);
    };

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

    onMounted(() => {
        console.log('Models:', props.models);
        console.log('Field Types:', props.fieldTypes);
    });
</script>
<template>
    <div class="flex flex-col p-6 w-full">
<!-- Quick Stats -->
        <div v-if="filteredModels.length > 0" class="mt-8 bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-6">
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
        <!-- Search and Actions -->
        <div class="mb-6 flex flex-col sm:flex-row gap-4 p-4">
            <div class="flex-1">
                <Input
                    v-model="searchQuery"
                    placeholder="Search models by name..."
                    class="w-full"
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
            <ModelCard
                v-for="model in filteredModels"
                :key="model.id"
                :model="model"
                @view="viewModel"
                @edit="editModel"
                @delete="deleteModel"
            />
        </div>


    </div>
</template>
