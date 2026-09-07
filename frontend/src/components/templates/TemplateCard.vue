<script setup lang="ts">
import { templatesApi } from '@/api/templates'
import SitePreviewFrame from '@/components/portfolio/SitePreviewFrame.vue'
import AppIcon from '@/components/ui/AppIcon.vue'

import type { Template } from '@/types'

/**
 * Deliberately large. A template card that is too small to read the type on
 * tells you nothing about the template, which is the one thing this screen
 * exists to communicate.
 */
defineProps<{ template: Template; preset?: string; selected?: boolean; actionLabel?: string }>()

const emit = defineEmits<{ preview: []; choose: [] }>()
</script>

<template>
  <article
    class="group panel relative overflow-hidden transition-all duration-200 ease-[cubic-bezier(0.22,0.8,0.3,1)] hover:-translate-y-0.5 hover:shadow-pop"
    :class="selected ? 'ring-2 ring-brand ring-offset-2 ring-offset-canvas' : ''"
  >
    <div class="relative border-b border-line-soft">
      <SitePreviewFrame :src="templatesApi.demoUrl(template.key, preset)" :ratio="0.72" />

      <!-- Overlay actions appear on hover and on keyboard focus within. -->
      <div
        class="absolute inset-0 flex items-center justify-center gap-2 bg-[#17171a]/45 opacity-0 backdrop-blur-[1px] transition-opacity duration-200 group-hover:opacity-100 group-focus-within:opacity-100"
      >
        <button
          type="button"
          class="inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-white/95 px-3.5 text-[13px] font-medium text-ink shadow-soft transition hover:bg-white"
          @click="emit('preview')"
        >
          <AppIcon name="eye" :size="15" />
          Предпросмотр
        </button>
        <button
          type="button"
          class="inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-brand px-3.5 text-[13px] font-medium text-white shadow-soft transition hover:bg-brand-hover"
          @click="emit('choose')"
        >
          {{ actionLabel ?? 'Взять этот шаблон' }}
          <AppIcon name="arrowRight" :size="15" />
        </button>
      </div>
    </div>

    <div class="p-4">
      <div class="flex items-center gap-2">
        <h3 class="text-[15px] font-semibold tracking-[-0.015em]">{{ template.name }}</h3>
        <span
          v-if="selected"
          class="inline-flex items-center gap-1 rounded-full bg-brand-soft px-2 py-0.5 text-[11px] font-medium text-brand"
        >
          <AppIcon name="check" :size="12" />
          Текущий
        </span>
      </div>
      <p class="mt-1 text-[13px] leading-relaxed text-ink-muted">{{ template.description }}</p>

      <ul class="mt-3 flex flex-wrap gap-1.5">
        <li
          v-for="tag in template.tags"
          :key="tag"
          class="rounded-full border border-line bg-raised px-2 py-0.5 text-[11.5px] text-ink-muted capitalize"
        >
          {{ tag }}
        </li>
      </ul>
    </div>
  </article>
</template>
