import type { FontFamily, PartialThemeSettings, ThemeSettings } from '@/types'

/**
 * Theme compilation, mirroring App\Services\Templates\ThemeCompiler and
 * App\Support\Color.
 *
 * Both sides emit the same custom properties in the same order with the same
 * rounding, which is what lets the golden-file tests compare a PHP-rendered
 * document to a TypeScript-rendered one byte for byte.
 */

export const TYPE_SCALES: Record<string, number> = { compact: 0.94, default: 1.0, large: 1.08 }
export const SPACING_SCALES: Record<string, number> = {
  compact: 0.72,
  default: 1.0,
  spacious: 1.32,
}
export const CONTENT_WIDTHS: Record<string, number> = { narrow: 720, default: 960, wide: 1180 }
export const BUTTON_RADII: Record<string, string> = {
  rounded: '10px',
  square: '2px',
  pill: '999px',
}

export const THEME_DEFAULTS: ThemeSettings = {
  colors: {
    accent: '#4f46e5',
    background: '#ffffff',
    surface: '#f7f7f8',
    text: '#101014',
    muted: '#6b6b76',
    border: '#e6e6ea',
  },
  typography: { heading_font: 'inter', body_font: 'inter', scale: 'default' },
  layout: { section_spacing: 'default', content_width: 'default' },
  buttons: { style: 'rounded' },
  template: {},
}

// ------------------------------------------------------------------ colour

function toRgb(hex: string): [number, number, number] {
  let value = hex.trim().replace(/^#/, '')

  if (value.length === 3) {
    value = value[0] + value[0] + value[1] + value[1] + value[2] + value[2]
  }

  if (!/^[0-9a-fA-F]{6}$/.test(value)) return [0, 0, 0]

  return [
    parseInt(value.slice(0, 2), 16),
    parseInt(value.slice(2, 4), 16),
    parseInt(value.slice(4, 6), 16),
  ]
}

function clamp(value: number): number {
  return Math.max(0, Math.min(255, value))
}

function toHex(r: number, g: number, b: number): string {
  const part = (n: number) => clamp(n).toString(16).padStart(2, '0')

  return `#${part(r)}${part(g)}${part(b)}`
}

/** PHP rounds half away from zero; JavaScript's Math.round rounds half up. */
function phpRound(value: number): number {
  return value < 0 ? -Math.round(-value) : Math.round(value)
}

export function rgba(hex: string, alpha: number): string {
  const [r, g, b] = toRgb(hex)
  const formatted = alpha.toFixed(2).replace(/0+$/, '').replace(/\.$/, '')

  return `rgba(${r}, ${g}, ${b}, ${formatted})`
}

export function scaleColor(hex: string, factor: number): string {
  const [r, g, b] = toRgb(hex)

  return toHex(phpRound(r * factor), phpRound(g * factor), phpRound(b * factor))
}

export function luminance(hex: string): number {
  const [r, g, b] = toRgb(hex)
  const channel = (value: number) => {
    const v = value / 255

    return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4)
  }

  return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b)
}

export function readableOn(hex: string): string {
  return luminance(hex) > 0.45 ? '#101014' : '#ffffff'
}

// ---------------------------------------------------------------- merging

/**
 * Schema defaults, then template defaults, then the user's sparse overrides —
 * the same precedence the backend applies, so the preview and the saved
 * document agree even before the next autosave lands.
 */
export function mergeSettings(
  overrides: PartialThemeSettings = {},
  templateDefaults: PartialThemeSettings = {},
): ThemeSettings {
  const merged: ThemeSettings = {
    colors: { ...THEME_DEFAULTS.colors },
    typography: { ...THEME_DEFAULTS.typography },
    layout: { ...THEME_DEFAULTS.layout },
    buttons: { ...THEME_DEFAULTS.buttons },
    template: {},
  }

  for (const layer of [templateDefaults, overrides]) {
    for (const [group, values] of Object.entries(layer ?? {})) {
      if (!values || typeof values !== 'object') continue
      if (!(group in merged)) continue

      const target = merged[group as keyof ThemeSettings] as Record<string, unknown>

      for (const [key, value] of Object.entries(values as Record<string, unknown>)) {
        if (value === null || value === undefined || value === '') continue
        target[key] = value
      }
    }
  }

  return merged
}

// -------------------------------------------------------------- compiling

function formatNumber(value: number): string {
  const formatted = value.toFixed(2).replace(/0+$/, '').replace(/\.$/, '')

  return formatted === '' ? '0' : formatted
}

function stackFor(fonts: FontFamily[], key: string): string {
  return (
    fonts.find((font) => font.key === key)?.stack ??
    fonts.find((font) => font.key === 'inter')?.stack ??
    'system-ui, sans-serif'
  )
}

/** The custom properties every template is written against, in fixed order. */
export function themeVariables(
  settings: PartialThemeSettings,
  fonts: FontFamily[],
): Record<string, string> {
  const merged = mergeSettings(settings)
  const accent = merged.colors.accent

  return {
    '--pf-accent': accent,
    '--pf-accent-strong': scaleColor(accent, 0.86),
    '--pf-accent-soft': rgba(accent, 0.12),
    '--pf-accent-contrast': readableOn(accent),
    '--pf-bg': merged.colors.background,
    '--pf-surface': merged.colors.surface,
    '--pf-text': merged.colors.text,
    '--pf-muted': merged.colors.muted,
    '--pf-border': merged.colors.border,
    '--pf-font-heading': stackFor(fonts, merged.typography.heading_font),
    '--pf-font-body': stackFor(fonts, merged.typography.body_font),
    '--pf-type-scale': formatNumber(TYPE_SCALES[merged.typography.scale] ?? 1),
    '--pf-space-scale': formatNumber(SPACING_SCALES[merged.layout.section_spacing] ?? 1),
    '--pf-content-width': `${CONTENT_WIDTHS[merged.layout.content_width] ?? 960}px`,
    '--pf-btn-radius': BUTTON_RADII[merged.buttons.style] ?? '10px',
  }
}

export function compileTheme(settings: PartialThemeSettings, fonts: FontFamily[]): string {
  const lines = Object.entries(themeVariables(settings, fonts)).map(
    ([name, value]) => `  ${name}: ${value};`,
  )

  return `:root {\n${lines.join('\n')}\n}`
}

export function fontSlugs(settings: PartialThemeSettings): string[] {
  const merged = mergeSettings(settings)

  return [...new Set([merged.typography.heading_font, merged.typography.body_font])]
}
