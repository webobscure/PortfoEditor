<script setup lang="ts">
import AppIcon from '@/components/ui/AppIcon.vue'
import { useEditorStore } from '@/stores/editor'

/**
 * Reports what actually happened, not what was attempted.
 *
 * The store retries failed saves on its own, so the failure state offers a
 * manual retry rather than pretending the change was lost.
 */
const editor = useEditorStore()
</script>

<template>
  <div class="flex items-center gap-1.5 text-[12px]" role="status" aria-live="polite">
    <template v-if="editor.saveState === 'saving'">
      <span
        class="size-3 animate-spin rounded-full border-[1.5px] border-ink-faint border-t-transparent"
        aria-hidden="true"
      />
      <span class="text-ink-muted">Saving…</span>
    </template>

    <template v-else-if="editor.saveState === 'error'">
      <AppIcon name="alert" :size="13" class="text-danger" />
      <span class="text-danger">Could not save changes</span>
      <button
        type="button"
        class="font-medium text-brand underline-offset-2 hover:underline"
        @click="editor.retryNow()"
      >
        Retry
      </button>
    </template>

    <template v-else-if="editor.saveState === 'saved'">
      <AppIcon name="check" :size="13" class="text-positive" />
      <span class="text-ink-muted">Saved</span>
    </template>
  </div>
</template>
