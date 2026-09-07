<script setup lang="ts">
import { computed, useId } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: string | null | undefined
    label?: string
    placeholder?: string
    rows?: number
    hint?: string
    error?: string
    maxlength?: number
  }>(),
  { rows: 4 },
)

const emit = defineEmits<{ 'update:modelValue': [string] }>()

const id = useId()
const remaining = computed(() =>
  props.maxlength ? props.maxlength - (props.modelValue?.length ?? 0) : null,
)
</script>

<template>
  <div>
    <div v-if="label" class="flex items-baseline justify-between">
      <label :for="id" class="field-label">{{ label }}</label>
      <span
        v-if="remaining !== null && remaining < 60"
        class="mb-1.5 text-[11px] tabular-nums"
        :class="remaining < 0 ? 'text-danger' : 'text-ink-faint'"
      >
        {{ remaining }}
      </span>
    </div>
    <textarea
      :id="id"
      :value="modelValue ?? ''"
      :placeholder="placeholder"
      :rows="rows"
      :maxlength="maxlength"
      :aria-invalid="error ? 'true' : undefined"
      class="field resize-y leading-relaxed"
      @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    />
    <p v-if="error" class="mt-1.5 text-[12px] text-danger">{{ error }}</p>
    <p v-else-if="hint" class="mt-1.5 text-[12px] text-ink-muted">{{ hint }}</p>
  </div>
</template>
