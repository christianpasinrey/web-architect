<script setup lang="ts">
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import type { FieldType } from '@/types/model';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    fieldTypes: FieldType[];
}>();

const emits = defineEmits<{
    (e: 'cancel'): void;
}>();

interface Field {
  name: string;
  type: string;
  label?: string;
  default?: string;
  nullable?: boolean;
  unique?: boolean;
  index?: boolean;
  primary?: boolean;
  auto_increment?: boolean;
  foreign?: boolean;
  foreign_table?: string;
  foreign_key?: string;
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
  description: '',
  fillable: [] as Field[],
  appends: [] as AttributeModifier[],
  casts: [] as { key: string; type: string }[],
  relations: [] as Relation[],
});

const newField = reactive<Field>({
  name: '',
  type: '',
  label: '',
  nullable: false,
  unique: false,
  index: false,
  primary: false,
  auto_increment: false,
  foreign: false
});
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
    // Generar label automáticamente si no se proporciona
    if (!newField.label) {
      newField.label = newField.name.split('_').map(word =>
        word.charAt(0).toUpperCase() + word.slice(1)
      ).join(' ');
    }

    model.fillable.push({ ...newField });

    // Reset
    newField.name = '';
    newField.type = '';
    newField.label = '';
    newField.nullable = false;
    newField.unique = false;
    newField.index = false;
    newField.primary = false;
    newField.auto_increment = false;
    newField.foreign = false;
    newField.default = undefined;
    newField.foreign_table = undefined;
    newField.foreign_key = undefined;
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
  <div class="max-w-4xl mx-auto p-8 mt-8 bg-card rounded-2xl shadow-lg border border-border">
    <h2 class="text-3xl font-bold mb-8 text-foreground tracking-tight">Crear nuevo modelo</h2>
    <form @submit.prevent="submit" class="space-y-8">
      <!-- Datos básicos -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <Label for="model-name">Nombre del modelo</Label>
          <Input
            id="model-name"
            v-model="model.name"
            placeholder="Ej: Booking"
          />
          <span v-if="errors.name" class="text-destructive text-xs">{{ errors.name }}</span>
        </div>
        <div class="space-y-2">
          <Label for="table-name">Nombre de la tabla</Label>
          <Input
            id="table-name"
            v-model="model.table"
            placeholder="Ej: bookings"
          />
          <span v-if="errors.table" class="text-destructive text-xs">{{ errors.table }}</span>
        </div>
      </div>

      <!-- Descripción del modelo -->
      <div class="space-y-2 mb-6">
        <Label for="model-description">Descripción del modelo (opcional)</Label>
        <Textarea
          id="model-description"
          v-model="model.description"
          placeholder="Describe brevemente el propósito de este modelo..."
          :rows="3"
          class="resize-none"
        />
      </div>

      <!-- Campos fillable -->
      <div class="bg-white/50 dark:bg-black/20 backdrop-blur-sm border border-border rounded-xl p-6 mb-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <Label class="text-base font-semibold">Campos <span class="text-xs text-muted-foreground">(fillable)</span></Label>
        </div>

        <!-- Formulario para agregar campos -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4 p-4">
          <div class="space-y-2">
            <Label class="text-xs">Nombre del campo</Label>
            <Input v-model="newField.name" placeholder="ej: user_id" />
          </div>
          <div class="space-y-2">
            <Label class="text-xs">Tipo</Label>
            <Select v-model="newField.type" :options="FIELD_TYPES" placeholder="Selecciona tipo" />
          </div>
          <div class="space-y-2">
            <Label class="text-xs">Label (opcional)</Label>
            <Input v-model="newField.label" placeholder="ej: User ID" />
          </div>

          <!-- Checkboxes para propiedades -->
          <div class="md:col-span-2 lg:col-span-3">
            <Label class="text-xs mb-2 block">Propiedades</Label>
            <div class="flex flex-wrap gap-4">
              <label class="flex items-center space-x-2 cursor-pointer">
                <input v-model="newField.nullable" type="checkbox" class="rounded border-border bg-input text-primary focus:ring-ring" />
                <span class="text-sm text-foreground">Nullable</span>
              </label>
              <label class="flex items-center space-x-2 cursor-pointer">
                <input v-model="newField.unique" type="checkbox" class="rounded border-border bg-input text-primary focus:ring-ring" />
                <span class="text-sm text-foreground">Unique</span>
              </label>
              <label class="flex items-center space-x-2 cursor-pointer">
                <input v-model="newField.index" type="checkbox" class="rounded border-border bg-input text-primary focus:ring-ring" />
                <span class="text-sm text-foreground">Index</span>
              </label>
              <label class="flex items-center space-x-2 cursor-pointer">
                <input v-model="newField.primary" type="checkbox" class="rounded border-border bg-input text-primary focus:ring-ring" />
                <span class="text-sm text-foreground">Primary</span>
              </label>
              <label class="flex items-center space-x-2 cursor-pointer">
                <input v-model="newField.auto_increment" type="checkbox" class="rounded border-border bg-input text-primary focus:ring-ring" />
                <span class="text-sm text-foreground">Auto Increment</span>
              </label>
              <label class="flex items-center space-x-2 cursor-pointer">
                <input v-model="newField.foreign" type="checkbox" class="rounded border-border bg-input text-primary focus:ring-ring" />
                <span class="text-sm text-foreground">Foreign Key</span>
              </label>
            </div>
          </div>

          <!-- Campos adicionales para FK -->
          <div v-if="newField.foreign" class="md:col-span-2 lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label class="text-xs">Tabla referenciada</Label>
              <Input v-model="newField.foreign_table" placeholder="ej: users" />
            </div>
            <div class="space-y-2">
              <Label class="text-xs">Campo referenciado</Label>
              <Input v-model="newField.foreign_key" placeholder="ej: id" />
            </div>
          </div>

          <!-- Campo default -->
          <div class="md:col-span-2 lg:col-span-3 space-y-2">
            <Label class="text-xs">Valor por defecto (opcional)</Label>
            <Input v-model="newField.default" placeholder="ej: null, 0, ''" />
          </div>

          <div class="md:col-span-2 lg:col-span-3 flex justify-end">
            <Button type="button" @click="addField">Agregar Campo</Button>
          </div>
        </div>

        <!-- Lista de campos -->
        <div class="space-y-2">
          <div v-for="(field, idx) in model.fillable" :key="idx" class="flex items-center justify-between p-3 bg-white/30 dark:bg-black/30 backdrop-blur-sm rounded-lg border border-border/50 group shadow-sm">
            <div class="flex-1">
              <div class="flex items-center space-x-3">
                <span class="font-mono text-sm font-medium text-foreground">{{ field.name }}</span>
                <span class="text-xs px-2 py-1 bg-primary/10 text-primary rounded">{{ field.type }}</span>
                <span class="text-sm text-muted-foreground">{{ field.label }}</span>
              </div>
              <div class="flex flex-wrap gap-1 mt-1">
                <span v-if="field.nullable" class="text-xs px-1 py-0.5 bg-muted/50 text-muted-foreground rounded">nullable</span>
                <span v-if="field.unique" class="text-xs px-1 py-0.5 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded">unique</span>
                <span v-if="field.index" class="text-xs px-1 py-0.5 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded">index</span>
                <span v-if="field.primary" class="text-xs px-1 py-0.5 bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 rounded">primary</span>
                <span v-if="field.auto_increment" class="text-xs px-1 py-0.5 bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300 rounded">auto_increment</span>
                <span v-if="field.foreign" class="text-xs px-1 py-0.5 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300 rounded">FK → {{ field.foreign_table }}.{{ field.foreign_key }}</span>
              </div>
            </div>
            <button type="button" class="text-destructive hover:text-destructive/80 opacity-0 group-hover:opacity-100 transition px-2 py-1" @click="removeField(idx)">✕</button>
          </div>
        </div>
        <span v-if="errors.fillable" class="text-destructive text-xs mt-2 block">{{ errors.fillable }}</span>
      </div>

      <!-- Modificadores de atributo -->
      <div class="bg-white/50 dark:bg-black/20 backdrop-blur-sm border border-border rounded-xl p-6 mb-6 shadow-sm">
        <Label class="text-base font-semibold mb-4 block">Modificadores de atributo (Appends)</Label>
        <div class="space-y-4">
          <div class="space-y-2">
            <Label class="text-xs">Nombre</Label>
            <Input v-model="newModifier.name" placeholder="Ej: full_name" />
          </div>
          <div class="space-y-2">
            <Label class="text-xs">Tipo</Label>
            <Select v-model="newModifier.type">
              <SelectTrigger class="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="type in MODIFIER_TYPES" :key="type.id" :value="type.id">
                  {{ type.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label class="text-xs">Campos (selecciona uno o más)</Label>
            <Select v-model="newModifier.fields" multiple>
              <SelectTrigger class="w-full min-h-[90px] max-h-40">
                <SelectValue placeholder="Selecciona campos" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="field in availableFields" :key="field.name" :value="field.name">
                  {{ field.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <!-- Inputs dinámicos para opciones extra -->
          <div v-for="opt in selectedModifierType?.options || []" :key="opt.key" class="space-y-2">
            <Label class="text-xs">{{ opt.label }}</Label>
            <Input
              v-model="newModifier.options[opt.key]"
              :type="opt.type"
              :placeholder="opt.optional ? '(opcional)' : ''"
            />
          </div>
          <!-- Selector de relación si el modificador lo requiere -->
          <div v-if="selectedModifierType?.relationRequired" class="space-y-2">
            <Label class="text-xs">Relación</Label>
            <Select v-model="newModifier.relation">
              <SelectTrigger class="w-full">
                <SelectValue placeholder="Selecciona relación" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="rel in model.relations" :key="rel.name" :value="rel.name">
                  {{ rel.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="flex justify-end mt-2">
            <Button type="button" @click="addModifier">Agregar modificador</Button>
          </div>
          <span v-if="errors.modifier" class="text-destructive text-xs mt-1 block">{{ errors.modifier }}</span>
        </div>
        <ul class="divide-y divide-border/50 mt-3">
          <li v-for="(mod, idx) in model.appends" :key="idx" class="flex items-center justify-between py-2 group">
            <span class="font-mono text-sm text-foreground">{{ mod.name }}
              <span class="text-muted-foreground">[{{ mod.type }}]</span>
              <span class="text-muted-foreground">({{ mod.fields.join(', ') }})</span>
              <span v-if="mod.type === 'concatenar' && mod.options && mod.options.separator" class="text-muted-foreground"> sep: '{{ mod.options.separator }}'</span>
              <span v-if="mod.type === 'formatear_fecha' && mod.options && mod.options.format" class="text-muted-foreground"> formato: '{{ mod.options.format }}'</span>
            </span>
            <Button variant="destructive" size="sm" type="button" class="opacity-0 group-hover:opacity-100 transition" @click="removeModifier(idx)">Eliminar</Button>
          </li>
        </ul>
      </div>

      <!-- Casts -->
      <div class="bg-white/50 dark:bg-black/20 backdrop-blur-sm border border-border rounded-xl p-6 mb-6 shadow-sm">
        <Label class="text-base font-semibold mb-4 block">Casts</Label>
        <div class="flex flex-col md:flex-row gap-2 mb-3">
          <Input v-model="newCast.key" placeholder="Campo" class="w-full md:w-1/2" />
          <Input v-model="newCast.type" placeholder="Tipo (date, array, etc)" class="w-full md:w-1/2" />
          <Button type="button" @click="addCast">Agregar</Button>
        </div>
        <ul class="divide-y divide-border/50">
          <li v-for="(cast, idx) in model.casts" :key="idx" class="flex items-center justify-between py-2 group">
            <span class="font-mono text-sm text-foreground">{{ cast.key }}: <span class="text-muted-foreground">{{ cast.type }}</span></span>
            <Button variant="destructive" size="sm" type="button" class="opacity-0 group-hover:opacity-100 transition" @click="removeCast(idx)">Eliminar</Button>
          </li>
        </ul>
      </div>

      <!-- Relaciones -->
      <div class="bg-white/50 dark:bg-black/20 backdrop-blur-sm border border-border rounded-xl p-6 mb-6 shadow-sm">
        <Label class="text-base font-semibold mb-4 block">Relaciones</Label>
        <div class="flex flex-col md:flex-row gap-2 mb-3 items-end">
          <Input v-model="newRelation.name" placeholder="Nombre de la relación (ej: user)" class="w-full md:w-1/2" />
          <Select v-model="newRelation.foreignKey" class="w-full md:w-1/2">
            <SelectTrigger>
              <SelectValue placeholder="Selecciona campo clave foránea" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="field in model.fillable" :key="field.name" :value="field.name">
                {{ field.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <Button type="button" @click="addRelation">Agregar</Button>
        </div>
        <ul class="divide-y divide-border/50">
          <li v-for="(relation, idx) in model.relations" :key="idx" class="flex items-center justify-between py-2 group">
            <span class="font-mono text-sm text-foreground">{{ relation.name }} <span class="text-muted-foreground">({{ relation.foreignKey }})</span></span>
            <Button variant="destructive" size="sm" type="button" class="opacity-0 group-hover:opacity-100 transition" @click="removeRelation(idx)">Eliminar</Button>
          </li>
        </ul>
      </div>

      <div class="flex justify-end gap-4 mt-8">
        <Button
          type="button"
          variant="outline"
          @click="emits('cancel')"
        >
          Cancelar
        </Button>
        <Button
          type="submit"
          class="px-8 py-3 text-lg font-semibold shadow-md"
          :disabled="loading"
        >
          {{ loading ? 'Creando...' : 'Crear modelo' }}
        </Button>
      </div>
      <div v-if="success" class="text-green-600 dark:text-green-400 font-semibold text-center mt-4">{{ success }}</div>
    </form>
  </div>
</template>
