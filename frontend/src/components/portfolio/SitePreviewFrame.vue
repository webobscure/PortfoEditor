<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

/**
 * A real, scaled-down rendering of a site.
 *
 * Used for dashboard cards and gallery tiles. Rendering the actual page inside
 * a scaled iframe beats a stored screenshot: it can never be out of date, and
 * because the iframe is sized to a desktop width before being transformed, the
 * template's own media queries evaluate correctly instead of collapsing to the
 * mobile layout.
 */
const props = withDefaults(
  defineProps<{ src: string; width?: number; ratio?: number; lazy?: boolean }>(),
  { width: 1280, ratio: 0.68, lazy: true },
)

const host = ref<HTMLElement | null>(null)
const scale = ref(0.25)
const visible = ref(!props.lazy)
const loaded = ref(false)

let observer: ResizeObserver | null = null
let intersection: IntersectionObserver | null = null

const frameHeight = computed(() => Math.round(props.width * props.ratio))

function measure(): void {
  if (!host.value) return

  scale.value = host.value.clientWidth / props.width
}

onMounted(() => {
  measure()

  observer = new ResizeObserver(measure)
  observer.observe(host.value as Element)

  if (props.lazy && host.value) {
    // Template thumbnails are full pages; loading them all at once on a gallery
    // would be gratuitous.
    intersection = new IntersectionObserver(
      (entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
          visible.value = true
          intersection?.disconnect()
        }
      },
      { rootMargin: '200px' },
    )
    intersection.observe(host.value)
  }
})

onBeforeUnmount(() => {
  observer?.disconnect()
  intersection?.disconnect()
})
</script>

<template>
  <div
    ref="host"
    class="relative overflow-hidden bg-raised"
    :style="{ aspectRatio: `1 / ${ratio}` }"
  >
    <div
      v-if="!loaded"
      class="skeleton absolute inset-0"
      :style="{ borderRadius: '0' }"
      aria-hidden="true"
    />

    <iframe
      v-if="visible"
      :src="src"
      title="Предпросмотр сайта"
      loading="lazy"
      tabindex="-1"
      aria-hidden="true"
      scrolling="no"
      class="pointer-events-none absolute top-0 left-0 origin-top-left border-0 transition-opacity duration-300"
      :class="loaded ? 'opacity-100' : 'opacity-0'"
      :style="{
        width: `${width}px`,
        height: `${frameHeight}px`,
        transform: `scale(${scale})`,
      }"
      @load="loaded = true"
    />
  </div>
</template>
