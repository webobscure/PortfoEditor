/**
 * Shapes mirrored from the API resources. Kept in one place so a change to a
 * backend resource shows up as a type error rather than as undefined at runtime.
 */

export type SectionTypeKey =
  | 'hero'
  | 'about'
  | 'experience'
  | 'education'
  | 'skills'
  | 'projects'
  | 'services'
  | 'achievements'
  | 'contacts'
  | 'social_links'

export type DeviceKey = 'desktop' | 'tablet' | 'mobile'

export type SaveState = 'idle' | 'saving' | 'saved' | 'error'

export interface User {
  id: number
  name: string
  email: string
  initials: string
}

export interface ThemeColors {
  accent: string
  background: string
  surface: string
  text: string
  muted: string
  border: string
}

export interface ThemeTypography {
  heading_font: string
  body_font: string
  scale: 'compact' | 'default' | 'large'
}

export interface ThemeLayout {
  section_spacing: 'compact' | 'default' | 'spacious'
  content_width: 'narrow' | 'default' | 'wide'
}

export interface ThemeSettings {
  colors: ThemeColors
  typography: ThemeTypography
  layout: ThemeLayout
  buttons: { style: 'rounded' | 'square' | 'pill' }
  template: Record<string, string>
}

/** Sparse overrides: only what the user changed. */
export type PartialThemeSettings = {
  [K in keyof ThemeSettings]?: Partial<ThemeSettings[K]>
}

export interface PortfolioMeta {
  title?: string
  description?: string
  og_image_media_id?: number | null
}

export interface Section {
  id: number
  type: SectionTypeKey
  label: string
  position: number
  enabled: boolean
  /** The section payload. Named `content` because the API envelope owns `data`. */
  content: Record<string, unknown>
  settings: Record<string, unknown>
  is_placeholder: boolean
  updated_at: string | null
}

export interface Portfolio {
  id: number
  name: string
  slug: string
  template_key: string
  status: 'draft' | 'published'
  preset: string | null
  settings: PartialThemeSettings
  effective_settings: ThemeSettings
  meta: PortfolioMeta
  sections?: Section[]
  created_at: string | null
  updated_at: string | null
}

export interface ColorScheme {
  key: string
  name: string
  colors: ThemeColors
}

export interface Template {
  key: string
  name: string
  description: string
  tags: string[]
  supported_sections: SectionTypeKey[]
  default_settings: PartialThemeSettings
  color_schemes: ColorScheme[]
  capabilities: { script?: boolean }
  style_url: string
  demo_url: string
}

export interface FontFamily {
  key: string
  name: string
  category: 'sans' | 'serif' | 'mono'
  stack: string
  roles: string[]
}

export interface SectionTypeInfo {
  type: SectionTypeKey
  label: string
  singleton: boolean
  defaults: Record<string, unknown>
}

export interface Preset {
  key: string
  name: string
  description: string
  template: string
}

export interface ThemeOptions {
  fonts: FontFamily[]
  typography_scales: ThemeTypography['scale'][]
  section_spacings: ThemeLayout['section_spacing'][]
  content_widths: ThemeLayout['content_width'][]
  button_styles: ThemeSettings['buttons']['style'][]
  defaults: ThemeSettings
  section_types: SectionTypeInfo[]
  social_platforms: string[]
  presets: Preset[]
}

export interface MediaItem {
  id: number
  url: string
  mime: string
  size: number
  width: number | null
  height: number | null
  original_name: string
  created_at: string | null
}

export interface PortfolioExport {
  id: number
  portfolio_id: number
  status: 'queued' | 'processing' | 'completed' | 'failed'
  size: number | null
  error: string | null
  download_url: string | null
  created_at: string | null
  completed_at: string | null
}

/** A validation failure from Laravel, normalised by the API client. */
export interface ApiError {
  message: string
  status: number
  errors: Record<string, string[]>
}
