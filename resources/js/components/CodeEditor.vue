<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import hljs from 'highlight.js';
import 'highlight.js/styles/github-dark.css';

interface Props {
  code?: string;
  language?: 'php' | 'javascript' | 'typescript' | 'json';
  readonly?: boolean;
  class?: string;
}

const props = withDefaults(defineProps<Props>(), {
  language: 'php',
  readonly: true,
});

const codeContainer = ref<HTMLElement>();
const detectedLanguage = ref<string>('');

// Auto-detectar el tipo de contenido
const detectLanguage = (code: string): string => {
  const trimmed = code.trim();

  // Si el código viene como JSON string, intentar extraer el contenido
  if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
    try {
      const parsed = JSON.parse(trimmed);
      // Si tiene una propiedad que contiene código PHP
      if (typeof parsed === 'object' && parsed.modelFileContent) {
        const content = parsed.modelFileContent;
        if (content.includes('<?php') || content.includes('namespace') || content.includes('class ')) {
          return 'php';
        }
      }
      // Si es un JSON válido sin contenido PHP, es JSON
      return 'json';
    } catch {
      // No es JSON válido, continuar con la detección normal
    }
  }

  // Detectar PHP directamente en el contenido
  if (trimmed.includes('<?php') || trimmed.includes('namespace') || trimmed.includes('class ') || trimmed.includes('function ') || trimmed.includes('protected ') || trimmed.includes('public ')) {
    return 'php';
  }

  // Si el contenido parece JSON pero no es válido
  if (trimmed.includes('"') && trimmed.includes(':') && (trimmed.includes('{') || trimmed.includes('['))) {
    return 'json';
  }

  return props.language;
};

// Extraer el código del contenido si viene en un JSON
const extractCode = (code: string): string => {
  const trimmed = code.trim();

  // Si el código viene como JSON string, intentar extraer el modelFileContent
  if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
    try {
      const parsed = JSON.parse(trimmed);
      if (typeof parsed === 'object' && parsed.modelFileContent) {
        return parsed.modelFileContent;
      }
    } catch {
      // No es JSON válido, devolver el código original
    }
  }

  return code;
};

const updateHighlighting = () => {
  if (codeContainer.value && props.code) {
    const extractedCode = extractCode(props.code);
    const detectedLang = detectLanguage(props.code);
    detectedLanguage.value = detectedLang;

    // Usar highlight.js para el resaltado
    const result = hljs.highlight(extractedCode, { language: detectedLang });
    codeContainer.value.innerHTML = result.value;
  }
};onMounted(() => {
  updateHighlighting();
});

watch(() => props.code, () => {
  updateHighlighting();
});
</script>

<template>
  <div
    :class="[
      'bg-gray-900 text-gray-100 rounded-lg overflow-hidden border border-gray-700',
      props.class
    ]"
  >
    <div class="bg-gray-800 px-4 py-2 border-b border-gray-700 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <div class="flex space-x-1">
          <div class="w-3 h-3 bg-red-500 rounded-full"></div>
          <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
          <div class="w-3 h-3 bg-green-500 rounded-full"></div>
        </div>
        <span class="text-sm text-gray-400">{{ detectedLanguage.toUpperCase() || (language || 'php').toUpperCase() }}</span>
      </div>
      <div class="text-xs text-gray-400">Read Only</div>
    </div>

    <div class="p-4 overflow-auto max-h-full">
      <pre
        ref="codeContainer"
        class="text-sm leading-relaxed whitespace-pre-wrap font-mono hljs"
        :class="`language-${detectedLanguage || language || 'php'}`"
      ></pre>
      <div v-if="!code" class="text-sm text-gray-400 font-mono">No code available</div>
    </div>
  </div>
</template>

<style scoped>
/* Sobrescribir estilos de highlight.js para que se vean bien con nuestro tema oscuro */
.hljs {
  background: transparent !important;
  color: inherit !important;
}
</style>
