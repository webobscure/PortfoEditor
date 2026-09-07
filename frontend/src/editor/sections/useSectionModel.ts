import { computed } from 'vue'

import { useEditorStore } from '@/stores/editor'

import type { Section } from '@/types'

/**
 * Two-way bindings over a section's JSON payload.
 *
 * Every write goes through the store so it lands in history and in the autosave
 * queue; components never mutate `section.data` directly.
 */
export function useSectionModel(section: () => Section) {
  const editor = useEditorStore()

  /** A ref-like binding for one top-level key of the section payload. */
  function field<T>(key: string, fallback: T) {
    return computed<T>({
      get: () => (section().content[key] as T) ?? fallback,
      set: (value) => editor.patchSectionData(section().id, { [key]: value }),
    })
  }

  /** A binding over a list of objects, with helpers for the usual operations. */
  function list<T extends Record<string, unknown>>(key: string) {
    const items = computed<T[]>(() => ((section().content[key] as T[]) ?? []).slice())

    function write(next: T[]): void {
      editor.patchSectionData(section().id, { [key]: next })
    }

    return {
      items,
      write,
      add: (item: T) => write([...items.value, item]),
      remove: (index: number) => write(items.value.filter((_, i) => i !== index)),
      update: (index: number, patch: Partial<T>) =>
        write(items.value.map((item, i) => (i === index ? { ...item, ...patch } : item))),
      move: (from: number, to: number) => {
        const next = items.value.slice()
        const [moved] = next.splice(from, 1)

        if (!moved) return

        next.splice(to, 0, moved)
        write(next)
      },
    }
  }

  return { editor, field, list }
}
