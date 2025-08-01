<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { SelectGroup, type SelectGroupProps, useForwardProps } from 'reka-ui'
import { cn } from '@/lib/utils'

interface Props extends SelectGroupProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<Props>()

const forwarded = useForwardProps(props)
</script>

<template>
  <SelectGroup
    data-slot="select-group"
    v-bind="forwarded"
    :class="cn('', props.class)"
  >
    <slot />
  </SelectGroup>
</template>
