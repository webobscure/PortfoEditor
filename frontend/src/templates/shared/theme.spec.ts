import { readFileSync } from 'node:fs'
import { join } from 'node:path'

import { describe, expect, it } from 'vitest'

import { compileTheme, mergeSettings, readableOn, rgba, scaleColor } from './theme'

import type { FontFamily, PartialThemeSettings } from '@/types'

const FIXTURES = join(__dirname, '../../../../backend/tests/Fixtures')
const fonts: { families: FontFamily[]; defaults: PartialThemeSettings; compiled: string } =
  JSON.parse(readFileSync(join(FIXTURES, 'fonts.json'), 'utf8'))

describe('theme compiler', () => {
  it('produces the same custom properties as the PHP compiler', () => {
    expect(compileTheme(fonts.defaults, fonts.families)).toBe(fonts.compiled)
  })

  it('layers user overrides on top of template defaults', () => {
    const merged = mergeSettings(
      { colors: { accent: '#ff0055' } },
      {
        colors: { accent: '#000000', background: '#111111' },
        typography: { heading_font: 'sora' },
      },
    )

    expect(merged.colors.accent).toBe('#ff0055')
    expect(merged.colors.background).toBe('#111111')
    expect(merged.typography.heading_font).toBe('sora')
  })

  it('ignores empty override values so a cleared field falls back', () => {
    const merged = mergeSettings({ colors: { accent: '' } }, { colors: { accent: '#123456' } })

    expect(merged.colors.accent).toBe('#123456')
  })
})

describe('colour maths', () => {
  it('matches the PHP helpers', () => {
    expect(rgba('#4f46e5', 0.12)).toBe('rgba(79, 70, 229, 0.12)')
    expect(scaleColor('#4f46e5', 0.86)).toBe('#443cc5')
    expect(readableOn('#ffffff')).toBe('#101014')
    expect(readableOn('#0b0c10')).toBe('#ffffff')
  })
})
