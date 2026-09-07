<script setup lang="ts">
import { useId } from 'vue'

import AppIcon from './AppIcon.vue'

defineProps<{
  modelValue: string
  label?: string
  options: { value: string; label: string }[]
  disabled?: boolean
}>()

const emit = defineEmits<{ 'update:modelValue': [string] }>()

const id = useId()
</script>

<template>
  <div>
    <label v-if="label" :for="id" class="field-label">{{ label }}</label>
    <div class="relative">
      <select
        :id="id"
        :value="modelValue"
        :disabled="disabled"
        class="field cursor-pointer appearance-none pr-8"
        @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
      >
        <option v-for="option in options" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
      <AppIcon
        name="chevronDown"
        :size="14"
        class="pointer-events-none absolute top-1/2 right-2.5 -translate-y-1/2 text-ink-muted"
      />
    </div>
  </div>
</template>
