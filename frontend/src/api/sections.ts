import { http } from './client'

import type { Section, SectionTypeKey } from '@/types'

export const sectionsApi = {
  async list(portfolioId: number): Promise<Section[]> {
    const { data } = await http.get<{ data: Section[] }>(`/portfolios/${portfolioId}/sections`)

    return data.data
  },

  async create(portfolioId: number, type: SectionTypeKey, position?: number): Promise<Section> {
    const { data } = await http.post<{ data: Section }>(`/portfolios/${portfolioId}/sections`, {
      type,
      ...(position === undefined ? {} : { position }),
    })

    return data.data
  },

  async update(
    portfolioId: number,
    sectionId: number,
    payload: { content?: Record<string, unknown>; enabled?: boolean },
  ): Promise<Section> {
    const { data } = await http.patch<{ data: Section }>(
      `/portfolios/${portfolioId}/sections/${sectionId}`,
      payload,
    )

    return data.data
  },

  async remove(portfolioId: number, sectionId: number): Promise<void> {
    await http.delete(`/portfolios/${portfolioId}/sections/${sectionId}`)
  },

  async reorder(portfolioId: number, sectionIds: number[]): Promise<Section[]> {
    const { data } = await http.post<{ data: Section[] }>(
      `/portfolios/${portfolioId}/sections/reorder`,
      { section_ids: sectionIds },
    )

    return data.data
  },
}
