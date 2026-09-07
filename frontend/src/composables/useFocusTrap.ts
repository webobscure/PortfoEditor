import { nextTick, onBeforeUnmount, watch, type Ref } from 'vue'

const FOCUSABLE = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled]):not([type="hidden"])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',')

/**
 * Keeps Tab inside a dialog while it is open, and restores focus to whatever
 * opened it on close.
 *
 * Without this, Tab walks into the page behind the overlay and the dialog is
 * unusable by keyboard — which is why it is a composable rather than something
 * each dialog reimplements.
 */
export function useFocusTrap(
  container: Ref<HTMLElement | null>,
  active: Ref<boolean>,
  onEscape?: () => void,
) {
  let previouslyFocused: HTMLElement | null = null

  function focusable(): HTMLElement[] {
    if (!container.value) return []

    return Array.from(container.value.querySelectorAll<HTMLElement>(FOCUSABLE)).filter(
      (element) => element.offsetParent !== null || element === document.activeElement,
    )
  }

  function onKeydown(event: KeyboardEvent): void {
    if (!active.value) return

    if (event.key === 'Escape') {
      event.stopPropagation()
      onEscape?.()

      return
    }

    if (event.key !== 'Tab') return

    const elements = focusable()

    if (elements.length === 0) {
      event.preventDefault()

      return
    }

    const first = elements[0]
    const last = elements[elements.length - 1]
    const current = document.activeElement as HTMLElement | null

    if (event.shiftKey && (current === first || !container.value?.contains(current))) {
      event.preventDefault()
      last.focus()
    } else if (!event.shiftKey && current === last) {
      event.preventDefault()
      first.focus()
    }
  }

  function teardown(): void {
    document.removeEventListener('keydown', onKeydown, true)
  }

  watch(
    active,
    async (open) => {
      if (open) {
        previouslyFocused = document.activeElement as HTMLElement | null
        document.addEventListener('keydown', onKeydown, true)
        await nextTick()
        ;(focusable()[0] ?? container.value)?.focus()
      } else {
        teardown()
        previouslyFocused?.focus?.()
        previouslyFocused = null
      }
    },
    { immediate: true },
  )

  onBeforeUnmount(teardown)
}
