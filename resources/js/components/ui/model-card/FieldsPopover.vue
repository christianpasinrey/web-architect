<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
import type { Field } from '@/types/model';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    fields: Field[];
}>();

const popoverElement = ref<HTMLElement>();
const showAbove = ref(false);

const calculatePosition = async () => {
    await nextTick();
    if (!popoverElement.value) return;

    const popover = popoverElement.value;
    const rect = popover.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;

    // Verificar si hay espacio suficiente abajo
    const spaceBelow = viewportHeight - rect.bottom;
    const spaceAbove = rect.top;

    // Si no hay suficiente espacio abajo, mostrar arriba
    if (spaceBelow < 50 && spaceAbove > spaceBelow) {
        showAbove.value = true;
    }

    // Ajustar posición horizontal si se sale del viewport
    const spaceRight = viewportWidth - rect.right;
    if (spaceRight < 0) {
        popover.style.right = '0';
        popover.style.left = 'auto';
    }
};

onMounted(() => {
    calculatePosition();
});
</script>

<template>
    <div
        ref="popoverElement"
        :class="[
            'absolute z-50 p-4 rounded-lg shadow-lg border max-w-md backdrop-blur-sm',
            showAbove ? 'bottom-full mb-2' : 'top-full mt-2'
        ]"
        :style="{
            left: '0',
            right: '0',
            backgroundColor: 'white',
            borderColor: 'rgb(229 231 235)',
            '--tw-shadow': '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
            '--tw-shadow-colored': '0 10px 15px -3px var(--tw-shadow-color), 0 4px 6px -4px var(--tw-shadow-color)',
            boxShadow: 'var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)'
        }"
        @click.stop
    >
        <h4 class="text-sm font-medium mb-3" :style="{ color: 'rgb(55 65 81)' }">Additional Fields:</h4>
        <div class="space-y-3 max-h-64 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
            <div
                v-for="field in fields"
                :key="field.id"
                class="flex items-center justify-between text-xs p-2 rounded border transition-colors hover-item"
                :style="{
                    backgroundColor: 'rgb(249 250 251)',
                    borderColor: 'rgb(229 231 235)'
                }"
            >
                <div class="flex items-center space-x-2 flex-1 min-w-0">
                    <span class="font-mono truncate" :style="{ color: 'rgb(17 24 39)' }">{{ field.name }}</span>
                    <div class="flex items-center space-x-1 flex-shrink-0">
                        <Badge v-if="!!field.nullable" variant="secondary" class="text-[10px] px-1 py-0">
                            nullable
                        </Badge>
                        <Badge v-if="!!field.unique" variant="default" class="text-[10px] px-1 py-0">
                            unique
                        </Badge>
                        <Badge v-if="!!field.foreign" variant="outline" class="text-[10px] px-1 py-0">
                            FK
                        </Badge>
                        <Badge v-if="!!field.primary" variant="destructive" class="text-[10px] px-1 py-0">
                            PK
                        </Badge>
                    </div>
                </div>
                <span class="font-mono text-[10px] ml-2 flex-shrink-0" :style="{ color: 'rgb(156 163 175)' }">
                    {{ field.field_type.column_type }}
                </span>
            </div>
        </div>

        <div v-if="fields.length === 0" class="text-xs text-center py-2" :style="{ color: 'rgb(107 114 128)' }">
            No additional fields
        </div>
    </div>
</template>

<style scoped>
.hover-item:hover {
    background-color: rgb(243 244 246) !important;
}
</style>
