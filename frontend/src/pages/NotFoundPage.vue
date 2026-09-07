<script setup lang="ts">
import BaseButton from '@/components/ui/BaseButton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
</script>

<template>
  <div class="grid h-full place-items-center p-6">
    <EmptyState
      icon="search"
      title="We could not find that page"
      description="The link may be out of date, or the portfolio may have been deleted."
    >
      <BaseButton variant="primary" @click="$router.push({ name: 'dashboard' })">
        Back to your portfolios
      </BaseButton>
    </EmptyState>
  </div>
</template>
