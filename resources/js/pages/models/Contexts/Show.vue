<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import type { Model, FieldType } from '@/types/model';
import CodeEditor from '@/components/CodeEditor.vue';
import FieldCard from '@/components/FieldCard.vue';

const props = defineProps<{
  modelId: number;
}>();

const emits = defineEmits<{
  (e: 'back'): void;
  (e: 'edit', model: Model): void;
  (e: 'delete', model: Model): void;
}>();

const activeTab = ref<'overview' | 'fields' | 'code'>('overview');
const model = ref<Model | null>(null);
const fieldTypes = ref<FieldType[]>([]);
const modelFileContent = ref<string | null>(null);
const loading = ref(true);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  {
    title: 'Models',
    href: '/models',
  },
  {
    title: model.value?.name || 'Model',
    href: `/models/${props.modelId}`,
  },
]);

onMounted(async () => {
  try {
    const response = await fetch(`/models/${props.modelId}`);
    const data = await response.json();

    model.value = data.model;
    fieldTypes.value = data.fieldTypes;
    modelFileContent.value = data.modelFileContent;
  } catch (error) {
    console.error('Error loading model data:', error);
  } finally {
    loading.value = false;
  }
});

const navigateBack = () => {
  emits('back');
};

const editModel = () => {
  if (model.value) {
    emits('edit', model.value);
  }
};

const deleteModel = () => {
  if (model.value && confirm(`¿Estás seguro de que quieres eliminar el modelo "${model.value.name}"?`)) {
    emits('delete', model.value);
  }
};

const getModelStats = computed(() => {
  if (!model.value) return { totalFields: 0, nullableFields: 0, uniqueFields: 0, indexedFields: 0, foreignKeys: 0 };

  const fields = model.value.fields || [];
  return {
    totalFields: fields.length,
    nullableFields: fields.filter((f: any) => !!f.nullable).length,
    uniqueFields: fields.filter((f: any) => !!f.unique).length,
    indexedFields: fields.filter((f: any) => !!f.index).length,
    foreignKeys: fields.filter((f: any) => !!f.foreign).length,
  };
});

const parsedRelations = computed(() => {
  if (!model.value) return [];
  try {
    return JSON.parse(model.value.relations || '[]');
  } catch {
    return [];
  }
});

const parsedAppends = computed(() => {
  if (!model.value) return [];
  try {
    return JSON.parse(model.value.appends || '[]');
  } catch {
    return [];
  }
});

const parsedCasts = computed(() => {
  if (!model.value) return {};
  try {
    return JSON.parse(model.value.casts || '{}');
  } catch {
    return {};
  }
});


</script>

<template>


    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center h-full">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
          <p class="mt-4 text-gray-600 dark:text-gray-400">Cargando modelo...</p>
        </div>
      </div>

      <!-- Content -->
      <template v-else-if="model">
        <!-- Header Actions -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ model.name }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Table: {{ model.table }}</p>
          </div>
          <div class="flex items-center space-x-2">
            <button
              @click="editModel"
              class="px-4 py-2 border border-sidebar-border/70 dark:border-sidebar-border text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
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

        <!-- Tabs Navigation -->
        <div class="border border-sidebar-border/70 dark:border-sidebar-border rounded-xl">
          <nav class="flex space-x-8 px-6 bg-gray-50/50 dark:bg-gray-800/50 rounded-t-xl border-b border-sidebar-border/70 dark:border-sidebar-border">
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
              Fields ({{ model.fields?.length || 0 }})
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

          <!-- Content -->
          <div class="p-6">
            <!-- Overview Tab -->
            <div v-if="activeTab === 'overview'" class="space-y-6">
              <!-- Stats Grid -->
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-4">
                  <div class="text-2xl font-bold text-blue-600">{{ getModelStats.totalFields }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">Total Fields</div>
                </div>
                <div class="bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-4">
                  <div class="text-2xl font-bold text-green-600">{{ getModelStats.nullableFields }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">Nullable</div>
                </div>
                <div class="bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-4">
                  <div class="text-2xl font-bold text-purple-600">{{ getModelStats.uniqueFields }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">Unique</div>
                </div>
                <div class="bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-4">
                  <div class="text-2xl font-bold text-yellow-600">{{ getModelStats.indexedFields }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">Indexed</div>
                </div>
                <div class="bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-4">
                  <div class="text-2xl font-bold text-red-600">{{ getModelStats.foreignKeys }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">Foreign Keys</div>
                </div>
              </div>

              <!-- Model Information -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Relations -->
                <div class="bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-6">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Relations</h3>
                  <div v-if="parsedRelations.length > 0" class="space-y-2">
                    <div
                      v-for="relation in parsedRelations"
                      :key="relation.name"
                      class="flex items-center justify-between p-3 bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/50 dark:border-sidebar-border"
                    >
                      <div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ relation.name }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Foreign Key: {{ relation.foreignKey }}</div>
                      </div>
                      <span class="text-xs bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 px-2 py-1 rounded">belongsTo</span>
                    </div>
                  </div>
                  <div v-else class="text-gray-500 dark:text-gray-400 text-center py-4">
                    No relations defined
                  </div>
                </div>

                <!-- Appends -->
                <div class="bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-6">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Appends</h3>
                  <div v-if="parsedAppends.length > 0" class="space-y-2">
                    <div
                      v-for="append in parsedAppends"
                      :key="append.name"
                      class="flex items-center justify-between p-3 bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/50 dark:border-sidebar-border"
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
                <div class="bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-6">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Casts</h3>
                  <div v-if="Object.keys(parsedCasts).length > 0" class="space-y-2">
                    <div
                      v-for="(type, key) in parsedCasts"
                      :key="key"
                      class="flex items-center justify-between p-3 bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/50 dark:border-sidebar-border"
                    >
                      <div class="font-medium text-gray-900 dark:text-white">{{ key }}</div>
                      <span class="text-xs bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 px-2 py-1 rounded">{{ type }}</span>
                    </div>
                  </div>
                  <div v-else class="text-gray-500 dark:text-gray-400 text-center py-4">
                    No casts defined
                  </div>
                </div>

                <!-- Timestamps -->
                <div class="bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-6">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Timestamps</h3>
                  <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                      <span class="text-gray-600 dark:text-gray-400">Created:</span>
                      <span class="font-medium text-gray-900 dark:text-white">{{ new Date(model.created_at).toLocaleString() }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                      <span class="text-gray-600 dark:text-gray-400">Updated:</span>
                      <span class="font-medium text-gray-900 dark:text-white">{{ new Date(model.updated_at).toLocaleString() }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Fields Tab -->
            <div v-if="activeTab === 'fields'" class="space-y-4">
              <div v-if="model.fields && model.fields.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <FieldCard
                  v-for="field in model.fields"
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
              <div class="bg-white dark:bg-black/80 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Generated Model Code</h3>
                <CodeEditor
                  :code="modelFileContent || ''"
                  language="php"
                  class="w-full"
                />
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Error State -->
      <div v-else class="flex items-center justify-center h-full">
        <div class="text-center">
          <p class="text-red-600 dark:text-red-400">Error al cargar el modelo</p>
          <button
            @click="$emit('back')"
            class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
          >
            Volver a modelos
          </button>
        </div>
      </div>
    </div>
</template>
