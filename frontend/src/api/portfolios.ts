import { http } from './client'

import type { PartialThemeSettings, Portfolio, PortfolioMeta } from '@/types'

export interface CreatePortfolioPayload {
  name: string
  preset?: string
  template_key?: string
  profile?: { name?: string; title?: string; intro?: string }
}

export interface UpdatePortfolioPayload {
  name?: string
  template_key?: string
  status?: 'draft' | 'published'
  settings?: PartialThemeSettings
  meta?: PortfolioMeta
}

export const portfoliosApi = {
  async list(): Promise<Portfolio[]> {
    const { data } = await http.get<{ data: Portfolio[] }>('/portfolios')

    return data.data
  },

  async get(id: number): Promise<Portfolio> {
    const { data } = await http.get<{ data: Portfolio }>(`/portfolios/${id}`)

    return data.data
  },

  async create(payload: CreatePortfolioPayload): Promise<Portfolio> {
    const { data } = await http.post<{ data: Portfolio }>('/portfolios', payload)

    return data.data
  },

  async update(id: number, payload: UpdatePortfolioPayload): Promise<Portfolio> {
    const { data } = await http.patch<{ data: Portfolio }>(`/portfolios/${id}`, payload)

    return data.data
  },

  async remove(id: number): Promise<void> {
    await http.delete(`/portfolios/${id}`)
  },

  previewUrl(id: number, templateKey?: string): string {
    const query = templateKey ? `?template=${encodeURIComponent(templateKey)}` : ''

    return `/api/portfolios/${id}/preview${query}`
  },
}
