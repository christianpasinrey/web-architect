<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { SelectValue, type SelectValueProps, useForwardProps } from 'reka-ui'
import { cn } from '@/lib/utils'

interface Props extends SelectValueProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<Props>()

const forwarded = useForwardProps(props)
</script>

<template>
  <SelectValue
    data-slot="select-value"
    v-bind="forwarded"
    :class="cn('text-sm pointer-events-none', props.class)"
  >
    <slot />
  </SelectValue>
</template>
