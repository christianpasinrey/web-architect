<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { SelectLabel, type SelectLabelProps, useForwardProps } from 'reka-ui'
import { cn } from '@/lib/utils'

interface Props extends SelectLabelProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<Props>()

const forwarded = useForwardProps(props)
</script>

<template>
  <SelectLabel
    data-slot="select-label"
    v-bind="forwarded"
    :class="cn('px-2 py-1.5 text-sm font-semibold text-muted-foreground', props.class)"
  >
    <slot />
  </SelectLabel>
</template>
