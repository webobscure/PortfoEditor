import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'

import { useCatalogStore } from './catalog'
import { useEditorStore } from './editor'

import type { Portfolio, Section } from '@/types'

/**
 * The editor store is where correctness actually lives: optimistic state,
 * debounced persistence, and an undo stack over both. These tests cover the
 * behaviours that are easy to break and hard to notice.
 */

const updatePortfolio = vi.fn()
const updateSection = vi.fn()
const reorderSections = vi.fn()
const createSection = vi.fn()
const removeSection = vi.fn()

vi.mock('@/api/portfolios', () => ({
  portfoliosApi: {
    get: vi.fn(),
    update: (...args: unknown[]) => updatePortfolio(...args),
    previewUrl: (id: number) => `/api/portfolios/${id}/preview`,
  },
}))

vi.mock('@/api/sections', () => ({
  sectionsApi: {
    list: vi.fn(async () => []),
    create: (...args: unknown[]) => createSection(...args),
    update: (...args: unknown[]) => updateSection(...args),
    remove: (...args: unknown[]) => removeSection(...args),
    reorder: (...args: unknown[]) => reorderSections(...args),
  },
}))

vi.mock('@/api/uploads', () => ({
  uploadsApi: { list: vi.fn(async () => []), upload: vi.fn() },
}))

function section(
  id: number,
  type: Section['type'],
  content: Record<string, unknown> = {},
): Section {
  return {
    id,
    type,
    label: type,
    position: id - 1,
    enabled: true,
    content,
    settings: {},
    is_placeholder: true,
    updated_at: null,
  }
}

