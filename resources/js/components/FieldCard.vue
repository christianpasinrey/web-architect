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
    string: 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300',
    text: 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300',
    integer: 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
    bigInteger: 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
    float: 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300',
    decimal: 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300',
    boolean: 'bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300',
    date: 'bg-pink-100 dark:bg-pink-900/50 text-pink-800 dark:text-pink-300',
    dateTime: 'bg-pink-100 dark:bg-pink-900/50 text-pink-800 dark:text-pink-300',
    timestamp: 'bg-pink-100 dark:bg-pink-900/50 text-pink-800 dark:text-pink-300',
    json: 'bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-300',
    enum: 'bg-orange-100 dark:bg-orange-900/50 text-orange-800 dark:text-orange-300',
  };
  return colorMap[type] || 'bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-300';
};
</script>

<template>
  <div
    :class="[
      'bg-white dark:bg-black/80 border border-sidebar-border/70 dark:border-sidebar-border rounded-lg p-4 hover:shadow-md transition-shadow',
      props.class
    ]"
  >
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center space-x-2">
        <span class="text-lg">{{ getFieldIcon(field.field_type.column_type) }}</span>
        <h4 class="font-semibold text-gray-900 dark:text-white">{{ field.name }}</h4>
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
      <p class="text-sm text-gray-600 dark:text-gray-400">
        <span class="font-medium">Label:</span> {{ field.label }}
      </p>

      <div class="flex flex-wrap gap-1">
        <span v-if="field.nullable" class="px-2 py-1 bg-gray-200 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 rounded text-xs">
          Nullable
        </span>
        <span v-if="field.unique" class="px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded text-xs">
          Unique
        </span>
        <span v-if="field.index" class="px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded text-xs">
          Indexed
        </span>
        <span v-if="field.primary" class="px-2 py-1 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded text-xs">
          Primary
        </span>
        <span v-if="field.auto_increment" class="px-2 py-1 bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300 rounded text-xs">
          Auto Increment
        </span>
        <span v-if="field.foreign" class="px-2 py-1 bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 rounded text-xs">
          Foreign Key
        </span>
      </div>

      <div v-if="field.default" class="text-sm text-gray-600 dark:text-gray-300">
        <span class="font-medium">Default:</span>
        <code class="bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-1 rounded text-xs">{{ field.default }}</code>
      </div>

      <div v-if="field.foreign && field.foreign_table" class="text-sm text-gray-600 dark:text-gray-300">
        <span class="font-medium">References:</span>
        {{ field.foreign_table }}.{{ field.foreign_key }}
      </div>
    </div>
  </div>
</template>
