import { onBeforeUnmount, onMounted } from 'vue'

export interface ShortcutMap {
  save?: () => void
  undo?: () => void
  redo?: () => void
}

function isTypingTarget(target: EventTarget | null): boolean {
  const element = target as HTMLElement | null

  if (!element) return false

  return (
    element.tagName === 'INPUT' ||
    element.tagName === 'TEXTAREA' ||
    element.tagName === 'SELECT' ||
    element.isContentEditable
  )
}

/**
 * Editor keyboard shortcuts.
 *
 * Cmd/Ctrl+S forces a save; Cmd/Ctrl+Z and Shift+Cmd/Ctrl+Z drive history.
 * Undo is ignored while typing so the browser's own field-level undo keeps
 * working, which is what people expect inside a text input.
 */
export function useShortcuts(handlers: ShortcutMap) {
  function onKeydown(event: KeyboardEvent): void {
    const meta = event.metaKey || event.ctrlKey

    if (!meta) return

    const key = event.key.toLowerCase()

    if (key === 's') {
      event.preventDefault()
      handlers.save?.()

      return
    }

    if (key === 'z' && !isTypingTarget(event.target)) {
      event.preventDefault()

      if (event.shiftKey) {
        handlers.redo?.()
      } else {
        handlers.undo?.()
      }
    }
  }

  onMounted(() => window.addEventListener('keydown', onKeydown))
  onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
}
