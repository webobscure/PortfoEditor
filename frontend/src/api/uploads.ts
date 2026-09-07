import { http } from './client'

import type { MediaItem } from '@/types'

export const uploadsApi = {
  /** Every image attached to a portfolio, so the editor can resolve stored ids. */
  async list(portfolioId: number): Promise<MediaItem[]> {
    const { data } = await http.get<{ data: MediaItem[] }>(`/portfolios/${portfolioId}/media`)

    return data.data
  },

  async upload(
    file: File,
    portfolioId?: number,
    onProgress?: (percent: number) => void,
  ): Promise<MediaItem> {
    const form = new FormData()
    form.append('file', file)

    if (portfolioId) {
      form.append('portfolio_id', String(portfolioId))
    }

    const { data } = await http.post<{ data: MediaItem }>('/uploads', form, {
      onUploadProgress: (event) => {
        if (!onProgress || !event.total) return
        onProgress(Math.round((event.loaded / event.total) * 100))
      },
    })

    return data.data
  },

  async remove(id: number): Promise<void> {
    await http.delete(`/media/${id}`)
  },
}
