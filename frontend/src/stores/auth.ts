import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { authApi } from '@/api/auth'
import { setUnauthenticatedHandler } from '@/api/client'

import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const ready = ref(false)

  const isAuthenticated = computed(() => user.value !== null)

  /**
   * Resolve the session once at boot. A 401 here is the expected answer for a
   * signed-out visitor, not an error worth surfacing.
   */
  async function initialise(): Promise<void> {
    if (ready.value) return

    try {
      user.value = await authApi.me()
    } catch {
      user.value = null
    } finally {
      ready.value = true
    }
  }

  async function login(email: string, password: string): Promise<void> {
    user.value = await authApi.login({ email, password })
  }

  async function register(name: string, email: string, password: string): Promise<void> {
    user.value = await authApi.register({ name, email, password })
  }

  async function logout(): Promise<void> {
    try {
      await authApi.logout()
    } finally {
      user.value = null
    }
  }

  /** Called by the HTTP layer when the session expires mid-session. */
  function clear(): void {
    user.value = null
  }

  setUnauthenticatedHandler(() => clear())

  return { user, ready, isAuthenticated, initialise, login, register, logout, clear }
})
