<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import type { FieldType } from '@/types/model';
import FieldBuilder from '@/components/FieldBuilder.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    fieldTypes: FieldType[];
}>();

const emits = defineEmits<{
    (e: 'cancel'): void;
}>();

interface FieldData {
  name: string;
  type: string;
  label?: string;
  nullable?: boolean;
  unique?: boolean;
  index?: boolean;
  primary?: boolean;
  auto_increment?: boolean;
  default?: string;
  foreign?: boolean;
  foreign_table?: string;
  foreign_key?: string;
}

interface RelationData {
  name: string;
  foreignKey: string;
}

const model = reactive({
  name: '',
  table: '',
  fillable: [] as FieldData[],
  relations: [] as RelationData[],
  appends: [] as any[],
  casts: [] as { key: string; type: string }[],
});

const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

// Auto-generate table name from model name
const updateTableName = () => {
  if (model.name && !model.table) {
    model.table = model.name
      .replace(/([A-Z])/g, '_$1')
      .toLowerCase()
      .replace(/^_/, '')
      .concat('s'); // pluralize
  }
};

const addField = () => {
  model.fillable.push({
    name: '',
    type: '',
    nullable: true,
  });
};

const removeField = (index: number) => {
  model.fillable.splice(index, 1);
};

const updateField = (index: number, field: FieldData) => {
  model.fillable[index] = field;
};

const addRelation = () => {
  model.relations.push({
    name: '',
    foreignKey: '',
  });
};

const removeRelation = (index: number) => {
  model.relations.splice(index, 1);
};

const addCast = () => {
  model.casts.push({
    key: '',
    type: 'string',
  });
};

const removeCast = (index: number) => {
  model.casts.splice(index, 1);
};

const validateForm = () => {
  errors.value = {};

  if (!model.name.trim()) {
    errors.value.name = 'Model name is required';
  }

  if (!model.table.trim()) {
    errors.value.table = 'Table name is required';
  }

  if (model.fillable.length === 0) {
    errors.value.fields = 'At least one field is required';
  }

  // Validate fields
  model.fillable.forEach((field, index) => {
    if (!field.name.trim()) {
      errors.value[`field_${index}_name`] = 'Field name is required';
    }
    if (!field.type) {
      errors.value[`field_${index}_type`] = 'Field type is required';
    }
  });

  return Object.keys(errors.value).length === 0;
};

const submitForm = async () => {
  if (!validateForm()) {
    return;
  }

  isSubmitting.value = true;

  try {
    await router.post('/models', {
      name: model.name,
      table: model.table,
      fillable: model.fillable,
      relations: model.relations,
      appends: model.appends,
      casts: model.casts,
    }, {
      onSuccess: () => {
        emits('cancel'); // Close the form
      },
      onError: (formErrors) => {
        errors.value = formErrors;
      }
    });
  } catch (error) {
    console.error('Error creating model:', error);
  } finally {
    isSubmitting.value = false;
  }
};

const cancel = () => {
  emits('cancel');
};

// Initialize with one empty field
if (model.fillable.length === 0) {
  addField();
}

const castTypes = [
  'string',
  'integer',
  'float',
  'boolean',
  'array',
  'object',
  'collection',
  'date',
  'datetime',
  'timestamp',
];
</script>

<template>
  <div class="flex flex-col h-full bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Create New Model</h1>
          <p class="text-sm text-gray-600">Define your model structure and generate Laravel files</p>
        </div>

        <div class="flex items-center space-x-2">
          <Button
            variant="outline"
            @click="cancel"
            :disabled="isSubmitting"
          >
            Cancel
          </Button>
          <Button
            @click="submitForm"
            :disabled="isSubmitting"
          >
            {{ isSubmitting ? 'Creating...' : 'Create Model' }}
          </Button>
        </div>
      </div>
    </div>

    <!-- Form Content -->
    <div class="flex-1 overflow-auto p-6 space-y-8">
      <!-- Basic Information -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Model Name *</label>
            <Input
              v-model="model.name"
              @blur="updateTableName"
              placeholder="User"
              class="w-full"
              :class="{ 'border-red-500': errors.name }"
            />
            <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Table Name *</label>
            <Input
              v-model="model.table"
              placeholder="users"
              class="w-full"
              :class="{ 'border-red-500': errors.table }"
            />
            <p v-if="errors.table" class="text-red-500 text-xs mt-1">{{ errors.table }}</p>
          </div>
        </div>
      </div>

      <!-- Fields Section -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Fields</h2>
          <Button @click="addField" size="sm">
            + Add Field
          </Button>
        </div>

        <div v-if="errors.fields" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
          {{ errors.fields }}
        </div>

        <div class="space-y-4">
          <FieldBuilder
            v-for="(field, index) in model.fillable"
            :key="index"
            :field="field"
            :field-types="fieldTypes"
            :index="index"
            @update:field="updateField(index, $event)"
            @remove="removeField(index)"
          />

          <div v-if="model.fillable.length === 0" class="text-center py-8 text-gray-500">
            <div class="text-4xl mb-2">📝</div>
            <p>No fields added yet. Click "Add Field" to get started.</p>
          </div>
        </div>
      </div>

      <!-- Relations Section -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Relations (Optional)</h2>
          <Button @click="addRelation" variant="outline" size="sm">
            + Add Relation
          </Button>
        </div>

        <div v-if="model.relations.length > 0" class="space-y-4">
          <div
            v-for="(relation, index) in model.relations"
            :key="index"
            class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg"
          >
            <div class="flex-1">
              <Input
                v-model="relation.name"
                placeholder="Related model name (e.g., user)"
                class="w-full"
              />
            </div>
            <div class="flex-1">
              <Input
                v-model="relation.foreignKey"
                placeholder="Foreign key (e.g., user_id)"
                class="w-full"
              />
            </div>
            <Button
              @click="removeRelation(index)"
              variant="destructive"
              size="sm"
            >
              Remove
            </Button>
          </div>
        </div>

        <div v-else class="text-center py-8 text-gray-500">
          <div class="text-4xl mb-2">🔗</div>
          <p>No relations defined. Add relations to link this model to others.</p>
        </div>
      </div>

      <!-- Casts Section -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Casts (Optional)</h2>
          <Button @click="addCast" variant="outline" size="sm">
            + Add Cast
          </Button>
        </div>

        <div v-if="model.casts.length > 0" class="space-y-4">
          <div
            v-for="(cast, index) in model.casts"
            :key="index"
            class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg"
          >
            <div class="flex-1">
              <Input
                v-model="cast.key"
                placeholder="Field name"
                class="w-full"
              />
            </div>
            <div class="flex-1">
              <select
                v-model="cast.type"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option
                  v-for="castType in castTypes"
                  :key="castType"
                  :value="castType"
                >
                  {{ castType }}
                </option>
              </select>
            </div>
            <Button
              @click="removeCast(index)"
              variant="destructive"
              size="sm"
            >
              Remove
            </Button>
          </div>
        </div>

        <div v-else class="text-center py-8 text-gray-500">
          <div class="text-4xl mb-2">🎭</div>
          <p>No casts defined. Add casts to automatically convert field types.</p>
        </div>
      </div>
    </div>
  </div>
</template>
