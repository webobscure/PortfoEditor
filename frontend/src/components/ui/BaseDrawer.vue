<script setup lang="ts">
import { ref, toRef, watch } from 'vue'

import { useFocusTrap } from '@/composables/useFocusTrap'

import AppIcon from './AppIcon.vue'

/**
 * The mobile counterpart to the editor's side panels.
 *
 * Below the editor's three-column breakpoint the sections list and the settings
 * panel become drawers rather than being squeezed into 390px.
 */
const props = withDefaults(
  defineProps<{ open: boolean; title?: string; side?: 'left' | 'right' }>(),
  { side: 'right' },
)

const emit = defineEmits<{ close: [] }>()

const panel = ref<HTMLElement | null>(null)

useFocusTrap(panel, toRef(props, 'open'), () => emit('close'))

watch(
  () => props.open,
  (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
  },
  { immediate: true },
)
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="opacity-0"
    >
      <div v-if="open" class="fixed inset-0 z-50 bg-[#17171a]/30" @click.self="emit('close')">
        <Transition
          appear
          enter-active-class="transition duration-250 ease-[cubic-bezier(0.22,0.8,0.3,1)]"
          :enter-from-class="side === 'right' ? 'translate-x-full' : '-translate-x-full'"
        >
          <div
            ref="panel"
            role="dialog"
            aria-modal="true"
            :aria-label="title"
            tabindex="-1"
            class="absolute inset-y-0 flex w-[min(360px,88vw)] flex-col bg-surface shadow-lift outline-none"
            :class="side === 'right' ? 'right-0' : 'left-0'"
          >
            <header class="flex items-center justify-between border-b border-line-soft px-4 py-3">
              <h2 class="text-[14px] font-semibold">{{ title }}</h2>
              <button
                type="button"
                aria-label="Закрыть"
                class="grid size-7 place-items-center rounded-lg text-ink-muted transition hover:bg-line-soft hover:text-ink"
                @click="emit('close')"
              >
                <AppIcon name="close" :size="15" />
              </button>
            </header>
            <div class="min-h-0 flex-1 overflow-auto scroll-slim">
              <slot />
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
