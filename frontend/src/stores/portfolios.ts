import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { portfoliosApi, type CreatePortfolioPayload } from '@/api/portfolios'

import type { Portfolio } from '@/types'

/** The dashboard's list. The editor owns its own copy of a single portfolio. */
export const usePortfoliosStore = defineStore('portfolios', () => {
  const items = ref<Portfolio[]>([])
  const loading = ref(false)
  const loaded = ref(false)

  const isEmpty = computed(() => loaded.value && items.value.length === 0)

  async function load(force = false): Promise<void> {
    if (loading.value) return
    if (loaded.value && !force) return

    loading.value = true

    try {
      items.value = await portfoliosApi.list()
      loaded.value = true
    } finally {
      loading.value = false
    }
  }

  async function create(payload: CreatePortfolioPayload): Promise<Portfolio> {
    const portfolio = await portfoliosApi.create(payload)
    items.value = [portfolio, ...items.value]

    return portfolio
  }

  async function remove(id: number): Promise<void> {
    const previous = items.value
    // Optimistic: the card disappears immediately and comes back if the
    // request fails, which is the behaviour that feels correct on a grid.
    items.value = items.value.filter((item) => item.id !== id)

    try {
      await portfoliosApi.remove(id)
    } catch (error) {
      items.value = previous
      throw error
    }
  }

  function upsert(portfolio: Portfolio): void {
    const index = items.value.findIndex((item) => item.id === portfolio.id)

    if (index === -1) {
      items.value = [portfolio, ...items.value]
    } else {
      items.value[index] = portfolio
    }
  }

  return { items, loading, loaded, isEmpty, load, create, remove, upsert }
})
