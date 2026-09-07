<script setup lang="ts">
import { ref } from 'vue'

import AppIcon from '@/components/ui/AppIcon.vue'
import BaseTooltip from '@/components/ui/BaseTooltip.vue'

/**
 * One entry in a list of things — a job, a project, a skill group.
 *
 * Collapsed by default beyond the first so a ten-role CV does not become a
 * three-metre scroll.
 */
withDefaults(
  defineProps<{
    title: string
    subtitle?: string
    open?: boolean
    canMoveUp?: boolean
    canMoveDown?: boolean
  }>(),
  {
    open: false,
  },
)

const emit = defineEmits<{ remove: []; up: []; down: [] }>()

const expanded = ref(false)
</script>

<template>
  <div class="rounded-[12px] border border-line bg-surface">
    <div class="flex items-center gap-1 px-1.5 py-1.5">
      <button
        type="button"
        class="flex min-w-0 flex-1 items-center gap-2 rounded-[8px] px-1.5 py-1 text-left transition hover:bg-line-soft"
        :aria-expanded="expanded || open"
        @click="expanded = !expanded"
      >
        <AppIcon
          name="chevronDown"
          :size="13"
          class="shrink-0 text-ink-faint transition-transform duration-200"
          :class="expanded || open ? '' : '-rotate-90'"
        />
        <span class="min-w-0">
          <span class="block truncate text-[13px] font-medium">{{ title || 'Untitled' }}</span>
          <span v-if="subtitle" class="block truncate text-[11.5px] text-ink-muted">
            {{ subtitle }}
          </span>
        </span>
      </button>

      <BaseTooltip text="Move up">
        <button
          type="button"
          class="grid size-6 place-items-center rounded-md text-ink-faint transition enabled:hover:bg-line-soft enabled:hover:text-ink-soft disabled:opacity-30"
          aria-label="Выше"
          :disabled="!canMoveUp"
          @click="emit('up')"
        >
          <AppIcon name="chevronDown" :size="13" class="rotate-180" />
        </button>
      </BaseTooltip>

      <BaseTooltip text="Move down">
        <button
          type="button"
          class="grid size-6 place-items-center rounded-md text-ink-faint transition enabled:hover:bg-line-soft enabled:hover:text-ink-soft disabled:opacity-30"
          aria-label="Ниже"
          :disabled="!canMoveDown"
          @click="emit('down')"
        >
          <AppIcon name="chevronDown" :size="13" />
        </button>
      </BaseTooltip>

      <BaseTooltip text="Remove">
        <button
          type="button"
          class="grid size-6 place-items-center rounded-md text-ink-faint transition hover:bg-danger-soft hover:text-danger"
          aria-label="Удалить"
          @click="emit('remove')"
        >
          <AppIcon name="close" :size="13" />
        </button>
      </BaseTooltip>
    </div>

    <div v-show="expanded || open" class="space-y-3 border-t border-line-soft p-3">
      <slot />
    </div>
  </div>
</template>
