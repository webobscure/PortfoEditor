<script setup lang="ts">
import { computed } from 'vue'

import { PATHS, type IconName } from './icons'

const props = withDefaults(
  defineProps<{ name: IconName; size?: number | string; strokeWidth?: number }>(),
  { size: 16, strokeWidth: 1.6 },
)

const dimension = computed(() => (typeof props.size === 'number' ? `${props.size}px` : props.size))
const path = computed(() => PATHS[props.name])
</script>

<template>
  <svg
    :width="dimension"
    :height="dimension"
    viewBox="0 0 24 24"
    fill="none"
    :stroke-width="strokeWidth"
    stroke="currentColor"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
    class="shrink-0"
  >
    <path :d="path" />
  </svg>
</template>
