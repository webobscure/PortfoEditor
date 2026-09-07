<script setup lang="ts">
import BaseButton from '@/components/ui/BaseButton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
</script>

<template>
  <div class="grid h-full place-items-center p-6">
    <EmptyState
      icon="search"
      title="Такая страница не найдена"
      description="Ссылка могла устареть, или портфолио удалили."
    >
      <BaseButton variant="primary" @click="$router.push({ name: 'dashboard' })">
        К моим портфолио
      </BaseButton>
    </EmptyState>
  </div>
</template>