function portfolio(): Portfolio {
  return {
    id: 7,
    name: 'Alex Morgan',
    slug: 'alex-morgan',
    template_key: 'minimal',
    status: 'draft',
    preset: 'designer',
    settings: {},
    effective_settings: {
      colors: {
        accent: '#000000',
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
    },
    meta: {},
    created_at: null,
    updated_at: null,
  }
}

function primed() {
  const editor = useEditorStore()
  const catalog = useCatalogStore()

  catalog.templates = [
    {
      key: 'minimal',
      name: 'Minimal',
      description: '',
      tags: [],
      supported_sections: ['hero', 'about', 'projects'],
      default_settings: { typography: { heading_font: 'manrope' } },
      color_schemes: [],
      capabilities: {},
      style_url: '',
      demo_url: '',
    },
    {
      key: 'developer-dark',
      name: 'Developer',
      description: '',
      tags: [],
      supported_sections: ['hero', 'about'],
      default_settings: { colors: { background: '#0b0c10' } },
      color_schemes: [],
      capabilities: {},
      style_url: '',
      demo_url: '',
    },
  ]
  catalog.loaded = true

  editor.portfolio = portfolio()
  editor.sections = [
    section(1, 'hero', { name: 'Alex' }),
    section(2, 'about', { body: 'Hello' }),
    section(3, 'projects', { items: [] }),
  ]

  return editor
}

beforeEach(() => {
  setActivePinia(createPinia())
  vi.useFakeTimers()
  updatePortfolio.mockReset().mockResolvedValue(portfolio())
  updateSection.mockReset().mockResolvedValue(undefined)
  reorderSections.mockReset()
  createSection.mockReset()
  removeSection.mockReset().mockResolvedValue(undefined)
})

afterEach(() => {
  vi.useRealTimers()
})

describe('autosave', () => {
  it('does not write on every keystroke', async () => {
    const editor = primed()

    for (const name of ['A', 'Al', 'Ale', 'Alex']) {
      editor.patchSectionData(1, { name })
    }

    expect(updateSection).not.toHaveBeenCalled()
    expect(editor.saveState).toBe('saving')

    await vi.advanceTimersByTimeAsync(800)

    // Four edits to one section collapse into a single request.
    expect(updateSection).toHaveBeenCalledTimes(1)
    expect(updateSection.mock.calls[0][2].content.name).toBe('Alex')
    expect(editor.saveState).toBe('saved')
  })

  it('writes each dirty section once and the portfolio once', async () => {
    const editor = primed()

    editor.patchSectionData(1, { name: 'Alex' })
    editor.patchSectionData(2, { body: 'About me' })
    editor.rename('Portfolio')

    await vi.advanceTimersByTimeAsync(800)

    expect(updateSection).toHaveBeenCalledTimes(2)
    expect(updatePortfolio).toHaveBeenCalledTimes(1)
  })

  it('reports a failure, keeps the work, and retries', async () => {
    const editor = primed()

    updateSection.mockRejectedValueOnce({ status: 500, message: 'Boom', errors: {} })

    editor.patchSectionData(1, { name: 'Alex' })
    await vi.advanceTimersByTimeAsync(800)

    expect(editor.saveState).toBe('error')
    expect(editor.hasPendingChanges).toBe(true)

    // The scheduled retry succeeds and the indicator recovers on its own.
    await vi.advanceTimersByTimeAsync(1200)

    expect(editor.saveState).toBe('saved')
    expect(editor.hasPendingChanges).toBe(false)
  })

  it('clears the placeholder flag as soon as the user types', () => {
    const editor = primed()

    editor.patchSectionData(1, { name: 'Alex' })

    expect(editor.sections[0].is_placeholder).toBe(false)
  })
})

describe('undo and redo', () => {
  it('steps back over a burst of typing as one change', async () => {
    const editor = primed()

    editor.patchSectionData(1, { name: 'A' })
    editor.patchSectionData(1, { name: 'Al' })
    editor.patchSectionData(1, { name: 'Alex' })

    expect(editor.canUndo).toBe(true)

    editor.undo()
    await nextTick()

    expect(editor.sections[0].content.name).toBe('Alex')
    expect(editor.canRedo).toBe(true)
  })

  it('restores an earlier value and can move forward again', async () => {
    const editor = primed()

    editor.patchSectionData(1, { name: 'First' })
    await vi.advanceTimersByTimeAsync(1000)
    editor.patchSectionData(1, { name: 'Second' })

    editor.undo()
    expect(editor.sections[0].content.name).toBe('First')

    editor.redo()
    expect(editor.sections[0].content.name).toBe('Second')
  })

  it('marks restored state dirty so an undo is persisted too', async () => {
    const editor = primed()

    editor.patchSectionData(1, { name: 'First' })
    await vi.advanceTimersByTimeAsync(800)
    updateSection.mockClear()

    editor.undo()
    await vi.advanceTimersByTimeAsync(800)

    expect(updateSection).toHaveBeenCalled()
  })
})

describe('theme settings', () => {
  it('stores sparse overrides and merges template defaults for the rest', () => {
    const editor = primed()

    editor.patchSettings('colors', { accent: '#ff0055' })

    expect(editor.portfolio?.settings).toEqual({ colors: { accent: '#ff0055' } })
    expect(editor.effectiveSettings.colors.accent).toBe('#ff0055')
    expect(editor.effectiveSettings.typography.heading_font).toBe('manrope')
  })
})

describe('template switching', () => {
  it('previews without touching stored state', () => {
    const editor = primed()

    editor.startTemplatePreview('developer-dark')

    expect(editor.activeTemplateKey).toBe('developer-dark')
    expect(editor.portfolio?.template_key).toBe('minimal')
    expect(editor.hasPendingChanges).toBe(false)

    editor.cancelTemplatePreview()

    expect(editor.activeTemplateKey).toBe('minimal')
  })

  it('keeps unsupported sections and only marks them as hidden', () => {
    const editor = primed()

    editor.applyTemplate('developer-dark')

    expect(editor.sections).toHaveLength(3)
    expect([...editor.unsupportedSectionIds]).toEqual([3])
  })
})

describe('reordering', () => {
  it('applies the new order immediately and keeps the server result', async () => {
    const editor = primed()

    reorderSections.mockResolvedValue([
      { ...section(3, 'projects'), position: 0 },
      { ...section(2, 'about'), position: 1 },
      { ...section(1, 'hero'), position: 2 },
    ])

    await editor.reorder([3, 2, 1])

    expect(editor.orderedSections.map((s) => s.id)).toEqual([3, 2, 1])
    expect(reorderSections).toHaveBeenCalledWith(7, [3, 2, 1])
  })
})
