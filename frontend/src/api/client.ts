import axios, { AxiosError, type AxiosInstance } from 'axios'

import type { ApiError } from '@/types'

/**
 * The single HTTP entry point.
 *
 * Components never call axios or fetch directly: they call a module in this
 * folder. That keeps the CSRF handshake, the error shape and the 401 handling
 * in one place instead of scattered across dozens of call sites.
 */
export const http: AxiosInstance = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

let csrfReady: Promise<void> | null = null

/**
 * Sanctum's SPA flow needs the XSRF cookie before the first mutating request.
 * The promise is cached so a burst of autosaves triggers one handshake.
 */
export function ensureCsrfCookie(): Promise<void> {
  csrfReady ??= axios
    .get('/sanctum/csrf-cookie', { withCredentials: true })
    .then(() => undefined)
    .catch((error) => {
      csrfReady = null
      throw error
    })

  return csrfReady
}

export function resetCsrfCookie(): void {
  csrfReady = null
}

/** Callback invoked when the session has expired, wired up by the auth store. */
let onUnauthenticated: (() => void) | null = null

export function setUnauthenticatedHandler(handler: () => void): void {
  onUnauthenticated = handler
}

http.interceptors.request.use(async (config) => {
  const method = (config.method ?? 'get').toLowerCase()

  if (['post', 'put', 'patch', 'delete'].includes(method)) {
    await ensureCsrfCookie()
  }

  return config
})

http.interceptors.response.use(
  (response) => response,
  (error: AxiosError<{ message?: string; errors?: Record<string, string[]> }>) => {
    const status = error.response?.status ?? 0

    // 419 means the CSRF token expired; the next request re-handshakes.
    if (status === 419) {
      resetCsrfCookie()
    }

    if (status === 401 && !error.config?.url?.includes('/user')) {
      onUnauthenticated?.()
    }

    return Promise.reject(toApiError(error))
  },
)

export function toApiError(
  error: AxiosError<{ message?: string; errors?: Record<string, string[]> }>,
): ApiError {
  const status = error.response?.status ?? 0
  const data = error.response?.data

  return {
    status,
    message: data?.message ?? fallbackMessage(status),
    errors: data?.errors ?? {},
  }
}

function fallbackMessage(status: number): string {
  if (status === 0) return 'Не удалось связаться с сервером. Проверьте соединение.'
  if (status === 403) return 'Нет доступа к этому.'
  if (status === 404) return 'Не найдено.'
  if (status === 429) return 'Слишком много запросов. Попробуйте через минуту.'
  if (status >= 500) return 'Что-то сломалось на нашей стороне.'

  return 'Что-то пошло не так.'
}

export function isApiError(value: unknown): value is ApiError {
  return typeof value === 'object' && value !== null && 'status' in value && 'message' in value
}
