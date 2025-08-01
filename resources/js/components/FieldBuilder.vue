<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import type { FieldType } from '@/types/model';

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

interface Props {
  field: FieldData;
  fieldTypes: FieldType[];
  index: number;
}

const props = defineProps<Props>();

const emits = defineEmits<{
  (e: 'update:field', field: FieldData): void;
  (e: 'remove'): void;
}>();

const fieldData = ref<FieldData>({ ...props.field });

// Watch for external changes
watch(() => props.field, (newField: any) => {
  fieldData.value = { ...newField };
}, { deep: true });

// Emit changes when field data changes
watch(fieldData, (newField: any) => {
  emits('update:field', { ...newField });
}, { deep: true });

const selectedFieldType = computed(() => {
  return props.fieldTypes.find((ft: any) => ft.column_type === fieldData.value.type);
});

const isNumericType = computed(() => {
  const numericTypes = ['integer', 'bigInteger', 'float', 'double', 'decimal'];
  return numericTypes.includes(fieldData.value.type);
});

const updateLabel = () => {
  if (!fieldData.value.label && fieldData.value.name) {
    fieldData.value.label = fieldData.value.name
      .replace(/_/g, ' ')
      .replace(/([A-Z])/g, ' $1')
      .toLowerCase()
      .replace(/^\w/, (c: any) => c.toUpperCase())
      .trim();
  }
};

const getFieldIcon = (type: string) => {
  const iconMap: Record<string, string> = {
    string: '📝',
    text: '📄',
    integer: '🔢',
    bigInteger: '🔢',
    float: '💯',
    decimal: '💰',
    boolean: '✅',
    date: '📅',
    dateTime: '🕐',
    timestamp: '⏰',
    json: '🗃️',
    enum: '📋',
  };
  return iconMap[type] || '📋';
};
</script>

<template>
  <div class="bg-white border border-gray-200 rounded-lg p-4 space-y-4 relative">
    <!-- Remove Button -->
    <button
      @click="emits('remove')"
      class="absolute top-2 right-2 px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
    >
      ×
    </button>

    <!-- Field Header -->
    <div class="flex items-center space-x-2 mb-4">
      <span class="text-lg">{{ selectedFieldType ? getFieldIcon(selectedFieldType.column_type) : '📋' }}</span>
      <h4 class="font-medium text-gray-900">Field {{ index + 1 }}</h4>
    </div>

    <!-- Basic Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
        <input
          v-model="fieldData.name"
          @blur="updateLabel"
          placeholder="field_name"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
        <select
          v-model="fieldData.type"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">Select type...</option>
          <optgroup
            v-for="category in [
              { label: 'Text', types: fieldTypes.filter(ft => ['string', 'varchar', 'char', 'text', 'mediumText', 'longText', 'tinyText'].includes(ft.column_type)) },
              { label: 'Numeric', types: fieldTypes.filter(ft => ['integer', 'bigInteger', 'smallInteger', 'tinyInteger', 'float', 'double', 'decimal'].includes(ft.column_type)) },
              { label: 'Date/Time', types: fieldTypes.filter(ft => ['date', 'dateTime', 'timestamp', 'time'].includes(ft.column_type)) },
              { label: 'Other', types: fieldTypes.filter(ft => !['string', 'varchar', 'char', 'text', 'mediumText', 'longText', 'tinyText', 'integer', 'bigInteger', 'smallInteger', 'tinyInteger', 'float', 'double', 'decimal', 'date', 'dateTime', 'timestamp', 'time'].includes(ft.column_type)) }
            ]"
            :key="category.label"
            :label="category.label"
          >
            <option
              v-for="fieldType in category.types"
              :key="fieldType.id"
              :value="fieldType.column_type"
            >
              {{ fieldType.label }}
            </option>
          </optgroup>
        </select>
      </div>
    </div>

    <!-- Label -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
      <input
        v-model="fieldData.label"
        placeholder="Human readable label"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
      />
    </div>

    <!-- Modifiers Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
      <label class="flex items-center space-x-2 cursor-pointer">
        <input
          v-model="fieldData.nullable"
          type="checkbox"
          class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
        />
        <span class="text-sm text-gray-700">Nullable</span>
      </label>

      <label class="flex items-center space-x-2 cursor-pointer">
        <input
          v-model="fieldData.unique"
          type="checkbox"
          class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
        />
        <span class="text-sm text-gray-700">Unique</span>
      </label>

      <label class="flex items-center space-x-2 cursor-pointer">
        <input
          v-model="fieldData.index"
          type="checkbox"
          class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
        />
        <span class="text-sm text-gray-700">Index</span>
      </label>

      <label class="flex items-center space-x-2 cursor-pointer">
        <input
          v-model="fieldData.primary"
          type="checkbox"
          class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
        />
        <span class="text-sm text-gray-700">Primary</span>
      </label>

      <label v-if="isNumericType" class="flex items-center space-x-2 cursor-pointer">
        <input
          v-model="fieldData.auto_increment"
          type="checkbox"
          class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
        />
        <span class="text-sm text-gray-700">Auto Increment</span>
      </label>

      <label class="flex items-center space-x-2 cursor-pointer">
        <input
          v-model="fieldData.foreign"
          type="checkbox"
          class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
        />
        <span class="text-sm text-gray-700">Foreign Key</span>
      </label>
    </div>

    <!-- Default Value -->
    <div v-if="!fieldData.auto_increment">
      <label class="block text-sm font-medium text-gray-700 mb-1">Default Value</label>
      <input
        v-model="fieldData.default"
        placeholder="Default value (optional)"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
      />
    </div>

    <!-- Foreign Key Configuration -->
    <div v-if="fieldData.foreign" class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-3 rounded-lg">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Foreign Table</label>
        <input
          v-model="fieldData.foreign_table"
          placeholder="users"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Foreign Key</label>
        <input
          v-model="fieldData.foreign_key"
          placeholder="id"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
      </div>
    </div>
  </div>
</template>
