<script setup lang="ts">
import { storeToRefs } from 'pinia'

import { useToastStore } from '@/stores/toast'

import AppIcon from './AppIcon.vue'

import type { IconName } from './icons'

const store = useToastStore()
const { toasts } = storeToRefs(store)

const icons: Record<string, IconName> = { info: 'info', success: 'check', error: 'alert' }
const tones: Record<string, string> = {
  info: 'text-ink-soft',
  success: 'text-positive',
  error: 'text-danger',
}
</script>

<template>
  <div
    class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex flex-col items-center gap-2 p-4 sm:items-end"
    role="status"
    aria-live="polite"
  >
    <TransitionGroup
      enter-active-class="transition duration-200 ease-[cubic-bezier(0.22,0.8,0.3,1)]"
      enter-from-class="opacity-0 translate-y-2"
      leave-active-class="transition duration-150 ease-in absolute"
      leave-to-class="opacity-0 translate-y-1"
      move-class="transition duration-200"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto flex w-full max-w-sm items-start gap-2.5 rounded-[12px] border border-line bg-surface p-3 shadow-pop"
      >
        <AppIcon :name="icons[toast.tone]" :size="16" :class="['mt-0.5', tones[toast.tone]]" />
        <div class="min-w-0 flex-1">
          <p class="text-[13px] font-medium">{{ toast.title }}</p>
          <p v-if="toast.description" class="mt-0.5 text-[12.5px] text-ink-muted">
            {{ toast.description }}
          </p>
          <button
            v-if="toast.action"
            type="button"
            class="mt-1.5 text-[12.5px] font-medium text-brand hover:underline"
            @click="toast.action.run()"
          >
            {{ toast.action.label }}
          </button>
        </div>
        <button
          type="button"
          aria-label="Скрыть"
          class="grid size-6 shrink-0 place-items-center rounded-md text-ink-faint transition hover:bg-line-soft hover:text-ink-soft"
          @click="store.dismiss(toast.id)"
        >
          <AppIcon name="close" :size="13" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
