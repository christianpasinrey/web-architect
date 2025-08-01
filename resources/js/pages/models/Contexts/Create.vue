<script setup lang="ts">
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import type { FieldType } from '@/types/model';

const props = defineProps<{
    fieldTypes: FieldType[];
}>();

const emits = defineEmits<{
    (e: 'cancel'): void;
}>();

interface Field {
  name: string;
  type: string;
  nullable?: boolean;
  unique?: boolean;
  index?: boolean;
  default?: string;
}

type ModifierType = 'concatenar' | 'formatear_fecha' | 'sumar' | 'restar' | 'multiplicar' | 'dividir';

interface AttributeModifier {
  name: string;
  fields: string[];
  type: ModifierType;
  options?: Record<string, any>;
}

interface Relation {
  name: string;
  foreignKey: string;
}



// Catálogo de modificadores
const MODIFIER_TYPES = [
  {
    id: 'concatenar',
    label: 'Concatenar',
    inputTypes: ['string'],
    minFields: 2,
    options: [
      { key: 'separator', label: 'Separador', type: 'string', optional: true }
    ]
  },
  {
    id: 'formatear_fecha',
    label: 'Formatear fecha',
    inputTypes: ['date', 'datetime'],
    minFields: 1,
    options: [
      { key: 'format', label: 'Formato', type: 'string', optional: false }
    ]
  },
  {
    id: 'sumar',
    label: 'Sumar',
    inputTypes: ['int', 'float', 'bigint', 'decimal'],
    minFields: 2,
    options: []
  },
  {
    id: 'operacion_matematica',
    label: 'Operación matemática',
    inputTypes: ['int', 'float', 'bigint', 'decimal'],
    minFields: 1,
    options: [
      { key: 'formula', label: 'Fórmula', type: 'string', optional: false }
    ]
  },
  {
    id: 'sumar_dias_relacion',
    label: 'Sumar días de relación a fecha',
    inputTypes: ['date'],
    minFields: 1,
    relationRequired: true,
    relationFieldType: 'int',
    options: []
  }
];


// Tipos de campo soportados por Laravel y el backend
const FIELD_TYPES = computed(() => {
  return props.fieldTypes.map(type => ({
    value: type.column_type,
    label: `${type.label} (${type.column_type})`
  }));
});

const model = reactive({
  name: '',
  table: '',
  fillable: [] as Field[],
  appends: [] as AttributeModifier[],
  casts: [] as { key: string; type: string }[],
  relations: [] as Relation[],
});

const newField = reactive<Field>({ name: '', type: '' });
const newModifier = reactive<any>({
  name: '',
  fields: [],
  type: MODIFIER_TYPES[0].id,
  options: {},
  relation: undefined,
});
const newCast = reactive<{ key: string; type: string }>({ key: '', type: '' });
const newRelation = reactive<Relation>({ name: '', foreignKey: '' });

const errors = ref<Record<string, string>>({});
const loading = ref(false);
const success = ref('');

import { computed } from 'vue';

const selectedModifierType = computed(() =>
  MODIFIER_TYPES.find(t => t.id === newModifier.type)
);

const availableFields = computed(() => {
  if (!selectedModifierType.value) return [];
  return model.fillable.filter(f =>
    selectedModifierType.value.inputTypes.includes(f.type)
  );
});

function addField() {
  if (newField.name && newField.type) {
    model.fillable.push({ ...newField });
    newField.name = '';
    newField.type = '';
  }
}
function removeField(idx: number) {
  model.fillable.splice(idx, 1);
}


function addModifier() {
  errors.value.modifier = '';
  const modType = selectedModifierType.value;
  if (!modType) {
    errors.value.modifier = 'Selecciona un tipo de modificador válido';
    return;
  }
  if (newModifier.fields.length < (modType.minFields || 1)) {
    errors.value.modifier = `Selecciona al menos ${modType.minFields} campo(s)`;
    return;
  }
  // Validar tipos de campo
  const selectedFields = model.fillable.filter(f => newModifier.fields.includes(f.name));
  if (!selectedFields.every(f => modType.inputTypes.includes(f.type))) {
    errors.value.modifier = 'Los campos seleccionados no son compatibles con este modificador';
    return;
  }
  // Validar opciones obligatorias
  for (const opt of modType.options || []) {
    if (!opt.optional && !newModifier.options[opt.key]) {
      errors.value.modifier = `El campo "${opt.label}" es obligatorio`;
      return;
    }
  }
  // Validar relación si aplica
  if (modType.relationRequired && !newModifier.relation) {
    errors.value.modifier = 'Selecciona una relación';
    return;
  }
  // Agregar el modificador
  model.appends.push({
    name: newModifier.name,
    fields: [...newModifier.fields],
    type: newModifier.type,
    options: { ...newModifier.options } || {},
    relation: newModifier.relation || undefined
  });
  // Reset
  newModifier.name = '';
  newModifier.fields = [];
  newModifier.type = MODIFIER_TYPES[0].id;
  newModifier.options = {};
  newModifier.relation = undefined;
}
function removeModifier(idx: number) {
  model.appends.splice(idx, 1);
}
function addCast() {
  if (newCast.key && newCast.type) {
    model.casts.push({ ...newCast });
    newCast.key = '';
    newCast.type = '';
  }
}
function removeCast(idx: number) {
  model.casts.splice(idx, 1);
}

