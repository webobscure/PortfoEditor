<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import { templatesApi } from '@/api/templates'
import AppIcon from '@/components/ui/AppIcon.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'

import type { Template } from '@/types'

/**
 * Full-screen live preview of a template.
 *
 * The frame is a real render at a real viewport width, so the desktop/mobile
 * switch shows the template's actual responsive behaviour rather than a
 * squashed desktop layout.
 */
const props = defineProps<{
  open: boolean
  template: Template | null
  preset?: string
  /** When set, previews this portfolio's own content instead of the demo. */
  portfolioPreviewUrl?: string
  actionLabel?: string
}>()

const emit = defineEmits<{ close: []; choose: [Template] }>()

const device = ref<'desktop' | 'mobile'>('desktop')
const loaded = ref(false)

watch(
  () => [props.open, props.template?.key, device.value],
  () => {
    loaded.value = false
  },
)

const src = computed(() => {
  if (!props.template) return ''

  return props.portfolioPreviewUrl ?? templatesApi.demoUrl(props.template.key, props.preset)
})
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
        v-if="open && template"
        class="fixed inset-0 z-50 flex flex-col bg-[#101014]/90 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        :aria-label="`Предпросмотр шаблона «${template.name}»`"
        @keydown.esc="emit('close')"
      >
        <header class="flex flex-wrap items-center gap-3 px-4 py-3 sm:px-5">
          <div class="min-w-0">
            <h2 class="truncate text-[15px] font-semibold text-white">{{ template.name }}</h2>
            <p class="truncate text-[12.5px] text-white/55">{{ template.description }}</p>
          </div>

          <div class="flex-1" />

          <div class="[&_*]:!border-white/15 [&_button]:!text-white/60">
            <SegmentedControl
              v-model="device"
              label="Устройство предпросмотра"
              compact
              class="!bg-white/10"
              :options="[
                { value: 'desktop', label: 'Десктоп', icon: 'desktop' },
                { value: 'mobile', label: 'Мобильный', icon: 'mobile' },
              ]"
            />
          </div>

          <BaseButton variant="primary" @click="emit('choose', template)">
            {{ actionLabel ?? 'Взять этот шаблон' }}
          </BaseButton>

          <button
            type="button"
            aria-label="Закрыть предпросмотр"
            class="grid size-8 place-items-center rounded-[10px] text-white/70 transition hover:bg-white/10 hover:text-white"
            @click="emit('close')"
          >
            <AppIcon name="close" :size="17" />
          </button>
        </header>

        <div class="flex min-h-0 flex-1 items-center justify-center px-3 pb-4 sm:px-6">
          <div
            class="relative h-full overflow-hidden rounded-[14px] bg-white shadow-lift transition-[width] duration-300 ease-[cubic-bezier(0.22,0.8,0.3,1)]"
            :class="device === 'mobile' ? 'w-[390px] max-w-full' : 'w-full max-w-[1400px]'"
          >
            <div v-if="!loaded" class="skeleton absolute inset-0" aria-hidden="true" />
            <iframe
              :key="`${template.key}-${device}`"
              :src="src"
              :title="`Предпросмотр шаблона «${template.name}»`"
              class="size-full border-0 transition-opacity duration-300"
              :class="loaded ? 'opacity-100' : 'opacity-0'"
              @load="loaded = true"
            />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
