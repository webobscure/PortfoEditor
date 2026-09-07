<script setup lang="ts">
import { RouterLink, useRouter } from 'vue-router'

import AppIcon from '@/components/ui/AppIcon.vue'
import BasePopover from '@/components/ui/BasePopover.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function signOut(): Promise<void> {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="flex min-h-full flex-col">
    <header class="sticky top-0 z-30 border-b border-line bg-surface/85 backdrop-blur-md">
      <div class="mx-auto flex h-14 max-w-6xl items-center gap-1 px-4 sm:px-6">
        <RouterLink
          :to="{ name: 'dashboard' }"
          class="mr-3 flex items-center gap-2 rounded-lg px-1 py-1"
        >
          <span class="grid size-7 place-items-center rounded-[8px] bg-brand text-white">
            <svg viewBox="0 0 32 32" class="size-4" aria-hidden="true">
              <path
                d="M10 23V9h6.2a4.6 4.6 0 0 1 0 9.2H13"
                stroke="currentColor"
                stroke-width="3"
                fill="none"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </span>
          <span class="text-[14.5px] font-semibold tracking-[-0.015em]">Portfoedit</span>
        </RouterLink>

        <nav class="flex items-center gap-0.5">
          <RouterLink
            v-for="link in [
              { name: 'dashboard', label: 'My portfolios' },
              { name: 'templates', label: 'Templates' },
            ]"
            :key="link.name"
            :to="{ name: link.name }"
            class="rounded-[9px] px-2.5 py-1.5 text-[13px] transition"
            active-class="bg-line-soft text-ink font-medium"
            exact-active-class="bg-line-soft text-ink font-medium"
            :class="'text-ink-soft hover:text-ink hover:bg-line-soft/70'"
          >
            {{ link.label }}
          </RouterLink>
        </nav>

        <div class="flex-1" />

        <BasePopover align="right" width="w-52">
          <template #trigger="{ toggle }">
            <button
              type="button"
              class="grid size-8 place-items-center rounded-full bg-brand-soft text-[12px] font-semibold text-brand transition hover:brightness-97"
              aria-label="Account menu"
              @click="toggle"
            >
              {{ auth.user?.initials }}
            </button>
          </template>

          <template #default="{ close }">
            <div class="px-2 py-1.5">
              <p class="truncate text-[13px] font-medium">{{ auth.user?.name }}</p>
              <p class="truncate text-[12px] text-ink-muted">{{ auth.user?.email }}</p>
            </div>
            <div class="divider my-1" />
            <button
              type="button"
              class="flex w-full items-center gap-2 rounded-[8px] px-2 py-1.5 text-left text-[13px] text-ink-soft transition hover:bg-line-soft hover:text-ink"
              @click="
                () => {
                  close()
                  signOut()
                }
              "
            >
              <AppIcon name="logout" :size="15" />
              Sign out
            </button>
          </template>
        </BasePopover>
      </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 sm:py-10">
      <slot />
    </main>
  </div>
</template>
