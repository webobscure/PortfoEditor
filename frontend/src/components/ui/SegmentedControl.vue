<script setup lang="ts">
import AppIcon from './AppIcon.vue'

import type { IconName } from './icons'

/**
 * A compact radio group. Used for device switching and small enum settings
 * where a dropdown would hide the options that matter.
 */
defineProps<{
  modelValue: string
  options: { value: string; label: string; icon?: IconName }[]
  /** Named `label` rather than `ariaLabel` because Vue passes an `aria-label`
   * attribute straight through instead of camelising it into a prop. */
  label: string
  compact?: boolean
}>()

const emit = defineEmits<{ 'update:modelValue': [string] }>()
</script>

<template>
  <div
    role="radiogroup"
    :aria-label="label"
    class="inline-flex items-center gap-0.5 rounded-[10px] border border-line bg-raised p-0.5"
  >
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      role="radio"
      :aria-checked="modelValue === option.value"
      :title="option.label"
      class="inline-flex items-center gap-1.5 rounded-[8px] transition-all duration-150"
      :class="[
        compact ? 'h-7 px-2 text-[12px]' : 'h-8 px-2.5 text-[12.5px]',
        modelValue === option.value
          ? 'bg-surface text-ink shadow-soft font-medium'
          : 'text-ink-muted hover:text-ink-soft',
      ]"
      @click="emit('update:modelValue', option.value)"
    >
      <AppIcon v-if="option.icon" :name="option.icon" :size="14" />
      <span :class="option.icon && compact ? 'sr-only' : ''">{{ option.label }}</span>
    </button>
  </div>
</template>
