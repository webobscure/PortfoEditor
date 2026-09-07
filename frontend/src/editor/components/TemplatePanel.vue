<script setup lang="ts">
import { computed } from 'vue'

import { portfoliosApi } from '@/api/portfolios'
import SitePreviewFrame from '@/components/portfolio/SitePreviewFrame.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useCatalogStore } from '@/stores/catalog'
import { useEditorStore } from '@/stores/editor'

/**
 * Template switching, previewed against the user's own content.
 *
 * Selecting a template only changes what the preview renders. Nothing is
 * written until "Apply" — and even then no section is touched, so a template
 * that does not support a section hides it rather than deleting it.
 */
const editor = useEditorStore()
const catalog = useCatalogStore()

const emit = defineEmits<{ close: [] }>()

const currentKey = computed(() => editor.portfolio?.template_key ?? '')

const unsupportedCount = computed(() => editor.unsupportedSectionIds.size)

// Russian agreement changes with the count, so the noun and the verb are both
// chosen from the plural category rather than glued together from fragments.
const plural = new Intl.PluralRules('ru')

const unsupportedNotice = computed(() => {
  const count = unsupportedCount.value

  const phrase = {
    one: 'секция не отображается',
    few: 'секции не отображаются',
    many: 'секций не отображается',
    other: 'секции не отображается',
    zero: 'секций не отображается',
    two: 'секции не отображаются',
  }[plural.select(count)]

  return `${count} ${phrase} в этом шаблоне.`
})

function preview(key: string): void {
  if (key === currentKey.value) {
    editor.cancelTemplatePreview()

    return
  }

  editor.startTemplatePreview(key)
}

function apply(): void {
  if (!editor.previewTemplateKey) return

  editor.applyTemplate(editor.previewTemplateKey)
  emit('close')
}

function cancel(): void {
  editor.cancelTemplatePreview()
  emit('close')
}
</script>

<template>
  <div class="flex h-full flex-col">
    <div class="scroll-slim flex-1 space-y-3 overflow-y-auto p-3">
      <button
        v-for="template in catalog.templates"
        :key="template.key"
        type="button"
        class="block w-full overflow-hidden rounded-[12px] border text-left transition-all duration-200 hover:shadow-soft"
        :class="
          editor.activeTemplateKey === template.key
            ? 'border-brand ring-1 ring-brand-ring'
            : 'border-line'
        "
        :aria-pressed="editor.activeTemplateKey === template.key"
        @click="preview(template.key)"
      >
        <SitePreviewFrame
          v-if="editor.portfolio"
          :src="portfoliosApi.previewUrl(editor.portfolio.id, template.key)"
          :ratio="0.7"
        />

        <span class="flex items-center gap-2 px-3 py-2.5">
          <span class="min-w-0 flex-1">
            <span class="block text-[13px] font-medium">{{ template.name }}</span>
            <span class="block truncate text-[11.5px] text-ink-muted">
              {{ template.description }}
            </span>
          </span>
          <AppIcon v-if="currentKey === template.key" name="check" :size="15" class="text-brand" />
        </span>
      </button>
    </div>

    <div class="border-t border-line-soft p-3">
      <p
        v-if="editor.isPreviewingTemplate && unsupportedCount > 0"
        class="mb-2 flex items-start gap-1.5 rounded-[10px] bg-raised px-2.5 py-2 text-[12px] text-ink-muted"
      >
        <AppIcon name="info" :size="14" class="mt-px shrink-0" />
        <span>
          {{ unsupportedNotice }}
          Ничего не удаляется — вернёте шаблон, вернётся и содержимое.
        </span>
      </p>

      <div class="flex gap-2">
        <BaseButton block :disabled="!editor.isPreviewingTemplate" @click="cancel">
          Отмена
        </BaseButton>
        <BaseButton variant="primary" block :disabled="!editor.isPreviewingTemplate" @click="apply">
          Применить шаблон
        </BaseButton>
      </div>
    </div>
  </div>
</template>
