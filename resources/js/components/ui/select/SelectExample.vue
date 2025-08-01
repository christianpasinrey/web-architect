<template>
  <div class="space-y-4">
    <!-- Select simple -->
    <div>
      <label class="text-sm font-medium">Select simple:</label>
      <Select v-model="singleValue">
        <SelectTrigger class="w-64">
          <SelectValue placeholder="Selecciona una opción" />
          <SelectIcon />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="option1">Opción 1</SelectItem>
          <SelectItem value="option2">Opción 2</SelectItem>
          <SelectItem value="option3">Opción 3</SelectItem>
        </SelectContent>
      </Select>
      <p class="text-xs text-muted-foreground mt-1">Valor: {{ singleValue }}</p>
    </div>

    <!-- Select múltiple -->
    <div>
      <label class="text-sm font-medium">Select múltiple:</label>
      <Select
        v-model="multipleValues"
        :multiple="true"
      >
        <SelectTrigger class="w-64">
          <SelectValue placeholder="Selecciona múltiples opciones" />
          <SelectIcon />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="apple">🍎 Manzana</SelectItem>
          <SelectItem value="banana">🍌 Plátano</SelectItem>
          <SelectItem value="orange">🍊 Naranja</SelectItem>
          <SelectItem value="grape">🍇 Uva</SelectItem>
          <SelectItem value="strawberry">🍓 Fresa</SelectItem>
        </SelectContent>
      </Select>
      <p class="text-xs text-muted-foreground mt-1">Valores: {{ multipleValues }}</p>
    </div>

    <!-- Select con grupos -->
    <div>
      <label class="text-sm font-medium">Select con grupos:</label>
      <Select v-model="groupValue">
        <SelectTrigger class="w-64">
          <SelectValue placeholder="Selecciona un framework" />
          <SelectIcon />
        </SelectTrigger>
        <SelectContent>
          <SelectGroup>
            <SelectLabel>Frontend</SelectLabel>
            <SelectItem value="vue">Vue.js</SelectItem>
            <SelectItem value="react">React</SelectItem>
            <SelectItem value="angular">Angular</SelectItem>
          </SelectGroup>
          <SelectSeparator />
          <SelectGroup>
            <SelectLabel>Backend</SelectLabel>
            <SelectItem value="laravel">Laravel</SelectItem>
            <SelectItem value="express">Express</SelectItem>
            <SelectItem value="django">Django</SelectItem>
          </SelectGroup>
        </SelectContent>
      </Select>
      <p class="text-xs text-muted-foreground mt-1">Valor: {{ groupValue }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectIcon,
  SelectContent,
  SelectItem,
  SelectGroup,
  SelectLabel,
  SelectSeparator,
} from './index'

const singleValue = ref('')
const multipleValues = ref([])
const groupValue = ref('')
</script>
