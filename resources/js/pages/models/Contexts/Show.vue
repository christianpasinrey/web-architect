<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Model, FieldType } from '@/types/model';
import CodeEditor from '@/components/CodeEditor.vue';
import FieldCard from '@/components/FieldCard.vue';

const props = defineProps<{
  model: Model;
  fieldTypes: FieldType[];
  modelFileContent: string;
}>();

const emits = defineEmits<{
  (e: 'back'): void;
  (e: 'edit', model: Model): void;
  (e: 'delete', model: Model): void;
}>();

const activeTab = ref<'overview' | 'fields' | 'code'>('overview');

const navigateBack = () => {
  emits('back');
};

const editModel = () => {
  emits('edit', props.model);
};

const deleteModel = () => {
  if (confirm(`¿Estás seguro de que quieres eliminar el modelo "${props.model.name}"?`)) {
    emits('delete', props.model);
  }
};

const getModelStats = computed(() => {
  const fields = props.model.fields || [];
  return {
    totalFields: fields.length,
    nullableFields: fields.filter((f: any) => !!f.nullable).length,
    uniqueFields: fields.filter((f: any) => !!f.unique).length,
    indexedFields: fields.filter((f: any) => !!f.index).length,
    foreignKeys: fields.filter((f: any) => !!f.foreign).length,
  };
});

const parsedRelations = computed(() => {
  try {
    return JSON.parse(props.model.relations || '[]');
  } catch {
    return [];
  }
});

const parsedAppends = computed(() => {
  try {
    return JSON.parse(props.model.appends || '[]');
  } catch {
    return [];
  }
});

const parsedCasts = computed(() => {
  try {
    return JSON.parse(props.model.casts || '{}');
  } catch {
    return {};
  }
});


</script>

<template>
  <div class="flex flex-col h-full bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button
            @click="navigateBack"
            class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white px-3 py-1 rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            ← Back to Models
          </button>
          <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ props.model.name }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Table: {{ props.model.table }}</p>
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <button
            @click="editModel"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 rounded hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
          >
            Edit
          </button>
          <button
            @click="deleteModel"
            class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6">
      <nav class="flex space-x-8">
        <button
          @click="activeTab = 'overview'"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
            activeTab === 'overview'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'
          ]"
        >
          Overview
        </button>
        <button
          @click="activeTab = 'fields'"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
            activeTab === 'fields'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'
          ]"
        >
          Fields ({{ props.model.fields?.length || 0 }})
        </button>
        <button
          @click="activeTab = 'code'"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
            activeTab === 'code'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'
          ]"
        >
          Generated Code
        </button>
      </nav>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-auto p-6">
      <!-- Overview Tab -->
      <div v-if="activeTab === 'overview'" class="space-y-6">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-blue-600">{{ getModelStats.totalFields }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Total Fields</div>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-green-600">{{ getModelStats.nullableFields }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Nullable</div>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-purple-600">{{ getModelStats.uniqueFields }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Unique</div>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-yellow-600">{{ getModelStats.indexedFields }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Indexed</div>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-red-600">{{ getModelStats.foreignKeys }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Foreign Keys</div>
          </div>
        </div>

        <!-- Model Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Relations -->
          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Relations</h3>
            <div v-if="parsedRelations.length > 0" class="space-y-2">
              <div
                v-for="relation in parsedRelations"
                :key="relation.name"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <div>
                  <div class="font-medium text-gray-900 dark:text-white">{{ relation.name }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">Foreign Key: {{ relation.foreignKey }}</div>
                </div>
                <span class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300 px-2 py-1 rounded">belongsTo</span>
              </div>
            </div>
            <div v-else class="text-gray-500 dark:text-gray-400 text-center py-4">
              No relations defined
            </div>
          </div>

          <!-- Appends -->
          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Appends</h3>
            <div v-if="parsedAppends.length > 0" class="space-y-2">
              <div
                v-for="append in parsedAppends"
                :key="append.name"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <div>
                  <div class="font-medium text-gray-900 dark:text-white">{{ append.name }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">Type: {{ append.type }}</div>
                </div>
              </div>
            </div>
            <div v-else class="text-gray-500 dark:text-gray-400 text-center py-4">
              No appends defined
            </div>
          </div>

          <!-- Casts -->
          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Casts</h3>
            <div v-if="Object.keys(parsedCasts).length > 0" class="space-y-2">
              <div
                v-for="(type, key) in parsedCasts"
                :key="key"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <div class="font-medium text-gray-900 dark:text-white">{{ key }}</div>
                <span class="text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300 px-2 py-1 rounded">{{ type }}</span>
              </div>
            </div>
            <div v-else class="text-gray-500 dark:text-gray-400 text-center py-4">
              No casts defined
            </div>
          </div>

          <!-- Timestamps -->
          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Timestamps</h3>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Created:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ new Date(props.model.created_at).toLocaleString() }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Updated:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ new Date(props.model.updated_at).toLocaleString() }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Fields Tab -->
      <div v-if="activeTab === 'fields'" class="space-y-4">
        <div v-if="props.model.fields && props.model.fields.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <FieldCard
            v-for="field in props.model.fields"
            :key="field.id"
            :field="field"
          />
        </div>
        <div v-else class="text-gray-500 dark:text-gray-400 text-center py-8">
          No fields defined for this model
        </div>
      </div>

      <!-- Code Tab -->
      <div v-if="activeTab === 'code'" class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Generated Model Code</h3>
          <CodeEditor
            :code="props.modelFileContent"
            language="php"
            class="w-full"
          />
        </div>
      </div>
    </div>
  </div>
</template>
