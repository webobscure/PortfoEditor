<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { isApiError } from '@/api/client'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const name = ref('')
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
    await auth.register(name.value, email.value, password.value)
    router.push({ name: 'portfolio-create' })
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
  <AuthLayout title="Create your account" subtitle="Your first portfolio takes about a minute.">
    <form class="space-y-4" novalidate @submit.prevent="submit">
      <p
        v-if="formError"
        class="rounded-[10px] border border-danger/25 bg-danger-soft px-3 py-2 text-[13px] text-danger"
        role="alert"
      >
        {{ formError }}
      </p>

      <BaseInput
        v-model="name"
        label="Your name"
        autocomplete="name"
        placeholder="Alex Morgan"
        :error="fieldErrors.name?.[0]"
        required
      />

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
        autocomplete="new-password"
        placeholder="At least 8 characters"
        hint="Eight characters or more, with a number."
        :error="fieldErrors.password?.[0]"
        required
      />

      <BaseButton type="submit" variant="primary" size="lg" block :loading="busy">
        Create account
      </BaseButton>
    </form>

    <p class="mt-6 text-center text-[13px] text-ink-muted">
      Already have an account?
      <RouterLink :to="{ name: 'login' }" class="font-medium text-brand hover:underline">
        Sign in
      </RouterLink>
    </p>
  </AuthLayout>
</template>
