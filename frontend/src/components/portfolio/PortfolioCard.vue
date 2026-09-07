<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

import { portfoliosApi } from '@/api/portfolios'
import AppIcon from '@/components/ui/AppIcon.vue'
import BasePopover from '@/components/ui/BasePopover.vue'
import { useCatalogStore } from '@/stores/catalog'

import SitePreviewFrame from './SitePreviewFrame.vue'

import type { Portfolio } from '@/types'

const props = defineProps<{ portfolio: Portfolio }>()
const emit = defineEmits<{ download: [Portfolio]; delete: [Portfolio] }>()

const catalog = useCatalogStore()

const templateName = computed(
  () => catalog.template(props.portfolio.template_key)?.name ?? props.portfolio.template_key,
)

const edited = computed(() => {
  const value = props.portfolio.updated_at

  if (!value) return 'just now'

  const minutes = Math.round((Date.now() - new Date(value).getTime()) / 60000)

  if (minutes < 1) return 'just now'
  if (minutes < 60) return `${minutes} min ago`
  if (minutes < 60 * 24) return `${Math.round(minutes / 60)} h ago`
  if (minutes < 60 * 24 * 7) return `${Math.round(minutes / 1440)} d ago`

  return new Date(value).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
})

const previewUrl = computed(() => portfoliosApi.previewUrl(props.portfolio.id))
</script>

<template>
  <article
    class="group panel overflow-hidden transition-all duration-200 ease-[cubic-bezier(0.22,0.8,0.3,1)] hover:-translate-y-0.5 hover:shadow-pop"
  >
    <RouterLink
      :to="{ name: 'editor', params: { id: portfolio.id } }"
      class="block border-b border-line-soft"
      :aria-label="`Edit ${portfolio.name}`"
    >
      <SitePreviewFrame :src="previewUrl" :ratio="0.66" />
    </RouterLink>

    <div class="flex items-start gap-2 p-3.5">
      <div class="min-w-0 flex-1">
        <RouterLink
          :to="{ name: 'editor', params: { id: portfolio.id } }"
          class="block truncate text-[14px] font-medium tracking-[-0.01em] hover:text-brand"
        >
          {{ portfolio.name }}
        </RouterLink>
        <p class="mt-0.5 flex items-center gap-1.5 truncate text-[12px] text-ink-muted">
          <span>{{ templateName }}</span>
          <span aria-hidden="true">·</span>
          <span>Edited {{ edited }}</span>
        </p>
      </div>

      <span
        class="mt-0.5 rounded-full px-2 py-0.5 text-[11px] font-medium"
        :class="
          portfolio.status === 'published'
            ? 'bg-positive-soft text-positive'
            : 'bg-line-soft text-ink-muted'
        "
      >
        {{ portfolio.status === 'published' ? 'Published' : 'Draft' }}
      </span>
    </div>

    <div class="flex items-center gap-1 border-t border-line-soft px-2.5 py-2">
      <RouterLink
        :to="{ name: 'editor', params: { id: portfolio.id } }"
        class="inline-flex h-7 items-center gap-1.5 rounded-[9px] px-2.5 text-[12.5px] font-medium text-ink-soft transition hover:bg-line-soft hover:text-ink"
      >
        <AppIcon name="sliders" :size="14" />
        Edit
      </RouterLink>

      <a
        :href="previewUrl"
        target="_blank"
        rel="noopener"
        class="inline-flex h-7 items-center gap-1.5 rounded-[9px] px-2.5 text-[12.5px] font-medium text-ink-soft transition hover:bg-line-soft hover:text-ink"
      >
        <AppIcon name="external" :size="14" />
        Preview
      </a>

      <button
        type="button"
        class="inline-flex h-7 items-center gap-1.5 rounded-[9px] px-2.5 text-[12.5px] font-medium text-ink-soft transition hover:bg-line-soft hover:text-ink"
        @click="emit('download', portfolio)"
      >
        <AppIcon name="download" :size="14" />
        Download
      </button>

      <div class="flex-1" />

      <BasePopover align="right" width="w-44">
        <template #trigger="{ toggle }">
          <button
            type="button"
            class="grid size-7 place-items-center rounded-[9px] text-ink-muted transition hover:bg-line-soft hover:text-ink"
            aria-label="More actions"
            @click="toggle"
          >
            <AppIcon name="dots" :size="15" />
          </button>
        </template>

        <template #default="{ close }">
          <button
            type="button"
            class="flex w-full items-center gap-2 rounded-[8px] px-2 py-1.5 text-left text-[13px] text-danger transition hover:bg-danger-soft"
            @click="
              () => {
                close()
                emit('delete', portfolio)
              }
            "
          >
            <AppIcon name="trash" :size="15" />
            Delete portfolio
          </button>
        </template>
      </BasePopover>
    </div>
  </article>
</template>
