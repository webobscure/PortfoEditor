<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import BasePopover from './BasePopover.vue'

/**
 * A swatch that opens a popover with the native picker, a hex field and a small
 * set of considered presets.
 *
 * Emitting only on a valid six-digit hex keeps half-typed values out of the
 * store, so the preview never flashes black while someone types.
 */
const props = defineProps<{ modelValue: string; label: string; presets?: string[] }>()
const emit = defineEmits<{ 'update:modelValue': [string] }>()

const draft = ref(props.modelValue)

watch(
  () => props.modelValue,
  (value) => (draft.value = value),
)

const isValid = computed(() => /^#[0-9a-fA-F]{6}$/.test(draft.value))

function commit(value: string): void {
  draft.value = value

  if (/^#[0-9a-fA-F]{6}$/.test(value)) {
    emit('update:modelValue', value.toLowerCase())
  }
}

function onHexInput(event: Event): void {
  const raw = (event.target as HTMLInputElement).value.trim()

  commit(raw.startsWith('#') ? raw : `#${raw}`)
}
</script>

<template>
  <div class="flex items-center justify-between gap-3">
    <span class="text-[12.5px] text-ink-soft">{{ label }}</span>

    <BasePopover align="right" width="w-60">
      <template #trigger="{ toggle }">
        <button
          type="button"
          class="flex items-center gap-2 rounded-[9px] border border-line bg-surface py-1 pr-2 pl-1 transition hover:border-[#d9d9de]"
          :aria-label="`${label}: ${modelValue}`"
          @click="toggle"
        >
          <span
            class="size-5 rounded-[6px] border border-black/8"
            :style="{ background: modelValue }"
          />
          <span class="font-mono text-[11.5px] text-ink-muted uppercase">{{ modelValue }}</span>
        </button>
      </template>

      <div class="p-1.5">
        <input
          type="color"
          :value="isValid ? draft : '#000000'"
          class="h-20 w-full cursor-pointer rounded-[9px] border border-line bg-surface p-1"
          :aria-label="`${label} colour picker`"
          @input="commit(($event.target as HTMLInputElement).value)"
        />

        <input
          :value="draft"
          maxlength="7"
          spellcheck="false"
          class="field mt-2 font-mono text-[12px] uppercase"
          :class="isValid ? '' : 'border-danger'"
          :aria-label="`${label} hex value`"
          @input="onHexInput"
        />

        <div v-if="presets?.length" class="mt-2 grid grid-cols-6 gap-1.5">
          <button
            v-for="preset in presets"
            :key="preset"
            type="button"
            class="size-7 rounded-[7px] border border-black/8 transition hover:scale-110"
            :style="{ background: preset }"
            :aria-label="preset"
            @click="commit(preset)"
          />
        </div>
      </div>
    </BasePopover>
  </div>
</template>
