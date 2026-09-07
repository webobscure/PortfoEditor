<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { isApiError } from '@/api/client'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const busy = ref(false)
const formError = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

async function submit(): Promise<void> {
  busy.value = true
  formError.value = ''
  fieldErrors.value = {}

  try {
    await auth.login(email.value, password.value)
    const redirect = route.query.redirect

    router.push(typeof redirect === 'string' ? redirect : { name: 'dashboard' })
  } catch (error) {
    if (isApiError(error)) {
      fieldErrors.value = error.errors
      formError.value = Object.keys(error.errors).length === 0 ? error.message : ''
    } else {
      formError.value = 'Something went wrong. Try again.'
    }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthLayout title="Welcome back" subtitle="Sign in to keep building.">
    <form class="space-y-4" novalidate @submit.prevent="submit">
      <p
        v-if="formError"
        class="rounded-[10px] border border-danger/25 bg-danger-soft px-3 py-2 text-[13px] text-danger"
        role="alert"
      >
        {{ formError }}
      </p>

      <BaseInput
        v-model="email"
        label="Email"
        type="email"
        autocomplete="email"
        placeholder="you@example.com"
        :error="fieldErrors.email?.[0]"
        required
      />

      <BaseInput
        v-model="password"
        label="Password"
        type="password"
        autocomplete="current-password"
        placeholder="••••••••"
        :error="fieldErrors.password?.[0]"
        required
      />

      <BaseButton type="submit" variant="primary" size="lg" block :loading="busy">
        Sign in
      </BaseButton>
    </form>

    <p class="mt-6 text-center text-[13px] text-ink-muted">
      New here?
      <RouterLink :to="{ name: 'register' }" class="font-medium text-brand hover:underline">
        Create an account
      </RouterLink>
    </p>
  </AuthLayout>
</template>
