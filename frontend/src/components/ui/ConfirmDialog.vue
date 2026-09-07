<script setup lang="ts">
import BaseButton from './BaseButton.vue'
import BaseModal from './BaseModal.vue'

/**
 * Used before anything destructive — deleting a portfolio, or a section that
 * already has content in it.
 */
defineProps<{
  open: boolean
  title: string
  description?: string
  confirmLabel?: string
  destructive?: boolean
  busy?: boolean
}>()

const emit = defineEmits<{ close: []; confirm: [] }>()
</script>

<template>
  <BaseModal
    :open="open"
    size="sm"
    :title="title"
    :description="description"
    @close="emit('close')"
  >
    <div class="flex justify-end gap-2 px-5 pt-1 pb-4">
      <BaseButton variant="ghost" @click="emit('close')">Отмена</BaseButton>
      <BaseButton
        :variant="destructive ? 'danger' : 'primary'"
        :loading="busy"
        @click="emit('confirm')"
      >
        {{ confirmLabel ?? 'Подтвердить' }}
      </BaseButton>
    </div>
  </BaseModal>
</template>
