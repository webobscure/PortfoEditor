<script setup lang="ts">
import { ref } from 'vue'

/**
 * CSS-positioned tooltip. Shown on hover and on keyboard focus, because a
 * tooltip that only responds to a pointer is invisible to half the users who
 * need it.
 */
/*
 * `align` exists because a centred tooltip on a control at the very edge of a
 * panel is clipped by it. Anchoring to the control's left edge keeps the label
 * inside the panel.
 */
withDefaults(defineProps<{ text: string; side?: 'top' | 'bottom'; align?: 'center' | 'start' }>(), {
  side: 'bottom',
  align: 'center',
})

const visible = ref(false)
let timer: number | null = null

function show(): void {
  timer = window.setTimeout(() => (visible.value = true), 260)
}

function hide(): void {
  if (timer !== null) window.clearTimeout(timer)
  visible.value = false
}
</script>

<template>
  <span
    class="relative inline-flex"
    @mouseenter="show"
    @mouseleave="hide"
    @focusin="visible = true"
    @focusout="hide"
  >
    <slot />
    <Transition
      enter-active-class="transition duration-120 ease-out"
      enter-from-class="opacity-0 translate-y-0.5"
      leave-active-class="transition duration-80"
      leave-to-class="opacity-0"
    >
      <span
        v-if="visible"
        role="tooltip"
        class="pointer-events-none absolute z-50 rounded-lg bg-ink px-2 py-1 text-[11.5px] whitespace-nowrap text-white shadow-pop"
        :class="[
          side === 'bottom' ? 'top-[calc(100%+7px)]' : 'bottom-[calc(100%+7px)]',
          align === 'start' ? 'left-0' : 'left-1/2 -translate-x-1/2',
        ]"
      >
        {{ text }}
      </span>
    </Transition>
  </span>
</template>
