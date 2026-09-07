import { computed } from 'vue'

import { templatesApi } from '@/api/templates'
import { useCatalogStore } from '@/stores/catalog'
import { useEditorStore } from '@/stores/editor'
import { buildFontCss } from '@/templates/shared/fonts'
import { renderDocument, type RenderContext, type RenderImage } from '@/templates/shared/renderer'
import { compileTheme, fontSlugs } from '@/templates/shared/theme'

/**
 * Builds the preview document from editor state.
 *
 * This is the same context the backend assembles for an export — same sections,
 * same merged settings, same font faces — with two differences that only make
 * sense in an editor: assets are addressed by URL instead of by archive-relative
 * path, and the document carries the click-to-select instrumentation.
 */
export function usePreviewDocument() {
  const editor = useEditorStore()
  const catalog = useCatalogStore()

  const images = computed<Record<number, RenderImage>>(() => {
    const map: Record<number, RenderImage> = {}

    for (const item of Object.values(editor.media)) {
      map[item.id] = { url: item.url, width: item.width, height: item.height, alt: '' }
    }

    return map
  })

  const context = computed<RenderContext | null>(() => {
    const portfolio = editor.portfolio

    if (!portfolio || !catalog.loaded) return null

    const templateKey = editor.activeTemplateKey
    const template = catalog.template(templateKey)
    const settings = portfolio.settings ?? {}
    const merged = { ...(template?.default_settings ?? {}), ...settings }

    // Sections the template cannot display are dropped from the document, not
    // from the store: switching back brings them straight back.
    const supported = new Set(template?.supported_sections ?? [])

    return {
      mode: 'preview',
      templateKey,
      portfolio: {
        name: portfolio.name,
        slug: portfolio.slug,
        meta: portfolio.meta as unknown as Record<string, unknown>,
      },
      sections: editor.orderedSections
        .filter((section) => section.enabled && supported.has(section.type))
        .map((section) => ({
          id: section.id,
          type: section.type,
          data: section.content,
          settings: section.settings,
        })),
      images: images.value,
      themeCss: compileTheme(editor.effectiveSettings, catalog.fonts),
      fontCss: buildFontCss(catalog.faces, fontSlugs(merged), '/api/fonts'),
      styleHref: templatesApi.styleUrl(templateKey),
      scriptSrc: null,
      ogImageUrl: null,
      selectedSectionId: editor.selectedSectionId,
      interactive: true,
    }
  })

  const html = computed(() => (context.value ? renderDocument(context.value) : ''))

  return { context, html }
}
