<script setup lang="ts">
    import {ref, computed} from 'vue';
    import type {Model, FieldType} from '@/types/model';

    const props = defineProps<{
        models: Model[];
        fieldTypes: FieldType[];
    }>();

    const emits = defineEmits<{
        (e: 'create'): void;
    }>();

    const searchQuery = ref('');
    const filteredModels = computed(() => {
        if (!searchQuery.value) {
            return props.models;
        }
        return props.models.filter((model: Model) => model.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
    });
</script>
<template>
    <div class="flex flex-col p-12 w-full">
        <div class="mb-6">
            <input
                v-model="searchQuery"
                placeholder="Search models..."
                class="px-4 py-2 border border-gray-300 rounded-lg mr-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
            <button
                @click="emits('create')"
                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition"
            >
                Create Model
            </button>
        </div>

        <div v-if="filteredModels.length === 0" class="text-gray-500 text-center py-8">
            No models found. Create your first model!
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="model in filteredModels"
                :key="model.id"
                class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition"
            >
                <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ model.name }}</h3>
                <p class="text-gray-600 mb-4">Table: {{ model.table }}</p>

                <div v-if="model.fields && model.fields.length > 0" class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Fields:</h4>
                    <ul class="space-y-1">
                        <li
                            v-for="field in model.fields.slice(0, 5)"
                            :key="field.id"
                            class="text-xs text-gray-600 flex justify-between"
                        >
                            <span class="font-mono">{{ field.name }}</span>
                            <span class="text-gray-400">{{ field.field_type.column_type }}</span>
                        </li>
                        <li v-if="model.fields.length > 5" class="text-xs text-gray-400">
                            +{{ model.fields.length - 5 }} more fields
                        </li>
                    </ul>
                </div>

                <div class="flex space-x-2 mt-4">
                    <button class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition">
                        View
                    </button>
                    <button class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition">
                        Edit
                    </button>
                    <button class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200 transition">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
