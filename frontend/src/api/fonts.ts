import { http } from './client'

export interface FontFace {
  slug: string
  family: string
  subset: string
  weight: string
  style: string
  file: string
  range: string
}

export const fontsApi = {
  async faces(): Promise<FontFace[]> {
    const { data } = await http.get<{ data: { faces: FontFace[] } }>('/fonts')

    return data.data.faces
  },

  fileUrl(file: string): string {
    return `/api/fonts/${file}`
  },
}
