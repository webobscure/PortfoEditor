<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import AppIcon from '@/components/ui/AppIcon.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseDrawer from '@/components/ui/BaseDrawer.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { useExport } from '@/composables/useExport'
import { useShortcuts } from '@/composables/useShortcuts'
import { useUnsavedGuard } from '@/composables/useUnsavedGuard'
import EditorToolbar from '@/editor/components/EditorToolbar.vue'
import SaveIndicator from '@/editor/components/SaveIndicator.vue'
import SectionList from '@/editor/components/SectionList.vue'
import TemplatePanel from '@/editor/components/TemplatePanel.vue'
import PreviewCanvas from '@/editor/preview/PreviewCanvas.vue'
import { SECTION_EDITORS, SECTION_META } from '@/editor/sections/registry'
import ThemePanel from '@/editor/settings/ThemePanel.vue'
import { useCatalogStore } from '@/stores/catalog'
import { useEditorStore } from '@/stores/editor'
import { useToastStore } from '@/stores/toast'

/**
 * The editor.
 *
 * Three columns on desktop — sections, preview, properties — with the preview
 * taking every pixel the other two do not need. Below the breakpoint the side
 * panels become drawers rather than being crushed into a phone-width column.
 */
const props = defineProps<{ id: number }>()

const editor = useEditorStore()
const catalog = useCatalogStore()
const toast = useToastStore()
const router = useRouter()
const { busy: downloading, download } = useExport()

const canvas = ref<InstanceType<typeof PreviewCanvas> | null>(null)

// The properties column only exists at xl; below that the same panels are
// presented as drawers, so the two must not both render at once.
const wideLayout = ref(false)
let layoutQuery: MediaQueryList | null = null

function syncLayout(event: MediaQueryListEvent | MediaQueryList): void {
  wideLayout.value = event.matches
}
const showTemplates = ref(false)
const sectionsDrawer = ref(false)
const settingsDrawer = ref(false)

const selected = computed(() => editor.selectedSection)
const editorComponent = computed(() =>
  selected.value ? SECTION_EDITORS[selected.value.type] : null,
)

onMounted(async () => {
  layoutQuery = window.matchMedia('(min-width: 1280px)')
  syncLayout(layoutQuery)
  layoutQuery.addEventListener('change', syncLayout)

  await catalog.load()

  try {
    await editor.load(props.id)
  } catch {
    // The store already recorded the message; the page renders it below.
  }
})

onBeforeUnmount(() => {
  layoutQuery?.removeEventListener('change', syncLayout)
  void editor.saveNow()
  editor.reset()
})

useShortcuts({
  save: () => {
    void editor.saveNow()
    toast.success('Saved')
  },
  undo: () => editor.undo(),
  redo: () => editor.redo(),
})

useUnsavedGuard(() => editor.hasPendingChanges)

// Picking a section in the rail scrolls the preview to it, which is what makes
// the two halves feel like one surface rather than a form beside a picture.
watch(
  () => editor.selectedSectionId,
  (id) => {
    if (id !== null) canvas.value?.scrollToSection(id)
  },
)

function selectSection(id: number): void {
  editor.select(id)
  sectionsDrawer.value = false
}

function startDownload(): void {
  if (!editor.portfolio) return

  void editor.saveNow().then(() => download(props.id, editor.portfolio?.name ?? 'portfolio'))
}
</script>

<template>
  <div class="flex h-full flex-col overflow-hidden bg-canvas">
    <EditorToolbar
      v-if="editor.portfolio"
      :downloading="downloading"
      @templates="showTemplates = !showTemplates"
      @download="startDownload"
      @sections="sectionsDrawer = true"
      @settings="settingsDrawer = true"
    />

    <div v-if="editor.loadError" class="grid flex-1 place-items-center p-6">
      <EmptyState
        icon="alert"
        title="Не удалось открыть это портфолио"
        :description="editor.loadError"
      >
        <BaseButton variant="primary" @click="router.push({ name: 'dashboard' })">
          К моим портфолио
        </BaseButton>
      </EmptyState>
    </div>

    <div v-else class="flex min-h-0 flex-1">
      <!-- Sections rail -->
      <aside class="hidden w-[248px] shrink-0 border-r border-line bg-surface lg:block">
        <SectionList @select="selectSection" />
      </aside>

      <!-- Preview -->
      <div class="flex min-w-0 flex-1 flex-col">
        <div class="flex items-center justify-between px-4 pt-3 pb-2 sm:hidden">
          <SaveIndicator />
        </div>
        <div class="min-h-0 flex-1 px-3 pt-3 pb-4 sm:px-6 sm:pt-5">
          <PreviewCanvas ref="canvas" />
        </div>
      </div>

      <!-- Properties -->
      <aside
        class="hidden w-[312px] shrink-0 overflow-y-auto border-l border-line bg-surface scroll-slim xl:block"
      >
        <div v-if="showTemplates" class="h-full">
          <div class="flex items-center justify-between px-4 pt-4 pb-1">
            <span class="panel-eyebrow">Шаблоны</span>
            <button
              type="button"
              class="grid size-6 place-items-center rounded-md text-ink-muted transition hover:bg-line-soft hover:text-ink"
              aria-label="Закрыть шаблоны"
              @click="showTemplates = false"
            >
              <AppIcon name="close" :size="14" />
            </button>
          </div>
          <TemplatePanel @close="showTemplates = false" />
        </div>

        <div v-else-if="selected">
          <div class="flex items-center gap-2 px-4 pt-4 pb-1">
            <AppIcon :name="SECTION_META[selected.type].icon" :size="15" class="text-brand" />
            <span class="panel-eyebrow">{{ SECTION_META[selected.type].label }}</span>
            <div class="flex-1" />
            <button
              type="button"
              class="rounded-md px-1.5 py-0.5 text-[12px] text-ink-muted transition hover:bg-line-soft hover:text-ink"
              @click="editor.select(null)"
            >
              Готово
            </button>
          </div>

          <p
            v-if="editor.unsupportedSectionIds.has(selected.id)"
            class="mx-4 mt-2 rounded-[10px] bg-raised px-2.5 py-2 text-[12px] text-ink-muted"
          >
            Этот шаблон не показывает такую секцию. Содержимое цело — смените шаблон, и оно
            вернётся.
          </p>

          <component :is="editorComponent" :key="selected.id" :section="selected" />
        </div>

        <ThemePanel v-else />
      </aside>
    </div>

    <!-- Small screens: panels as drawers -->
    <BaseDrawer :open="sectionsDrawer" title="Секции" side="left" @close="sectionsDrawer = false">
      <SectionList @select="selectSection" />
    </BaseDrawer>

    <BaseDrawer
      :open="settingsDrawer"
      :title="selected ? SECTION_META[selected.type].label : 'Оформление'"
      @close="settingsDrawer = false"
    >
      <component :is="editorComponent" v-if="selected" :key="selected.id" :section="selected" />
      <ThemePanel v-else />
    </BaseDrawer>

    <BaseDrawer
      v-if="!wideLayout"
      :open="showTemplates"
      title="Шаблоны"
      @close="showTemplates = false"
    >
      <TemplatePanel @close="showTemplates = false" />
    </BaseDrawer>
  </div>
</template>
