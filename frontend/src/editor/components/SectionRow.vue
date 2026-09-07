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
    <!--
      The handle used to be invisible until the row was hovered, which meant
      the list gave no sign that sections can be reordered at all. It is now
      always drawn, in the faintest ink of the palette: legible enough to be
      discovered, quiet enough not to compete with the section names.
    -->
    <BaseTooltip class="shrink-0" align="start" text="Перетащите, чтобы поменять порядок">
      <span
        class="js-drag-handle grid size-6 shrink-0 cursor-grab place-items-center text-ink-faint transition group-hover:text-ink-soft active:cursor-grabbing"
        aria-hidden="true"
      >
        <AppIcon name="grip" :size="14" />
      </span>
    </BaseTooltip>

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
          Не отображается в этом шаблоне
        </span>
        <span v-else-if="section.is_placeholder" class="block truncate text-[11px] text-ink-faint">
          Текст-пример
        </span>
      </span>
    </button>

    <BaseTooltip :text="section.enabled ? 'Скрыть секцию' : 'Показать секцию'">
      <button
        type="button"
        class="grid size-6 place-items-center rounded-md text-ink-faint transition hover:bg-line hover:text-ink-soft"
        :aria-label="section.enabled ? 'Скрыть секцию' : 'Показать секцию'"
        @click="emit('toggle')"
      >
        <AppIcon :name="section.enabled ? 'eye' : 'eyeOff'" :size="14" />
      </button>
    </BaseTooltip>

    <BaseTooltip text="Delete section">
      <button
        type="button"
        class="grid size-6 place-items-center rounded-md text-ink-faint opacity-0 transition group-hover:opacity-100 hover:bg-danger-soft hover:text-danger"
        aria-label="Удалить секцию"
        @click="emit('remove')"
      >
        <AppIcon name="trash" :size="14" />
      </button>
    </BaseTooltip>
  </div>
</template>
