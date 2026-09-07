<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import { portfoliosApi } from '@/api/portfolios'
import AppIcon from '@/components/ui/AppIcon.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseTooltip from '@/components/ui/BaseTooltip.vue'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'
import { useEditorStore } from '@/stores/editor'

import SaveIndicator from './SaveIndicator.vue'

import type { DeviceKey } from '@/types'

/**
 * Kept short on purpose: every pixel here is a pixel the preview does not get.
 */
const editor = useEditorStore()
const router = useRouter()

const emit = defineEmits<{ templates: []; download: []; sections: []; settings: [] }>()
defineProps<{ downloading?: boolean }>()

const name = ref(editor.portfolio?.name ?? '')

watch(
  () => editor.portfolio?.name,
  (value) => {
    if (value !== undefined && value !== name.value) name.value = value
  },
)

function commitName(): void {
  const next = name.value.trim()

  if (next === '' || next === editor.portfolio?.name) {
    name.value = editor.portfolio?.name ?? ''

    return
  }

  editor.rename(next)
}

async function back(): Promise<void> {
  await editor.saveNow()
  router.push({ name: 'dashboard' })
}
</script>

<template>
  <header
    class="flex h-13 shrink-0 items-center gap-2 border-b border-line bg-surface px-2.5 sm:px-3"
  >
    <BaseTooltip text="Back to your portfolios">
      <button
        type="button"
        class="grid size-8 place-items-center rounded-[9px] text-ink-soft transition hover:bg-line-soft hover:text-ink"
        aria-label="Back to your portfolios"
        @click="back"
      >
        <AppIcon name="arrowLeft" :size="16" />
      </button>
    </BaseTooltip>

    <input
      v-model="name"
      class="min-w-0 max-w-[15rem] flex-1 rounded-[9px] border border-transparent bg-transparent px-2 py-1 text-[13.5px] font-medium transition hover:border-line focus:border-brand focus:bg-surface focus:shadow-[0_0_0_3px_var(--color-brand-soft)] focus:outline-none sm:flex-none"
      aria-label="Portfolio name"
      maxlength="120"
      @blur="commitName"
      @keydown.enter="($event.target as HTMLInputElement).blur()"
    />

    <SaveIndicator class="hidden sm:flex" />

    <div class="flex-1" />

    <div class="hidden items-center gap-1 md:flex">
      <BaseTooltip text="Undo (⌘Z)">
        <button
          type="button"
          class="grid size-8 place-items-center rounded-[9px] text-ink-soft transition enabled:hover:bg-line-soft enabled:hover:text-ink disabled:opacity-30"
          aria-label="Undo"
          :disabled="!editor.canUndo"
          @click="editor.undo()"
        >
          <AppIcon name="undo" :size="16" />
        </button>
      </BaseTooltip>
      <BaseTooltip text="Redo (⇧⌘Z)">
        <button
          type="button"
          class="grid size-8 place-items-center rounded-[9px] text-ink-soft transition enabled:hover:bg-line-soft enabled:hover:text-ink disabled:opacity-30"
          aria-label="Redo"
          :disabled="!editor.canRedo"
          @click="editor.redo()"
        >
          <AppIcon name="redo" :size="16" />
        </button>
      </BaseTooltip>
    </div>

    <SegmentedControl
      :model-value="editor.device"
      label="Preview device"
      compact
      class="hidden sm:inline-flex"
      :options="[
        { value: 'desktop', label: 'Desktop', icon: 'desktop' },
        { value: 'tablet', label: 'Tablet', icon: 'tablet' },
        { value: 'mobile', label: 'Mobile', icon: 'mobile' },
      ]"
      @update:model-value="editor.setDevice($event as DeviceKey)"
    />

    <div class="flex-1 sm:hidden" />

    <!-- Drawer triggers, small screens only. -->
    <button
      type="button"
      class="grid size-8 place-items-center rounded-[9px] text-ink-soft transition hover:bg-line-soft lg:hidden"
      aria-label="Sections"
      @click="emit('sections')"
    >
      <AppIcon name="layers" :size="16" />
    </button>
    <button
      type="button"
      class="grid size-8 place-items-center rounded-[9px] text-ink-soft transition hover:bg-line-soft lg:hidden"
      aria-label="Settings"
      @click="emit('settings')"
    >
      <AppIcon name="sliders" :size="16" />
    </button>

    <BaseButton size="sm" icon="palette" class="hidden sm:inline-flex" @click="emit('templates')">
      Templates
    </BaseButton>

    <a
      v-if="editor.portfolio"
      :href="portfoliosApi.previewUrl(editor.portfolio.id)"
      target="_blank"
      rel="noopener"
      class="hidden h-7 items-center gap-1.5 rounded-[10px] border border-line bg-surface px-2.5 text-[12.5px] font-medium text-ink shadow-soft transition hover:border-[#d9d9de] sm:inline-flex"
    >
      <AppIcon name="external" :size="14" />
      Preview
    </a>

    <BaseButton
      size="sm"
      variant="primary"
      icon="download"
      :loading="downloading"
      @click="emit('download')"
    >
      <span class="hidden sm:inline">Download</span>
    </BaseButton>
  </header>
</template>
