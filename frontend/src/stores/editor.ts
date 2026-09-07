import { defineStore } from 'pinia'
import { computed, ref, shallowRef } from 'vue'

import { isApiError } from '@/api/client'
import { portfoliosApi } from '@/api/portfolios'
import { sectionsApi } from '@/api/sections'
import { uploadsApi } from '@/api/uploads'
import { useCatalogStore } from '@/stores/catalog'
import { mergeSettings } from '@/templates/shared/theme'

import type {
  MediaItem,
  PartialThemeSettings,
  Portfolio,
  PortfolioMeta,
  SaveState,
  Section,
  SectionTypeKey,
  ThemeSettings,
} from '@/types'

const AUTOSAVE_DELAY = 700
const HISTORY_LIMIT = 60
const HISTORY_COALESCE_MS = 900

interface Snapshot {
  name: string
  templateKey: string
  settings: PartialThemeSettings
  meta: PortfolioMeta
  sections: Section[]
}

/**
 * Deep copy for history snapshots.
 *
 * A JSON round-trip rather than structuredClone: the values here are always
 * JSON — they came from and go back to a JSON column — and structuredClone
 * throws DataCloneError on Vue's reactive proxies.
 */
function clone<T>(value: T): T {
  return JSON.parse(JSON.stringify(value)) as T
}

/**
 * The editor's single source of truth.
 *
 * Everything the user does mutates this store optimistically and the preview
 * re-renders from it, so typing never waits on the network. Persistence is a
 * consequence: a debounced flush writes whatever is dirty, and the save
 * indicator reports what actually happened rather than what was attempted.
 */
