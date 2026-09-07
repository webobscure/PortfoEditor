<script setup lang="ts">
import { ref } from 'vue'

import { useDismissable } from '@/composables/useDismissable'

/**
 * A light popover for menus and colour pickers. Closes on Escape and on a click
 * outside, and never traps focus — it is not a dialog.
 */
withDefaults(defineProps<{ align?: 'left' | 'right'; width?: string }>(), {
  align: 'right',
  width: 'w-56',
})

const open = ref(false)
const trigger = ref<HTMLElement | null>(null)
const panel = ref<HTMLElement | null>(null)

useDismissable(open, [trigger, panel], () => (open.value = false))

function toggle(): void {
  open.value = !open.value
}

defineExpose({ close: () => (open.value = false) })
</script>

<template>
  <div class="relative">
    <div ref="trigger">
      <slot name="trigger" :open="open" :toggle="toggle" />
    </div>

    <Transition
      enter-active-class="transition duration-150 ease-[cubic-bezier(0.22,0.8,0.3,1)]"
      enter-from-class="opacity-0 scale-[0.97] -translate-y-1"
      leave-active-class="transition duration-100 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        ref="panel"
        class="absolute top-[calc(100%+6px)] z-40 origin-top rounded-[12px] border border-line bg-surface p-1.5 shadow-pop"
        :class="[align === 'right' ? 'right-0' : 'left-0', width]"
      >
        <slot :close="() => (open = false)" />
      </div>
    </Transition>
  </div>
</template>
