import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ToastTone = 'info' | 'success' | 'error'

export interface Toast {
  id: number
  tone: ToastTone
  title: string
  description?: string
  action?: { label: string; run: () => void }
}

let nextId = 1

/**
 * Errors surface as toasts or inline messages — never alert().
 */
export const useToastStore = defineStore('toast', () => {
  const toasts = ref<Toast[]>([])

  function push(toast: Omit<Toast, 'id'>, timeout = 5000): number {
    const id = nextId++
    toasts.value.push({ ...toast, id })

    if (timeout > 0) {
      window.setTimeout(() => dismiss(id), timeout)
    }

    return id
  }

  function dismiss(id: number): void {
    toasts.value = toasts.value.filter((toast) => toast.id !== id)
  }

  const success = (title: string, description?: string) =>
    push({ tone: 'success', title, description })
  const info = (title: string, description?: string) => push({ tone: 'info', title, description })
  const error = (title: string, description?: string, action?: Toast['action']) =>
    push({ tone: 'error', title, description, action }, 8000)

  return { toasts, push, dismiss, success, info, error }
})
