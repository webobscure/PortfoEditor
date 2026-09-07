import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { fontsApi, type FontFace } from '@/api/fonts'
import { templatesApi } from '@/api/templates'

import type { Template, ThemeOptions } from '@/types'

/**
 * Server-provided vocabulary: templates, fonts, theme option lists.
 *
 * Fetched once and shared. Keeping these on the server rather than duplicating
 * them in the frontend is what stops the TypeScript theme compiler from
 * disagreeing with the PHP one about what a font stack is.
 */
export const useCatalogStore = defineStore('catalog', () => {
  const templates = ref<Template[]>([])
  const options = ref<ThemeOptions | null>(null)
  const faces = ref<FontFace[]>([])
  const loading = ref(false)
  const loaded = ref(false)

  const fonts = computed(() => options.value?.fonts ?? [])
  const presets = computed(() => options.value?.presets ?? [])
  const sectionTypes = computed(() => options.value?.section_types ?? [])

  async function load(): Promise<void> {
    if (loaded.value || loading.value) return

    loading.value = true

    try {
      const [templateList, themeOptions, fontFaces] = await Promise.all([
        templatesApi.list(),
        templatesApi.themeOptions(),
        fontsApi.faces(),
      ])

      templates.value = templateList
      options.value = themeOptions
      faces.value = fontFaces
      loaded.value = true
    } finally {
      loading.value = false
    }
  }

  function template(key: string): Template | undefined {
    return templates.value.find((item) => item.key === key)
  }

  function defaultsFor(key: string) {
    return template(key)?.default_settings ?? {}
  }

  return {
    templates,
    options,
    faces,
    fonts,
    presets,
    sectionTypes,
    loading,
    loaded,
    load,
    template,
    defaultsFor,
  }
})
