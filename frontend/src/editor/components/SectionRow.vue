<script setup lang="ts">
import AppIcon from '@/components/ui/AppIcon.vue'
import BaseTooltip from '@/components/ui/BaseTooltip.vue'
import { SECTION_META } from '@/editor/sections/registry'

import type { Section } from '@/types'

defineProps<{ section: Section; active: boolean; unsupported: boolean }>()

const emit = defineEmits<{ select: []; toggle: []; remove: [] }>()
</script>

<template>
  <div
    class="group flex items-center gap-1 rounded-[10px] pr-1 pl-0.5 transition-colors duration-150"
    :class="active ? 'bg-brand-soft' : 'hover:bg-line-soft'"
  >
    <span
      class="js-drag-handle grid size-6 shrink-0 cursor-grab place-items-center text-ink-faint opacity-0 transition group-hover:opacity-100 active:cursor-grabbing"
      aria-hidden="true"
    >
      <AppIcon name="grip" :size="14" />
    </span>

    <button
      type="button"
      class="flex min-w-0 flex-1 items-center gap-2 py-1.5 text-left"
      :aria-current="active ? 'true' : undefined"
      @click="emit('select')"
    >
      <AppIcon
        :name="SECTION_META[section.type].icon"
        :size="15"
        :class="active ? 'text-brand' : 'text-ink-muted'"
      />
      <span class="min-w-0 flex-1">
        <span
          class="block truncate text-[13px]"
          :class="[
            active ? 'font-medium text-brand' : 'text-ink',
            section.enabled ? '' : 'opacity-50',
          ]"
        >
          {{ SECTION_META[section.type].label }}
        </span>
        <span v-if="unsupported" class="block truncate text-[11px] text-warning">
          Not shown in this template
        </span>
        <span v-else-if="section.is_placeholder" class="block truncate text-[11px] text-ink-faint">
          Sample content
        </span>
      </span>
    </button>

    <BaseTooltip :text="section.enabled ? 'Hide section' : 'Show section'">
      <button
        type="button"
        class="grid size-6 place-items-center rounded-md text-ink-faint transition hover:bg-line hover:text-ink-soft"
        :aria-label="section.enabled ? 'Hide section' : 'Show section'"
        @click="emit('toggle')"
      >
        <AppIcon :name="section.enabled ? 'eye' : 'eyeOff'" :size="14" />
      </button>
    </BaseTooltip>

    <BaseTooltip text="Delete section">
      <button
        type="button"
        class="grid size-6 place-items-center rounded-md text-ink-faint opacity-0 transition group-hover:opacity-100 hover:bg-danger-soft hover:text-danger"
        aria-label="Delete section"
        @click="emit('remove')"
      >
        <AppIcon name="trash" :size="14" />
      </button>
    </BaseTooltip>
  </div>
</template>
