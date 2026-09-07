import { onBeforeUnmount, onMounted } from 'vue'

/**
 * Warns before a reload or tab close while a save is still outstanding.
 *
 * The editor already retries on its own, so this only covers the case where the
 * page goes away before the retry can land.
 */
export function useUnsavedGuard(hasPending: () => boolean) {
  function onBeforeUnload(event: BeforeUnloadEvent): void {
    if (!hasPending()) return

    event.preventDefault()
    event.returnValue = ''
  }

  onMounted(() => window.addEventListener('beforeunload', onBeforeUnload))
  onBeforeUnmount(() => window.removeEventListener('beforeunload', onBeforeUnload))
}
