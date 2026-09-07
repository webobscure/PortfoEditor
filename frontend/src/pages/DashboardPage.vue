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
    toast.success('Портфолио удалено')
    pendingDelete.value = null
  } catch {
    toast.error('Не удалось удалить', 'Портфолио на месте. Попробуйте ещё раз.')
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[26px] font-semibold tracking-[-0.025em]">Мои портфолио</h1>
        <p class="mt-1 text-[13.5px] text-ink-muted">
          Редактируйте, смотрите предпросмотр или скачивайте любое из них как статический сайт.
        </p>
      </div>

      <BaseButton variant="primary" icon="plus" @click="router.push({ name: 'portfolio-create' })">
        Создать портфолио
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
        title="Создайте первое портфолио"
        description="Выберите, чем занимаетесь, ответьте на три вопроса и подберите шаблон. Меньше чем через минуту будет готовый сайт."
      >
        <BaseButton
          variant="primary"
          size="lg"
          icon="plus"
          @click="router.push({ name: 'portfolio-create' })"
        >
          Создать портфолио
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
      title="Удалить это портфолио?"
      :description="`«${pendingDelete?.name}» и всё его содержимое будет удалено. Отменить это нельзя.`"
      confirm-label="Удалить"
      destructive
      :busy="deleting"
      @close="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </AppLayout>
</template>
