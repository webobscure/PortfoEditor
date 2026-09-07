<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

import { usePreviewDocument } from './usePreviewDocument'
import PreviewFrame from './PreviewFrame.vue'

import { useEditorStore } from '@/stores/editor'

import type { DeviceKey } from '@/types'

/**
 * The device canvas.
 *
 * Widths are real: the frame is laid out at 1440/834/390 CSS pixels and then
 * scaled down to fit, so the template's media queries fire exactly as they will
 * on a real screen. Scaling the whole page instead would evaluate them against
 * the browser window and the mobile view would be a lie.
 */
const DEVICES: Record<DeviceKey, { width: number; label: string }> = {
  desktop: { width: 1440, label: 'Desktop' },
  tablet: { width: 834, label: 'Tablet' },
  mobile: { width: 390, label: 'Mobile' },
}

const editor = useEditorStore()
const { html } = usePreviewDocument()

const stage = ref<HTMLElement | null>(null)
const frame = ref<InstanceType<typeof PreviewFrame> | null>(null)
const available = ref({ width: 0, height: 0 })

let observer: ResizeObserver | null = null

onMounted(() => {
  observer = new ResizeObserver(([entry]) => {
    available.value = {
      width: entry.contentRect.width,
      height: entry.contentRect.height,
    }
  })

  if (stage.value) observer.observe(stage.value)
})

onBeforeUnmount(() => observer?.disconnect())

const device = computed(() => DEVICES[editor.device])

const scale = computed(() => {
  if (available.value.width === 0) return 1

  return Math.min(1, available.value.width / device.value.width)
})

// The frame is laid out at full device size and scaled; the wrapper reserves
// the post-scale footprint so centring and the shadow line up with what is
// actually drawn.
const frameHeight = computed(() =>
  available.value.height > 0 ? available.value.height / scale.value : 900,
)

const scaledWidth = computed(() => device.value.width * scale.value)
const scaledHeight = computed(() => frameHeight.value * scale.value)

function scrollToSection(id: number): void {
  frame.value?.scrollToSection(id)
}

defineExpose({ scrollToSection })
</script>

<template>
  <div class="flex h-full min-h-0 flex-col">
    <div ref="stage" class="relative flex min-h-0 flex-1 justify-center overflow-hidden">
      <div
        class="relative overflow-hidden rounded-[14px] bg-white shadow-lift transition-[width] duration-300 ease-[cubic-bezier(0.22,0.8,0.3,1)]"
        :style="{ width: `${scaledWidth}px`, height: `${scaledHeight}px` }"
      >
        <div
          class="absolute top-0 left-0 origin-top-left"
          :style="{
            width: `${device.width}px`,
            height: `${frameHeight}px`,
            transform: `scale(${scale})`,
          }"
        >
          <PreviewFrame ref="frame" :html="html" @select="editor.select($event)" />
        </div>

        <!-- Skeleton while the first document is being assembled. -->
        <div
          v-if="!html"
          class="absolute inset-0 flex flex-col gap-4 bg-white p-10"
          aria-hidden="true"
        >
          <div class="skeleton h-3 w-24" />
          <div class="skeleton h-12 w-3/5" />
          <div class="skeleton h-3 w-2/5" />
          <div class="skeleton mt-6 h-56 w-full" />
        </div>
      </div>
    </div>
  </div>
</template>