export const useEditorStore = defineStore('editor', () => {
  const catalog = useCatalogStore()

  const portfolio = ref<Portfolio | null>(null)
  const sections = ref<Section[]>([])
  const media = ref<Record<number, MediaItem>>({})

  const selectedSectionId = ref<number | null>(null)
  const device = ref<'desktop' | 'tablet' | 'mobile'>('desktop')
  const loading = ref(false)
  const loadError = ref<string | null>(null)

  /** A template being tried on without committing to it. */
  const previewTemplateKey = ref<string | null>(null)

  const saveState = ref<SaveState>('idle')
  const saveError = ref<string | null>(null)

  const dirtySections = ref<Set<number>>(new Set())
  const dirtyPortfolio = ref(false)

  let saveTimer: number | null = null
  let retryTimer: number | null = null
  let flushing: Promise<void> | null = null
  let retryDelay = 1000

  // History is deliberately local and shallow: it is an editing convenience
  // for the current session, not a document version history.
  const past = shallowRef<Snapshot[]>([])
  const future = shallowRef<Snapshot[]>([])
  let lastCommitLabel = ''
  let lastCommitAt = 0

  // ------------------------------------------------------------ getters

  const activeTemplateKey = computed(
    () => previewTemplateKey.value ?? portfolio.value?.template_key ?? 'minimal',
  )

  const isPreviewingTemplate = computed(() => previewTemplateKey.value !== null)

  const effectiveSettings = computed<ThemeSettings>(() =>
    mergeSettings(portfolio.value?.settings ?? {}, catalog.defaultsFor(activeTemplateKey.value)),
  )

  const orderedSections = computed(() =>
    [...sections.value].sort((a, b) => a.position - b.position),
  )

  const selectedSection = computed(
    () => sections.value.find((section) => section.id === selectedSectionId.value) ?? null,
  )

  const supportedTypes = computed<SectionTypeKey[]>(
    () => catalog.template(activeTemplateKey.value)?.supported_sections ?? [],
  )

  /** Sections the current template cannot display. Their data is untouched. */
  const unsupportedSectionIds = computed(() => {
    const supported = new Set(supportedTypes.value)

    return new Set(orderedSections.value.filter((s) => !supported.has(s.type)).map((s) => s.id))
  })

  const canUndo = computed(() => past.value.length > 0)
  const canRedo = computed(() => future.value.length > 0)
  const hasPendingChanges = computed(() => dirtyPortfolio.value || dirtySections.value.size > 0)

  // ------------------------------------------------------------ loading

  async function load(id: number): Promise<void> {
    reset()
    loading.value = true

    try {
      const [loaded, portfolioMedia] = await Promise.all([
        portfoliosApi.get(id),
        uploadsApi.list(id),
      ])

      portfolio.value = loaded
      sections.value = loaded.sections ?? []
      media.value = Object.fromEntries(portfolioMedia.map((item) => [item.id, item]))
      selectedSectionId.value = null
    } catch (error) {
      loadError.value = isApiError(error) ? error.message : 'We could not open this portfolio.'
      throw error
    } finally {
      loading.value = false
    }
  }

  function reset(): void {
    cancelTimers()
    portfolio.value = null
    sections.value = []
    media.value = {}
    selectedSectionId.value = null
    previewTemplateKey.value = null
    saveState.value = 'idle'
    saveError.value = null
    loadError.value = null
    dirtySections.value = new Set()
    dirtyPortfolio.value = false
    past.value = []
    future.value = []
  }

  // ------------------------------------------------------------ history

  function snapshot(): Snapshot {
    return {
      name: portfolio.value?.name ?? '',
      templateKey: portfolio.value?.template_key ?? 'minimal',
      settings: clone(portfolio.value?.settings ?? {}),
      meta: clone(portfolio.value?.meta ?? {}),
      sections: clone(sections.value),
    }
  }

  /**
   * Record a history entry.
   *
   * Consecutive edits to the same field within a second collapse into one, so
   * undo steps back over a word rather than a keystroke.
   */
  function commit(label: string): void {
    const now = Date.now()
    const coalesce = label === lastCommitLabel && now - lastCommitAt < HISTORY_COALESCE_MS

    if (!coalesce) {
      past.value = [...past.value, snapshot()].slice(-HISTORY_LIMIT)
    }

    lastCommitLabel = label
    lastCommitAt = now
    future.value = []
  }

  function restore(state: Snapshot): void {
    if (!portfolio.value) return

    portfolio.value = {
      ...portfolio.value,
      name: state.name,
      template_key: state.templateKey,
      settings: clone(state.settings),
      meta: clone(state.meta),
    }
    sections.value = clone(state.sections)

    dirtyPortfolio.value = true
    sections.value.forEach((section) => dirtySections.value.add(section.id))
    scheduleSave()
  }

  function undo(): void {
    const previous = past.value[past.value.length - 1]

    if (!previous) return

    future.value = [snapshot(), ...future.value]
    past.value = past.value.slice(0, -1)
    lastCommitLabel = ''
    restore(previous)
  }

  function redo(): void {
    const next = future.value[0]

    if (!next) return

    past.value = [...past.value, snapshot()]
    future.value = future.value.slice(1)
    lastCommitLabel = ''
    restore(next)
  }

  // ------------------------------------------------------------ editing

  function select(id: number | null): void {
    selectedSectionId.value = id
  }

  function setDevice(next: 'desktop' | 'tablet' | 'mobile'): void {
    device.value = next
  }

  function rename(name: string): void {
    if (!portfolio.value) return

    commit('portfolio:name')
    portfolio.value = { ...portfolio.value, name }
    dirtyPortfolio.value = true
    scheduleSave()
  }

  function patchMeta(patch: Partial<PortfolioMeta>): void {
    if (!portfolio.value) return

    commit('portfolio:meta:' + Object.keys(patch).join(','))
    portfolio.value = { ...portfolio.value, meta: { ...portfolio.value.meta, ...patch } }
    dirtyPortfolio.value = true
    scheduleSave()
  }

  /** Write sparse theme overrides; template defaults fill in the rest. */
  function patchSettings<K extends keyof ThemeSettings>(
    group: K,
    values: Partial<ThemeSettings[K]>,
  ): void {
    if (!portfolio.value) return

    commit(`portfolio:settings:${String(group)}:${Object.keys(values).join(',')}`)

    const settings = clone(portfolio.value.settings ?? {}) as Record<string, unknown>
    settings[group as string] = { ...((settings[group as string] as object) ?? {}), ...values }

    portfolio.value = { ...portfolio.value, settings: settings as PartialThemeSettings }
    dirtyPortfolio.value = true
    scheduleSave()
  }

  function patchSectionData(id: number, patch: Record<string, unknown>): void {
    const section = sections.value.find((item) => item.id === id)

    if (!section) return

    commit(`section:${id}:${Object.keys(patch).join(',')}`)

    section.content = { ...section.content, ...patch }
    section.is_placeholder = false
    dirtySections.value.add(id)
    scheduleSave()
  }

  function setSectionEnabled(id: number, enabled: boolean): void {
    const section = sections.value.find((item) => item.id === id)

    if (!section) return

    commit(`section:${id}:enabled`)
    section.enabled = enabled
    dirtySections.value.add(id)
    scheduleSave()
  }

  async function addSection(type: SectionTypeKey): Promise<Section | null> {
    if (!portfolio.value) return null

    // Creation needs a server-assigned id before anything can reference it, so
    // it is the one mutation that is not optimistic.
    const section = await sectionsApi.create(portfolio.value.id, type)

    commit(`section:add:${type}`)
    sections.value = [...sections.value, section]
    selectedSectionId.value = section.id

    return section
  }

  async function deleteSection(id: number): Promise<void> {
    if (!portfolio.value) return

    const previous = sections.value
    commit(`section:delete:${id}`)
    sections.value = sections.value.filter((section) => section.id !== id)

    if (selectedSectionId.value === id) {
      selectedSectionId.value = null
    }

    dirtySections.value.delete(id)

    try {
      await sectionsApi.remove(portfolio.value.id, id)
    } catch (error) {
      sections.value = previous
      throw error
    }
  }

  async function reorder(orderedIds: number[]): Promise<void> {
    if (!portfolio.value) return

    commit('sections:reorder')

    const byId = new Map(sections.value.map((section) => [section.id, section]))
    sections.value = orderedIds
      .map((id, index) => {
        const section = byId.get(id)

        return section ? { ...section, position: index } : null
      })
      .filter((section): section is Section => section !== null)

    try {
      const updated = await sectionsApi.reorder(portfolio.value.id, orderedIds)
      sections.value = updated
    } catch (error) {
      // Re-fetching is more honest than guessing what the server ended up with.
      sections.value = await sectionsApi.list(portfolio.value.id)
      throw error
    }
  }

  // ----------------------------------------------------------- template

  function startTemplatePreview(key: string): void {
    previewTemplateKey.value = key
  }

  function cancelTemplatePreview(): void {
    previewTemplateKey.value = null
  }

  function applyTemplate(key: string): void {
    if (!portfolio.value) return

    commit('portfolio:template')
    portfolio.value = { ...portfolio.value, template_key: key }
    previewTemplateKey.value = null
    dirtyPortfolio.value = true
    scheduleSave()
  }

  // -------------------------------------------------------------- media

  function registerMedia(item: MediaItem): void {
    media.value = { ...media.value, [item.id]: item }
  }

  async function upload(file: File, onProgress?: (percent: number) => void): Promise<MediaItem> {
    if (!portfolio.value) throw new Error('No portfolio is open.')

    const item = await uploadsApi.upload(file, portfolio.value.id, onProgress)
    registerMedia(item)

    return item
  }

  // ----------------------------------------------------------- autosave

  function scheduleSave(): void {
    saveState.value = 'saving'

    if (saveTimer !== null) window.clearTimeout(saveTimer)

    saveTimer = window.setTimeout(() => {
      saveTimer = null
      void flush()
    }, AUTOSAVE_DELAY)
  }

  function cancelTimers(): void {
    if (saveTimer !== null) window.clearTimeout(saveTimer)
    if (retryTimer !== null) window.clearTimeout(retryTimer)
    saveTimer = null
    retryTimer = null
  }

  /**
   * Persist everything currently dirty.
   *
   * Concurrent callers share one in-flight promise, so a Cmd+S during an
   * autosave does not produce two overlapping write sequences.
   */
  function flush(): Promise<void> {
    if (flushing) return flushing

    flushing = (async () => {
      if (!portfolio.value) return
      if (!dirtyPortfolio.value && dirtySections.value.size === 0) {
        saveState.value = 'saved'

        return
      }

      saveState.value = 'saving'
      saveError.value = null

      const portfolioWasDirty = dirtyPortfolio.value
      const sectionIds = [...dirtySections.value]

      dirtyPortfolio.value = false
      dirtySections.value = new Set()

      try {
        if (portfolioWasDirty) {
          const current = portfolio.value
          const updated = await portfoliosApi.update(current.id, {
            name: current.name,
            template_key: current.template_key,
            settings: current.settings,
            meta: current.meta,
          })

          // Keep the server's derived fields without stomping newer local edits.
          portfolio.value = {
            ...portfolio.value,
            slug: updated.slug,
            effective_settings: updated.effective_settings,
            updated_at: updated.updated_at,
          }
        }

        for (const id of sectionIds) {
          const section = sections.value.find((item) => item.id === id)

          if (!section) continue

          await sectionsApi.update(portfolio.value.id, id, {
            content: section.content,
            enabled: section.enabled,
          })
        }

        retryDelay = 1000
        saveState.value = hasPendingChanges.value ? 'saving' : 'saved'
      } catch (error) {
        // Put the work back so a retry — manual or automatic — picks it up.
        dirtyPortfolio.value = dirtyPortfolio.value || portfolioWasDirty
        sectionIds.forEach((id) => dirtySections.value.add(id))

        saveState.value = 'error'
        saveError.value = isApiError(error) ? error.message : 'Could not save changes'
        scheduleRetry()
      } finally {
        flushing = null
      }
    })()

    return flushing
  }

  function scheduleRetry(): void {
    if (retryTimer !== null) window.clearTimeout(retryTimer)

    retryTimer = window.setTimeout(() => {
      retryTimer = null
      void flush()
    }, retryDelay)

    retryDelay = Math.min(retryDelay * 2, 30_000)
  }

  /** Manual retry from the save indicator. */
  function retryNow(): Promise<void> {
    retryDelay = 1000

    return flush()
  }

  async function saveNow(): Promise<void> {
    if (saveTimer !== null) {
      window.clearTimeout(saveTimer)
      saveTimer = null
    }

    await flush()
  }

  return {
    portfolio,
    sections,
    media,
    selectedSectionId,
    device,
    loading,
    loadError,
    saveState,
    saveError,
    previewTemplateKey,

    activeTemplateKey,
    isPreviewingTemplate,
    effectiveSettings,
    orderedSections,
    selectedSection,
    supportedTypes,
    unsupportedSectionIds,
    canUndo,
    canRedo,
    hasPendingChanges,

    load,
    reset,
    select,
    setDevice,
    rename,
    patchMeta,
    patchSettings,
    patchSectionData,
    setSectionEnabled,
    addSection,
    deleteSection,
    reorder,
    startTemplatePreview,
    cancelTemplatePreview,
    applyTemplate,
    registerMedia,
    upload,
    undo,
    redo,
    saveNow,
    retryNow,
  }
})
