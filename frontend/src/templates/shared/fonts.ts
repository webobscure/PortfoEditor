import type { FontFace } from '@/api/fonts'

/**
 * Builds the @font-face block the preview injects.
 *
 * Mirrors App\Services\Templates\FontCatalog::faceCss, including whitespace,
 * so the <style> element in the preview is identical to the one written into an
 * exported index.html apart from the URL base.
 */
export function buildFontCss(faces: FontFace[], slugs: string[], baseUrl: string): string {
  const base = baseUrl.replace(/\/+$/, '')
  const wanted = new Set(slugs.filter(Boolean))

  return faces
    .filter((face) => wanted.has(face.slug))
    .map((face) => {
      const range = face.range !== '' ? `\n  unicode-range: ${face.range};` : ''

      return [
        '@font-face {',
        `  font-family: '${face.family}';`,
        `  font-style: ${face.style};`,
        `  font-weight: ${face.weight};`,
        '  font-display: swap;',
        `  src: url('${base}/${face.file}') format('woff2');${range}`,
        '}',
      ].join('\n')
    })
    .join('\n')
}
