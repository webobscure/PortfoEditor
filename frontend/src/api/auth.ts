import { ensureCsrfCookie, http, resetCsrfCookie } from './client'

import type { User } from '@/types'

export const authApi = {
  async register(payload: { name: string; email: string; password: string }): Promise<User> {
    await ensureCsrfCookie()
    const { data } = await http.post<{ data: User }>('/register', payload)

    return data.data
  },

  async login(payload: { email: string; password: string }): Promise<User> {
    await ensureCsrfCookie()
    const { data } = await http.post<{ data: User }>('/login', payload)

    return data.data
  },

  async logout(): Promise<void> {
    await http.post('/logout')
    resetCsrfCookie()
  },

  async me(): Promise<User> {
    const { data } = await http.get<{ data: User }>('/user')

    return data.data
  },
}
