<script setup lang="ts">
import Sortable from 'sortablejs'
import { computed, onBeforeUnmount, ref, watch } from 'vue'

import AppIcon from '@/components/ui/AppIcon.vue'
import BasePopover from '@/components/ui/BasePopover.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import { SECTION_META, SECTION_ORDER } from '@/editor/sections/registry'
import { useCatalogStore } from '@/stores/catalog'
import { useEditorStore } from '@/stores/editor'
import { useToastStore } from '@/stores/toast'

import SectionRow from './SectionRow.vue'

import type { Section, SectionTypeKey } from '@/types'

/**
 * The sections rail.
 *
 * Reordering is a drag on the handle; the store applies the new order
 * optimistically and re-reads from the server if the write fails, because a
 * silently wrong order is worse than a visible reload.
 */
const emit = defineEmits<{ select: [number] }>()

const editor = useEditorStore()
const catalog = useCatalogStore()
const toast = useToastStore()

const listElement = ref<HTMLElement | null>(null)
const pendingDelete = ref<Section | null>(null)
const busy = ref(false)

let sortable: Sortable | null = null

const availableTypes = computed<SectionTypeKey[]>(() => {
  const existing = new Set(editor.sections.map((section) => section.type))

  // Which types may only appear once is a server rule, published through
  // /api/theme/options rather than duplicated here.
  const singletons = new Set(
    catalog.sectionTypes.filter((info) => info.singleton).map((info) => info.type),
  )

  return SECTION_ORDER.filter((type) => !(singletons.has(type) && existing.has(type)))
})

function mountSortable(): void {
  if (!listElement.value || sortable) return

  sortable = Sortable.create(listElement.value, {
    handle: '.js-drag-handle',
    animation: 160,
    easing: 'cubic-bezier(0.22, 0.8, 0.3, 1)',
    ghostClass: 'opacity-40',
    onEnd: async () => {
      const ids = Array.from(listElement.value?.children ?? [])
        .map((child) => Number((child as HTMLElement).dataset.sectionId))
        .filter((id) => Number.isFinite(id))

      try {
        await editor.reorder(ids)
      } catch {
        toast.error('Не удалось изменить порядок', 'Прежний порядок восстановлен.')
      }
    },
  })
}

watch(listElement, mountSortable, { immediate: true })

onBeforeUnmount(() => {
  sortable?.destroy()
  sortable = null
})

async function addSection(type: SectionTypeKey, close: () => void): Promise<void> {
  close()

  try {
    const section = await editor.addSection(type)

    if (section) emit('select', section.id)
  } catch {
    toast.error('Не удалось добавить секцию', 'Попробуйте ещё раз.')
  }
}

function hasContent(section: Section): boolean {
  if (section.is_placeholder) return false

  return Object.values(section.content).some((value) => {
    if (Array.isArray(value)) return value.length > 0

    return typeof value === 'string' && value.trim() !== ''
  })
}

function requestDelete(section: Section): void {
  // Only confirm when there is something to lose.
  if (!hasContent(section)) {
    void remove(section)

    return
  }

  pendingDelete.value = section
}

async function remove(section: Section): Promise<void> {
  busy.value = true

  try {
    await editor.deleteSection(section.id)
    pendingDelete.value = null
  } catch {
    toast.error('Не удалось удалить секцию', 'Попробуйте ещё раз.')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="flex h-full flex-col">
    <div class="flex items-center justify-between px-4 pt-4 pb-2">
      <span class="panel-eyebrow">Секции</span>

      <BasePopover align="left" width="w-56">
        <template #trigger="{ toggle }">
          <button
            type="button"
            class="grid size-6 place-items-center rounded-md text-ink-muted transition hover:bg-line-soft hover:text-ink"
            aria-label="Добавить секцию"
            @click="toggle"
          >
            <AppIcon name="plus" :size="15" />
          </button>
        </template>

        <template #default="{ close }">
          <p class="px-2 pt-1 pb-1.5 text-[11px] text-ink-faint">Добавить секцию</p>
          <button
            v-for="type in availableTypes"
            :key="type"
            type="button"
            class="flex w-full items-start gap-2 rounded-[8px] px-2 py-1.5 text-left transition hover:bg-line-soft"
            @click="addSection(type, close)"
          >
            <AppIcon :name="SECTION_META[type].icon" :size="15" class="mt-0.5 text-ink-muted" />
            <span class="min-w-0">
              <span class="block text-[13px]">{{ SECTION_META[type].label }}</span>
              <span class="block truncate text-[11.5px] text-ink-faint">
                {{ SECTION_META[type].description }}
              </span>
            </span>
          </button>
        </template>
      </BasePopover>
    </div>

    <div ref="listElement" class="scroll-slim flex-1 space-y-0.5 overflow-y-auto px-2 pb-4">
      <div
        v-for="section in editor.orderedSections"
        :key="section.id"
        :data-section-id="section.id"
      >
        <SectionRow
          :section="section"
          :active="editor.selectedSectionId === section.id"
          :unsupported="editor.unsupportedSectionIds.has(section.id)"
          @select="emit('select', section.id)"
          @toggle="editor.setSectionEnabled(section.id, !section.enabled)"
          @remove="requestDelete(section)"
        />
      </div>
    </div>

    <ConfirmDialog
      :open="pendingDelete !== null"
      title="Удалить эту секцию?"
      :description="`Содержимое секции «${pendingDelete ? SECTION_META[pendingDelete.type].label : ''}» будет удалено. Отменить это нельзя.`"
      confirm-label="Удалить секцию"
      destructive
      :busy="busy"
      @close="pendingDelete = null"
      @confirm="pendingDelete && remove(pendingDelete)"
    />
  </div>
</template>
