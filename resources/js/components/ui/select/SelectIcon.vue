<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { SelectIcon, type SelectIconProps, useForwardProps } from 'reka-ui'
import { cn } from '@/lib/utils'

interface Props extends SelectIconProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<Props>()

const forwarded = useForwardProps(props)
</script>

<template>
  <SelectIcon
    data-slot="select-icon"
    v-bind="forwarded"
    :class="cn('h-4 w-4 opacity-50', props.class)"
  >
    <slot>
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m6 9 6 6 6-6"/>
      </svg>
    </slot>
  </SelectIcon>
</template>
