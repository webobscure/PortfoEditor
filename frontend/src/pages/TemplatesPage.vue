<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import TemplateCard from '@/components/templates/TemplateCard.vue'
import TemplatePreviewModal from '@/components/templates/TemplatePreviewModal.vue'
import SkeletonBlock from '@/components/ui/SkeletonBlock.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { useCatalogStore } from '@/stores/catalog'

import type { Template } from '@/types'

const catalog = useCatalogStore()
const router = useRouter()

const previewing = ref<Template | null>(null)

onMounted(() => catalog.load())

const templates = computed(() => catalog.templates)

function useTemplate(template: Template): void {
  previewing.value = null
  router.push({ name: 'portfolio-create', query: { template: template.key } })
}
</script>

<template>
  <AppLayout>
    <div class="max-w-2xl">
      <h1 class="text-[26px] font-semibold tracking-[-0.025em]">Шаблоны</h1>
      <p class="mt-1 text-[13.5px] text-ink-muted">
        Каждый — самостоятельный дизайн, а не смена палитры. Содержимое переносится между ними без
        потерь, так что выбор никогда не окончательный.
      </p>
    </div>

    <div v-if="!catalog.loaded" class="mt-8 grid gap-6 md:grid-cols-2">
      <div v-for="n in 2" :key="n" class="panel overflow-hidden">
        <SkeletonBlock height="300px" rounded="0" />
        <div class="space-y-2 p-4">
          <SkeletonBlock height="16px" width="40%" />
          <SkeletonBlock height="12px" width="80%" />
        </div>
      </div>
    </div>

    <div v-else class="mt-8 grid gap-6 md:grid-cols-2">
      <TemplateCard
        v-for="template in templates"
        :key="template.key"
        :template="template"
        @preview="previewing = template"
        @choose="useTemplate(template)"
      />
    </div>

    <TemplatePreviewModal
      :open="previewing !== null"
      :template="previewing"
      @close="previewing = null"
      @choose="useTemplate"
    />
  </AppLayout>
</template>
