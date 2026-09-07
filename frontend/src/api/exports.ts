import { http } from './client'

import type { PortfolioExport } from '@/types'

export const exportsApi = {
  async create(portfolioId: number): Promise<PortfolioExport> {
    const { data } = await http.post<{ data: PortfolioExport }>(`/portfolios/${portfolioId}/export`)

    return data.data
  },

  async get(id: number): Promise<PortfolioExport> {
    const { data } = await http.get<{ data: PortfolioExport }>(`/exports/${id}`)

    return data.data
  },
}
