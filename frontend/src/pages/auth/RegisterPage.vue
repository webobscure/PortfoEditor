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
      formError.value = 'Что-то пошло не так. Попробуйте ещё раз.'
    }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthLayout title="Создайте аккаунт" subtitle="Первое портфолио займёт около минуты.">
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
        label="Ваше имя"
        autocomplete="name"
        placeholder="Александра Морозова"
        :error="fieldErrors.name?.[0]"
        required
      />

      <BaseInput
        v-model="email"
        label="Почта"
        type="email"
        autocomplete="email"
        placeholder="you@example.com"
        :error="fieldErrors.email?.[0]"
        required
      />

      <BaseInput
        v-model="password"
        label="Пароль"
        type="password"
        autocomplete="new-password"
        placeholder="Минимум 8 символов"
        hint="Восемь символов или больше, хотя бы одна цифра."
        :error="fieldErrors.password?.[0]"
        required
      />

      <BaseButton type="submit" variant="primary" size="lg" block :loading="busy">
        Создать аккаунт
      </BaseButton>
    </form>

    <p class="mt-6 text-center text-[13px] text-ink-muted">
      Уже есть аккаунт?
      <RouterLink :to="{ name: 'login' }" class="font-medium text-brand hover:underline">
        Войти
      </RouterLink>
    </p>
  </AuthLayout>
</template>
