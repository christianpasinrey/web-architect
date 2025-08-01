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
            'absolute z-50 py-4 px-2 rounded-lg shadow-lg border max-w-md backdrop-blur-sm',
            'bg-white dark:bg-black/80 border-sidebar-border/70 dark:border-sidebar-border',
            showAbove ? 'bottom-full mb-2' : 'top-full mt-2'
        ]"
        :style="{
            left: '0',
            right: '0',
            boxShadow: 'var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)'
        }"
        @click.stop
    >
        <h4 class="text-sm font-medium mb-3" :style="{ color: 'rgb(55 65 81)' }">Additional Fields:</h4>
        <div class="space-y-3 scrollable max-h-64 overflow-y-auto px-2">
            <div
                v-for="field in fields"
                :key="field.id"
                class="flex items-center justify-between text-xs p-2 rounded border transition-colors hover-item"
            >
                <div class="flex items-center space-x-2 flex-1 min-w-0">
                    <span class="font-mono truncate dark:text-gray-300">{{ field.name }}</span>
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

.scrollable {
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
}

.scrollable::-webkit-scrollbar {
  width: 8px;
}

.scrollable::-webkit-scrollbar-track {
  background: transparent;
  border-radius: 10px;
}

.scrollable::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(4px);
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: background 0.3s ease;
}

.scrollable::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.4);
}
</style>
