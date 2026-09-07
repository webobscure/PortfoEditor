<script setup lang="ts">
import { computed, ref, toRef, watch } from 'vue'

import { useFocusTrap } from '@/composables/useFocusTrap'

import AppIcon from './AppIcon.vue'

const props = withDefaults(
  defineProps<{
    open: boolean
    title?: string
    description?: string
    size?: 'sm' | 'md' | 'lg' | 'full'
    hideClose?: boolean
  }>(),
  { size: 'md' },
)

const emit = defineEmits<{ close: [] }>()

const panel = ref<HTMLElement | null>(null)

useFocusTrap(panel, toRef(props, 'open'), () => emit('close'))

// The page behind the overlay must not scroll.
watch(
  () => props.open,
  (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
  },
  { immediate: true },
)

const widthClass = computed(
  () =>
    ({
      sm: 'max-w-md',
      md: 'max-w-xl',
      lg: 'max-w-3xl',
      full: 'max-w-[min(1240px,95vw)] h-[92vh]',
    })[props.size],
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
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#17171a]/35 p-4 backdrop-blur-[2px]"
        @click.self="emit('close')"
      >
        <div
          ref="panel"
          role="dialog"
          aria-modal="true"
          :aria-label="title || undefined"
          tabindex="-1"
          class="panel flex w-full flex-col overflow-hidden shadow-lift outline-none"
          :class="widthClass"
        >
          <div v-if="title" class="flex items-start justify-between gap-4 px-5 pt-4 pb-3">
            <div>
              <h2 class="text-[15px] font-semibold tracking-[-0.01em]">{{ title }}</h2>
              <p v-if="description" class="mt-0.5 text-[13px] text-ink-muted">{{ description }}</p>
            </div>
            <button
              v-if="!hideClose"
              type="button"
              aria-label="Close"
              class="grid size-7 place-items-center rounded-lg text-ink-muted transition hover:bg-line-soft hover:text-ink"
              @click="emit('close')"
            >
              <AppIcon name="close" :size="15" />
            </button>
          </div>

          <div class="min-h-0 flex-1 overflow-auto scroll-slim">
            <slot />
          </div>

          <div v-if="$slots.footer" class="border-t border-line-soft px-5 py-3">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
