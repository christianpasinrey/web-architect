<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { SelectItem, SelectItemText, type SelectItemProps, useForwardProps } from 'reka-ui'

const props = withDefaults(defineProps<SelectItemProps & {
  class?: HTMLAttributes['class']
}>(), {})

const forwardedProps = useForwardProps(props)
</script>

<template>
  <SelectItem
    v-bind="forwardedProps"
    class="relative flex w-full cursor-pointer select-none items-center rounded px-2 py-1.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
  >
    <SelectItemText>
      <slot />
    </SelectItemText>
  </SelectItem>
</template>
