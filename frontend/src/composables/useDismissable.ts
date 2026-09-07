import { onBeforeUnmount, watch, type Ref } from 'vue'

/**
 * Close-on-outside-click and close-on-Escape for popovers and dropdowns.
 */
export function useDismissable(
  open: Ref<boolean>,
  elements: Ref<HTMLElement | null>[],
  close: () => void,
) {
  function onPointerDown(event: PointerEvent): void {
    const target = event.target as Node

    if (elements.some((element) => element.value?.contains(target))) return

    close()
  }

  function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
      event.stopPropagation()
      close()
    }
  }

  function bind(): void {
    document.addEventListener('pointerdown', onPointerDown, true)
    document.addEventListener('keydown', onKeydown)
  }

  function unbind(): void {
    document.removeEventListener('pointerdown', onPointerDown, true)
    document.removeEventListener('keydown', onKeydown)
  }

  watch(open, (value) => (value ? bind() : unbind()), { immediate: true })

  onBeforeUnmount(unbind)
}
