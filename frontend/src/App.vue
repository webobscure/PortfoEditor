<script setup lang="ts">
import { onMounted } from 'vue'

import ToastHost from '@/components/ui/ToastHost.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

// Resolve the session before the first guarded navigation renders, so a signed
// in user never sees the login screen flash on a refresh.
onMounted(() => auth.initialise())
</script>

<template>
  <RouterView v-if="auth.ready" />
  <div v-else class="grid h-full place-items-center">
    <div class="flex items-center gap-2 text-[13px] text-ink-muted">
      <span
        class="size-3.5 animate-spin rounded-full border-[1.6px] border-current border-t-transparent"
      />
      Загрузка
    </div>
  </div>

  <ToastHost />
</template>
