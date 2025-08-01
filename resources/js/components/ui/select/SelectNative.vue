<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'

const props = defineProps<{
  defaultValue?: string | string[]
  modelValue?: string | string[]
  class?: HTMLAttributes['class']
  multiple?: boolean
  placeholder?: string
  disabled?: boolean
  required?: boolean
  name?: string
  id?: string
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | string[]): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <select
    v-model="modelValue"
    data-slot="select"
    :multiple="multiple"
    :disabled="disabled"
    :required="required"
    :name="name"
    :id="id"
    :class="cn(
      'placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
      'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
      'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
      multiple ? 'min-h-[80px] h-auto py-2' : 'h-9',
      props.class,
    )"
  >
    <option v-if="!multiple && placeholder" value="" disabled selected>{{ placeholder }}</option>
    <slot />
  </select>
</template>
