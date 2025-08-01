<script setup lang="ts">
interface Props {
  variant?: 'default' | 'secondary' | 'destructive' | 'outline'
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
})

const getVariantClasses = (variant: string) => {
  const variants = {
    default: 'bg-blue-500 text-white',
    secondary: 'bg-gray-100 text-gray-800',
    destructive: 'bg-red-500 text-white',
    outline: 'border border-gray-300 text-gray-700 bg-white'
  }
  return variants[variant as keyof typeof variants] || variants.default
}
</script>

<template>
  <span
    :class="[
      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors',
      getVariantClasses(props.variant),
      props.class
    ]"
  >
    <slot />
  </span>
</template>
