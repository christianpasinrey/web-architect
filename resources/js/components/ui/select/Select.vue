<script setup lang="ts">
import { ref, computed } from 'vue'

interface Option {
  value: string | number
  label: string
}

interface Props {
  modelValue?: string | number | string[] | number[]
  options?: Option[]
  placeholder?: string
  multiple?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Selecciona una opción',
  multiple: false,
  disabled: false
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number | string[] | number[]]
}>()

const isOpen = ref(false)
const selectRef = ref<HTMLDivElement>()

const selectedLabel = computed(() => {
  if (props.multiple) {
    const values = Array.isArray(props.modelValue) ? props.modelValue : []
    if (values.length === 0) return props.placeholder
    if (values.length === 1) {
      const option = props.options?.find(opt => opt.value === values[0])
      return option?.label || values[0]
    }
    return `${values.length} seleccionados`
  } else {
    if (!props.modelValue) return props.placeholder
    const option = props.options?.find(opt => opt.value === props.modelValue)
    return option?.label || props.modelValue
  }
})

function toggleOpen() {
  if (props.disabled) return
  isOpen.value = !isOpen.value
}

function selectOption(value: string | number) {
  if (props.multiple) {
    const currentValues = Array.isArray(props.modelValue) ? [...props.modelValue] : []
    const index = currentValues.indexOf(value)
    if (index > -1) {
      currentValues.splice(index, 1)
    } else {
      currentValues.push(value)
    }
    emit('update:modelValue', currentValues)
  } else {
    emit('update:modelValue', value)
    isOpen.value = false
  }
}

function isSelected(value: string | number): boolean {
  if (props.multiple) {
    const values = Array.isArray(props.modelValue) ? props.modelValue : []
    return values.includes(value)
  }
  return props.modelValue === value
}

// Cerrar al hacer click fuera
function handleClickOutside(event: Event) {
  if (selectRef.value && !selectRef.value.contains(event.target as Node)) {
    isOpen.value = false
  }
}

// Agregar/quitar event listener
import { onMounted, onUnmounted } from 'vue'

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div ref="selectRef" class="relative w-full">
    <!-- Trigger -->
    <div
      @click="toggleOpen"
      class="flex h-9 w-full items-center justify-between whitespace-nowrap rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer"
      :class="{ 'ring-1 ring-ring': isOpen }"
    >
      <span class="truncate">{{ selectedLabel }}</span>
      <svg
        class="h-4 w-4 opacity-50 transition-transform"
        :class="{ 'rotate-180': isOpen }"
        xmlns="http://www.w3.org/2000/svg"
        width="24"
        height="24"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="m6 9 6 6 6-6"/>
      </svg>
    </div>

    <!-- Content -->
    <div
      v-if="isOpen"
      class="absolute top-full z-[999999999999] mt-1 w-full min-w-32 max-h-60 overflow-auto rounded-md border bg-white dark:bg-gray-900 p-1 shadow-lg"
    >
      <div
        v-for="option in options"
        :key="option.value"
        @click="selectOption(option.value)"
        class="relative flex w-full cursor-pointer select-none items-center rounded px-2 py-1.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
        :class="{ 'bg-gray-100 dark:bg-gray-800': isSelected(option.value) }"
      >
        <span v-if="multiple && isSelected(option.value)" class="mr-2">✓</span>
        {{ option.label }}
      </div>
      <div v-if="!options || options.length === 0" class="px-2 py-1.5 text-sm text-gray-500">
        No hay opciones
      </div>
    </div>
  </div>
</template>