function addRelation() {
  if (newRelation.name && newRelation.foreignKey) {
    model.relations.push({ name: newRelation.name, foreignKey: newRelation.foreignKey });
    newRelation.name = '';
    newRelation.foreignKey = '';
  }
}
function removeRelation(idx: number) {
  model.relations.splice(idx, 1);
}

function validate() {
  errors.value = {};
  if (!model.name) errors.value.name = 'El nombre es obligatorio';
  if (!model.table) errors.value.table = 'La tabla es obligatoria';
  if (!model.fillable.length) errors.value.fillable = 'Debe agregar al menos un campo';
  return Object.keys(errors.value).length === 0;
}

function submit() {
  if (!validate()) return;
  loading.value = true;
  router.post('/models', model, {
    onSuccess: () => {
      success.value = 'Modelo creado correctamente';
      loading.value = false;
      // Redirigir de vuelta al índice después de un breve delay
      setTimeout(() => {
        emits('cancel');
      }, 1500);
    },
    onError: (err: any) => {
      errors.value = err;
      loading.value = false;
    },
  });
}
</script>
<template>
  <div class="max-w-2xl mx-auto p-8 bg-white rounded-2xl shadow-lg border border-gray-100">
    <h2 class="text-3xl font-bold mb-8 text-gray-800 tracking-tight">Crear nuevo modelo</h2>
    <form @submit.prevent="submit" class="space-y-8">
      <!-- Datos básicos -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-gray-700 font-semibold mb-1">Nombre del modelo</label>
          <input v-model="model.name" class="w-full rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 px-4 py-2 transition" placeholder="Ej: Booking" />
          <span v-if="errors.name" class="text-red-500 text-xs mt-1 block">{{ errors.name }}</span>
        </div>
        <div>
          <label class="block text-gray-700 font-semibold mb-1">Nombre de la tabla</label>
          <input v-model="model.table" class="w-full rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 px-4 py-2 transition" placeholder="Ej: bookings" />
          <span v-if="errors.table" class="text-red-500 text-xs mt-1 block">{{ errors.table }}</span>
        </div>
      </div>

      <!-- Campos fillable -->
      <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-2">
        <div class="flex items-center justify-between mb-2">
          <label class="block text-gray-700 font-semibold">Campos <span class="text-xs text-gray-400">(fillable)</span></label>
        </div>
        <div class="flex flex-col md:flex-row gap-2 mb-3">
          <input v-model="newField.name" class="rounded-lg border border-gray-300 px-3 py-1 focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition w-full md:w-1/2" placeholder="Nombre" />
          <select v-model="newField.type" class="rounded-lg border border-gray-300 px-3 py-1 focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition w-full md:w-1/2">
            <option value="" disabled>Selecciona tipo</option>
            <option v-for="type in FIELD_TYPES" :key="type.value" :value="type.value">{{ type.label }}</option>
          </select>
          <button type="button" class="btn btn-primary px-4 py-1 rounded-lg shadow-sm hover:bg-primary-600 transition" @click="addField">Agregar</button>
        </div>
        <ul class="divide-y divide-gray-200">
          <li v-for="(field, idx) in model.fillable" :key="idx" class="flex items-center justify-between py-1 group">
            <span class="font-mono text-sm">{{ field.name }} <span class="text-gray-400">({{ field.type }})</span></span>
            <button type="button" class="btn btn-xs btn-error opacity-0 group-hover:opacity-100 transition" @click="removeField(idx)">Eliminar</button>
          </li>
        </ul>
        <span v-if="errors.fillable" class="text-red-500 text-xs mt-1 block">{{ errors.fillable }}</span>
      </div>

      <!-- Modificadores de atributo -->
      <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-2">
        <label class="block text-gray-700 font-semibold mb-3">Modificadores de atributo</label>
        <div class="flex flex-col gap-3">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">Nombre</label>
            <input v-model="newModifier.name" class="rounded-lg border border-gray-300 px-3 py-1 w-full focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition" placeholder="Ej: full_name" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">Tipo</label>
            <select v-model="newModifier.type" class="rounded-lg border border-gray-300 px-3 py-1 w-full focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition">
              <option v-for="type in MODIFIER_TYPES" :key="type.id" :value="type.id">{{ type.label }}</option>
            </select>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">Campos (selecciona uno o más)</label>
            <select v-model="newModifier.fields" multiple class="rounded-lg border border-gray-300 px-3 py-2 w-full min-h-[90px] max-h-40 focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition" size="4">
              <option v-for="field in availableFields" :key="field.name" :value="field.name">{{ field.name }}</option>
            </select>
          </div>
          <!-- Inputs dinámicos para opciones extra -->
          <div v-for="opt in selectedModifierType?.options || []" :key="opt.key">
            <label class="text-xs text-gray-500 mb-1 block">{{ opt.label }}</label>
            <input
              v-model="newModifier.options[opt.key]"
              :type="opt.type"
              class="rounded-lg border border-gray-300 px-3 py-1 w-full focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition"
              :placeholder="opt.optional ? '(opcional)' : ''"
            />
          </div>
          <!-- Selector de relación si el modificador lo requiere -->
          <div v-if="selectedModifierType?.relationRequired">
            <label class="text-xs text-gray-500 mb-1 block">Relación</label>
            <select v-model="newModifier.relation" class="rounded-lg border border-gray-300 px-3 py-1 w-full focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition">
              <option value="" disabled>Selecciona relación</option>
              <option v-for="rel in model.relations" :key="rel.name" :value="rel.name">{{ rel.name }}</option>
            </select>
          </div>
          <div class="flex justify-end mt-2">
            <button type="button" class="btn btn-primary px-4 py-1 rounded-lg shadow-sm hover:bg-primary-600 transition" @click="addModifier">Agregar modificador</button>
          </div>
          <span v-if="errors.modifier" class="text-red-500 text-xs mt-1 block">{{ errors.modifier }}</span>
        </div>
        <ul class="divide-y divide-gray-200 mt-3">
          <li v-for="(mod, idx) in model.appends" :key="idx" class="flex items-center justify-between py-1 group">
            <span class="font-mono text-sm">{{ mod.name }}
              <span class="text-gray-400">[{{ mod.type }}]</span>
              <span class="text-gray-500">({{ mod.fields.join(', ') }})</span>
              <span v-if="mod.type === 'concatenar' && mod.options && mod.options.separator"> sep: '{{ mod.options.separator }}'</span>
              <span v-if="mod.type === 'formatear_fecha' && mod.options && mod.options.format"> formato: '{{ mod.options.format }}'</span>
            </span>
            <button type="button" class="btn btn-xs btn-error opacity-0 group-hover:opacity-100 transition" @click="removeModifier(idx)">Eliminar</button>
          </li>
        </ul>
      </div>

      <!-- Casts -->
      <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-2">
        <label class="block text-gray-700 font-semibold mb-2">Casts</label>
        <div class="flex flex-col md:flex-row gap-2 mb-3">
          <input v-model="newCast.key" class="rounded-lg border border-gray-300 px-3 py-1 focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition w-full md:w-1/2" placeholder="Campo" />
          <input v-model="newCast.type" class="rounded-lg border border-gray-300 px-3 py-1 focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition w-full md:w-1/2" placeholder="Tipo (date, array, etc)" />
          <button type="button" class="btn btn-primary px-4 py-1 rounded-lg shadow-sm hover:bg-primary-600 transition" @click="addCast">Agregar</button>
        </div>
        <ul class="divide-y divide-gray-200">
          <li v-for="(cast, idx) in model.casts" :key="idx" class="flex items-center justify-between py-1 group">
            <span class="font-mono text-sm">{{ cast.key }}: <span class="text-gray-500">{{ cast.type }}</span></span>
            <button type="button" class="btn btn-xs btn-error opacity-0 group-hover:opacity-100 transition" @click="removeCast(idx)">Eliminar</button>
          </li>
        </ul>
      </div>

      <!-- Relaciones -->
      <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-2">
        <label class="block text-gray-700 font-semibold mb-2">Relaciones</label>
        <div class="flex flex-col md:flex-row gap-2 mb-3 items-end">
          <input v-model="newRelation.name" class="rounded-lg border border-gray-300 px-3 py-1 focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition w-full md:w-1/2" placeholder="Nombre de la relación (ej: user)" />
          <select v-model="newRelation.foreignKey" class="rounded-lg border border-gray-300 px-3 py-1 focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition w-full md:w-1/2">
            <option value="" disabled>Selecciona campo clave foránea</option>
            <option v-for="field in model.fillable" :key="field.name" :value="field.name">{{ field.name }}</option>
          </select>
          <button type="button" class="btn btn-primary px-4 py-1 rounded-lg shadow-sm hover:bg-primary-600 transition" @click="addRelation">Agregar</button>
        </div>
        <ul class="divide-y divide-gray-200">
          <li v-for="(relation, idx) in model.relations" :key="idx" class="flex items-center justify-between py-1 group">
            <span class="font-mono text-sm">{{ relation.name }} <span class="text-gray-400">({{ relation.foreignKey }})</span></span>
            <button type="button" class="btn btn-xs btn-error opacity-0 group-hover:opacity-100 transition" @click="removeRelation(idx)">Eliminar</button>
          </li>
        </ul>
      </div>

      <div class="flex justify-end gap-2 mt-6">
        <button
          type="button"
          @click="emits('cancel')"
          class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
        >
          Cancelar
        </button>
        <button
          type="submit"
          class="btn btn-primary px-8 py-2 rounded-lg text-lg font-semibold shadow-md hover:bg-primary-600 transition"
          :disabled="loading"
        >
          Crear modelo
        </button>
      </div>
      <div v-if="success" class="text-green-600 font-semibold text-center mt-4">{{ success }}</div>
    </form>
  </div>
</template>
