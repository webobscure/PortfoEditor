<script setup lang="ts">
import AppIcon from './AppIcon.vue'

import type { IconName } from './icons'

defineProps<{ icon?: IconName; title: string; description?: string; compact?: boolean }>()
</script>

<template>
  <div
    class="flex flex-col items-center justify-center text-center"
    :class="compact ? 'gap-2 px-4 py-8' : 'gap-3 px-6 py-16'"
  >
    <div
      v-if="icon"
      class="grid place-items-center rounded-[14px] border border-line bg-raised text-ink-faint"
      :class="compact ? 'size-9' : 'size-12'"
    >
      <AppIcon :name="icon" :size="compact ? 17 : 21" />
    </div>
    <div>
      <p class="font-medium" :class="compact ? 'text-[13px]' : 'text-[15px]'">{{ title }}</p>
      <p
        v-if="description"
        class="mx-auto mt-1 max-w-[46ch] text-ink-muted"
        :class="compact ? 'text-[12.5px]' : 'text-[13.5px]'"
      >
        {{ description }}
      </p>
    </div>
    <div v-if="$slots.default" :class="compact ? 'mt-1' : 'mt-2'">
      <slot />
    </div>
  </div>
</template>
