<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';

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

// Resaltado básico de sintaxis PHP
const highlightPHP = (code: string) => {
  return code
    .replace(/(&lt;\?php|&lt;\?)/g, '<span class="text-blue-600 font-semibold">$1</span>')
    .replace(/(namespace|use|class|extends|function|public|protected|private|return|if|else|foreach|for|while|do|switch|case|break|continue|try|catch|finally)/g, '<span class="text-purple-600 font-semibold">$1</span>')
    .replace(/(\$[a-zA-Z_][a-zA-Z0-9_]*)/g, '<span class="text-green-600">$1</span>')
    .replace(/(\/\/.*$)/gm, '<span class="text-gray-500 italic">$1</span>')
    .replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="text-gray-500 italic">$1</span>')
    .replace(/('.*?'|".*?")/g, '<span class="text-amber-600">$1</span>')
    .replace(/(\d+)/g, '<span class="text-blue-500">$1</span>')
    .replace(/(true|false|null)/g, '<span class="text-red-500 font-semibold">$1</span>');
};

const escapeHtml = (text: string) => {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
};

const updateHighlighting = () => {
  if (codeContainer.value && props.code) {
    const escapedCode = escapeHtml(props.code);
    const highlightedCode = props.language === 'php' ? highlightPHP(escapedCode) : escapedCode;
    codeContainer.value.innerHTML = highlightedCode;
  }
};

onMounted(() => {
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
        <span class="text-sm text-gray-400">{{ (language || 'php').toUpperCase() }}</span>
      </div>
      <div class="text-xs text-gray-400">Read Only</div>
    </div>

    <div class="p-4 overflow-auto max-h-96">
      <pre
        ref="codeContainer"
        class="text-sm leading-relaxed whitespace-pre-wrap font-mono"
      ></pre>
      <div v-if="!code" class="text-sm text-gray-400 font-mono">No code available</div>
    </div>
  </div>
</template>
