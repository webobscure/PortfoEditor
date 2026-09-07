<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import PortfolioCard from '@/components/portfolio/PortfolioCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import SkeletonBlock from '@/components/ui/SkeletonBlock.vue'
import { useExport } from '@/composables/useExport'
import AppLayout from '@/layouts/AppLayout.vue'
import { useCatalogStore } from '@/stores/catalog'
import { usePortfoliosStore } from '@/stores/portfolios'
import { useToastStore } from '@/stores/toast'

import type { Portfolio } from '@/types'

const portfolios = usePortfoliosStore()
const catalog = useCatalogStore()
const toast = useToastStore()
const router = useRouter()
const { download } = useExport()

const pendingDelete = ref<Portfolio | null>(null)
const deleting = ref(false)

onMounted(() => {
  portfolios.load()
  catalog.load()
})

async function confirmDelete(): Promise<void> {
  if (!pendingDelete.value) return

  deleting.value = true

  try {
    await portfolios.remove(pendingDelete.value.id)
    toast.success('Portfolio deleted')
    pendingDelete.value = null
  } catch {
    toast.error('Could not delete', 'The portfolio is still there. Please try again.')
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[26px] font-semibold tracking-[-0.025em]">Your portfolios</h1>
        <p class="mt-1 text-[13.5px] text-ink-muted">
          Edit, preview, or download any of them as a static site.
        </p>
      </div>

      <BaseButton variant="primary" icon="plus" @click="router.push({ name: 'portfolio-create' })">
        Create portfolio
      </BaseButton>
    </div>

    <div
      v-if="portfolios.loading && !portfolios.loaded"
      class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
    >
      <div v-for="n in 3" :key="n" class="panel overflow-hidden">
        <SkeletonBlock height="180px" rounded="0" />
        <div class="space-y-2 p-3.5">
          <SkeletonBlock height="14px" width="60%" />
          <SkeletonBlock height="12px" width="40%" />
        </div>
      </div>
    </div>

    <div v-else-if="portfolios.isEmpty" class="panel mt-8">
      <EmptyState
        icon="sparkle"
        title="Create your first portfolio"
        description="Pick what you do, answer three questions, and choose a template. You will have a real site to look at in under a minute."
      >
        <BaseButton
          variant="primary"
          size="lg"
          icon="plus"
          @click="router.push({ name: 'portfolio-create' })"
        >
          Create portfolio
        </BaseButton>
      </EmptyState>
    </div>

    <div v-else class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <PortfolioCard
        v-for="portfolio in portfolios.items"
        :key="portfolio.id"
        :portfolio="portfolio"
        @download="download(portfolio.id, portfolio.name)"
        @delete="pendingDelete = $event"
      />
    </div>

    <ConfirmDialog
      :open="pendingDelete !== null"
      title="Delete this portfolio?"
      :description="`“${pendingDelete?.name}” and everything in it will be removed. This cannot be undone.`"
      confirm-label="Delete"
      destructive
      :busy="deleting"
      @close="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </AppLayout>
</template>
