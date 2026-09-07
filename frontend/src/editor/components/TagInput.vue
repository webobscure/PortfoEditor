<script setup lang="ts">
import { ref, useId } from 'vue'

import AppIcon from '@/components/ui/AppIcon.vue'

/** Comma or Enter adds a tag; Backspace on an empty field removes the last. */
const props = defineProps<{ modelValue: string[]; label: string; placeholder?: string }>()
const emit = defineEmits<{ 'update:modelValue': [string[]] }>()

const id = useId()
const draft = ref('')

function commit(): void {
  const value = draft.value.trim().replace(/,$/, '')

  if (value === '' || props.modelValue.includes(value)) {
    draft.value = ''

    return
  }

  emit('update:modelValue', [...props.modelValue, value])
  draft.value = ''
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'Enter' || event.key === ',') {
    event.preventDefault()
    commit()

    return
  }

  if (event.key === 'Backspace' && draft.value === '' && props.modelValue.length > 0) {
    emit('update:modelValue', props.modelValue.slice(0, -1))
  }
}

function removeAt(index: number): void {
  emit(
    'update:modelValue',
    props.modelValue.filter((_, i) => i !== index),
  )
}
</script>

<template>
  <div>
    <label :for="id" class="field-label">{{ label }}</label>
    <div
      class="flex flex-wrap items-center gap-1.5 rounded-[10px] border border-line bg-surface p-1.5 transition focus-within:border-brand focus-within:shadow-[0_0_0_3px_var(--color-brand-soft)]"
    >
      <span
        v-for="(tag, index) in modelValue"
        :key="`${tag}-${index}`"
        class="inline-flex items-center gap-1 rounded-full bg-line-soft py-0.5 pr-1 pl-2 text-[12px]"
      >
        {{ tag }}
        <button
          type="button"
          class="grid size-4 place-items-center rounded-full text-ink-faint transition hover:bg-line hover:text-ink"
          :aria-label="`Remove ${tag}`"
          @click="removeAt(index)"
        >
          <AppIcon name="close" :size="10" />
        </button>
      </span>

      <input
        :id="id"
        v-model="draft"
        :placeholder="modelValue.length === 0 ? placeholder : ''"
        class="min-w-[7rem] flex-1 bg-transparent px-1 py-0.5 text-[13px] outline-none"
        @keydown="onKeydown"
        @blur="commit"
      />
    </div>
  </div>
</template>
