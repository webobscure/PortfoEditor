import { readFileSync, readdirSync } from 'node:fs'
import { join } from 'node:path'

import { describe, expect, it } from 'vitest'

import { buildFontCss } from './fonts'
import { renderDocument, type RenderContext } from './renderer'
import { compileTheme, fontSlugs, mergeSettings } from './theme'

import type { FontFace } from '@/api/fonts'
import type { FontFamily, PartialThemeSettings, SectionTypeKey } from '@/types'

/**
 * Render parity.
 *
 * The backend writes a golden HTML document for a fixed fixture; this suite
 * renders the same fixture with the TypeScript renderer that drives the live
 * preview and asserts the two are byte-identical. Preview/export drift becomes
 * a failing test instead of a support ticket.
 *
 * Regenerate after an intentional renderer change:
 *   cd backend && php artisan render:fixtures
 */
const FIXTURES = join(__dirname, '../../../../backend/tests/Fixtures')

interface Fixture {
  name: string
  slug: string
  meta: Record<string, unknown>
  settings: PartialThemeSettings
  sections: { type: SectionTypeKey; content: Record<string, unknown> }[]
}

interface FontFixture {
  families: FontFamily[]
  faces: FontFace[]
  template_defaults: Record<string, PartialThemeSettings>
  compiled: string
}

const portfolio: Fixture = JSON.parse(readFileSync(join(FIXTURES, 'portfolio.json'), 'utf8'))
const fonts: FontFixture = JSON.parse(readFileSync(join(FIXTURES, 'fonts.json'), 'utf8'))

const templateKeys = readdirSync(join(FIXTURES, 'golden'))
  .filter((file) => file.endsWith('.html'))
  .map((file) => file.replace(/\.html$/, ''))

function contextFor(templateKey: string): RenderContext {
  const merged: PartialThemeSettings = {
    ...(fonts.template_defaults[templateKey] ?? {}),
    ...portfolio.settings,
  }

  const settings = mergeSettings(portfolio.settings, fonts.template_defaults[templateKey] ?? {})

  return {
    mode: 'export',
    templateKey,
    portfolio: { name: portfolio.name, slug: portfolio.slug, meta: portfolio.meta },
    sections: portfolio.sections.map((section, index) => ({
      id: index + 1,
      type: section.type,
      data: section.content,
      settings: {},
    })),
    images: {},
    themeCss: compileTheme(settings, fonts.families),
    fontCss: buildFontCss(fonts.faces, fontSlugs(merged), 'assets/fonts'),
    styleHref: 'assets/css/styles.css',
    scriptSrc: null,
    ogImageUrl: null,
  }
}

describe('render parity with the PHP renderer', () => {
  for (const templateKey of templateKeys) {
    it(`matches the golden document for ${templateKey}`, () => {
      const golden = readFileSync(join(FIXTURES, 'golden', `${templateKey}.html`), 'utf8')

      expect(renderDocument(contextFor(templateKey))).toBe(golden)
    })
  }

  it('covers every installed template', () => {
    expect(templateKeys.length).toBeGreaterThanOrEqual(3)
  })
})

describe('preview-only behaviour', () => {
  it('adds the editing marker and selection class only when interactive', () => {
    const base = contextFor('minimal')

    expect(renderDocument(base)).not.toContain('pf-editing')

    const interactive = renderDocument({ ...base, interactive: true, selectedSectionId: 1 })

    expect(interactive).toContain('pf-editing')
    expect(interactive).toContain('is-selected')
  })

  it('stamps a section id on every rendered section so clicks can be routed', () => {
    const html = renderDocument(contextFor('minimal'))
    const ids = [...html.matchAll(/data-pf-section="(\d+)"/g)].map((match) => match[1])

    expect(ids.length).toBeGreaterThan(3)
    expect(new Set(ids).size).toBe(ids.length)
  })
})
