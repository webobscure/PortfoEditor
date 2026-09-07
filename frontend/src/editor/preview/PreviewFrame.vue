<script setup lang="ts">
import morphdom from 'morphdom'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

/**
 * The live preview surface.
 *
 * The iframe is fed the exact HTML string the exporter produces, so what the
 * user sees is the artifact rather than an approximation of it. Updates are
 * patched into the live document with morphdom instead of reassigning srcdoc,
 * which keeps scroll position, focus and CSS transitions intact while typing.
 *
 * An iframe rather than a scaled div is not a detail: media queries have to
 * resolve against the simulated viewport for the device switcher to mean
 * anything, and template CSS must not be able to reach the editor's own styles.
 */
const props = defineProps<{ html: string }>()
const emit = defineEmits<{ select: [number]; ready: [] }>()

const frame = ref<HTMLIFrameElement | null>(null)
const booted = ref(false)

let parser: DOMParser | null = null
// An empty iframe fires `load` before anything is written to it; without this
// the first real document would be patched into a blank one.
let awaitingBoot = false

function documentOf(): Document | null {
  return frame.value?.contentDocument ?? null
}

function onClick(event: MouseEvent): void {
  const target = event.target as HTMLElement | null
  const section = target?.closest<HTMLElement>('[data-pf-section]')

  // Links inside the preview would navigate the iframe away from the document
  // we are patching, so they are inert here.
  const link = target?.closest('a')

  if (link) event.preventDefault()

  if (!section) return

  const id = Number(section.dataset.pfSection)

  if (Number.isFinite(id)) emit('select', id)
}

function attach(): void {
  if (!awaitingBoot) return

  const doc = documentOf()

  if (!doc) return

  awaitingBoot = false
  doc.addEventListener('click', onClick)
  booted.value = true
  emit('ready')
}

function boot(html: string): void {
  const iframe = frame.value

  if (!iframe) return

  booted.value = false
  awaitingBoot = true
  iframe.srcdoc = html
}

function patch(html: string): void {
  const doc = documentOf()

  if (!doc?.documentElement) return

  parser ??= new DOMParser()
  const next = parser.parseFromString(html, 'text/html')

  morphdom(doc.documentElement, next.documentElement, {
    // Preserve the iframe's own scroll: morphdom must not replace the root.
    childrenOnly: true,
  })
}

onMounted(() => {
  if (props.html) boot(props.html)
})

watch(
  () => props.html,
  (html) => {
    if (!html) return

    if (booted.value) {
      patch(html)
    } else if (!awaitingBoot) {
      boot(html)
    }
  },
)

/** Bring a section into view when it is picked in the sidebar. */
function scrollToSection(id: number): void {
  const doc = documentOf()
  const element = doc?.querySelector<HTMLElement>(`[data-pf-section="${id}"]`)

  element?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

defineExpose({ scrollToSection })

onBeforeUnmount(() => {
  documentOf()?.removeEventListener('click', onClick)
})
</script>

<template>
  <iframe
    ref="frame"
    title="Portfolio preview"
    class="size-full border-0 bg-white"
    @load="attach"
  />
</template>
