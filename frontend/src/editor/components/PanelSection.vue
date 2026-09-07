<script setup lang="ts">
import { ref } from 'vue'

import AppIcon from '@/components/ui/AppIcon.vue'

/** A collapsible group inside a side panel. */
const props = withDefaults(defineProps<{ title: string; defaultOpen?: boolean }>(), {
  defaultOpen: true,
})

const open = ref(props.defaultOpen)
</script>

<template>
  <section class="border-b border-line-soft last:border-b-0">
    <button
      type="button"
      class="flex w-full items-center justify-between gap-2 px-4 py-2.5 text-left"
      :aria-expanded="open"
      @click="open = !open"
    >
      <span class="panel-eyebrow">{{ title }}</span>
      <AppIcon
        name="chevronDown"
        :size="14"
        class="text-ink-faint transition-transform duration-200"
        :class="open ? '' : '-rotate-90'"
      />
    </button>

    <div v-show="open" class="space-y-3.5 px-4 pt-0.5 pb-4">
      <slot />
    </div>
  </section>
</template>
