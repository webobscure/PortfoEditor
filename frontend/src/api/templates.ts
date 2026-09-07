import { http } from './client'

import type { Template, ThemeOptions } from '@/types'

export const templatesApi = {
  async list(): Promise<Template[]> {
    const { data } = await http.get<{ data: Template[] }>('/templates')

    return data.data
  },

  async themeOptions(): Promise<ThemeOptions> {
    const { data } = await http.get<{ data: ThemeOptions }>('/theme/options')

    return data.data
  },

  /** The stylesheet the preview iframe loads — the same file an export ships. */
  styleUrl(key: string): string {
    return `/api/templates/${encodeURIComponent(key)}/styles.css`
  },

  demoUrl(key: string, preset?: string): string {
    const query = preset ? `?preset=${encodeURIComponent(preset)}` : ''

    return `/api/templates/${encodeURIComponent(key)}/demo${query}`
  },
}
