<script setup lang="ts">
import type { Field } from '@/types/model';

interface Props {
  field: Field;
  class?: string;
}

const props = defineProps<Props>();

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

const getTypeColor = (type: string) => {
  const colorMap: Record<string, string> = {
    string: 'bg-blue-100 text-blue-800',
    text: 'bg-indigo-100 text-indigo-800',
    integer: 'bg-green-100 text-green-800',
    bigInteger: 'bg-green-100 text-green-800',
    float: 'bg-yellow-100 text-yellow-800',
    decimal: 'bg-yellow-100 text-yellow-800',
    boolean: 'bg-purple-100 text-purple-800',
    date: 'bg-pink-100 text-pink-800',
    dateTime: 'bg-pink-100 text-pink-800',
    timestamp: 'bg-pink-100 text-pink-800',
    json: 'bg-gray-100 text-gray-800',
    enum: 'bg-orange-100 text-orange-800',
  };
  return colorMap[type] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
  <div
    :class="[
      'bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow',
      props.class
    ]"
  >
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center space-x-2">
        <span class="text-lg">{{ getFieldIcon(field.field_type.column_type) }}</span>
        <h4 class="font-semibold text-gray-900">{{ field.name }}</h4>
      </div>
      <span
        :class="[
          'px-2 py-1 rounded-full text-xs font-medium',
          getTypeColor(field.field_type.column_type)
        ]"
      >
        {{ field.field_type.label }}
      </span>
    </div>

    <div class="space-y-2">
      <p class="text-sm text-gray-600">
        <span class="font-medium">Label:</span> {{ field.label }}
      </p>

      <div class="flex flex-wrap gap-1">
        <span v-if="field.nullable" class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
          Nullable
        </span>
        <span v-if="field.unique" class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">
          Unique
        </span>
        <span v-if="field.index" class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">
          Indexed
        </span>
        <span v-if="field.primary" class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs">
          Primary
        </span>
        <span v-if="field.auto_increment" class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs">
          Auto Increment
        </span>
        <span v-if="field.foreign" class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">
          Foreign Key
        </span>
      </div>

      <div v-if="field.default" class="text-sm text-gray-600">
        <span class="font-medium">Default:</span>
        <code class="bg-gray-100 px-1 rounded text-xs">{{ field.default }}</code>
      </div>

      <div v-if="field.foreign && field.foreign_table" class="text-sm text-gray-600">
        <span class="font-medium">References:</span>
        {{ field.foreign_table }}.{{ field.foreign_key }}
      </div>
    </div>
  </div>
</template>
