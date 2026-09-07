<script setup lang="ts">
defineProps<{ modelValue: boolean; label: string; disabled?: boolean }>()

const emit = defineEmits<{ 'update:modelValue': [boolean] }>()
</script>

<template>
  <button
    type="button"
    role="switch"
    :aria-checked="modelValue"
    :aria-label="label"
    :disabled="disabled"
    class="relative inline-flex h-[18px] w-[30px] shrink-0 items-center rounded-full transition-colors duration-200 disabled:opacity-40"
    :class="modelValue ? 'bg-brand' : 'bg-[#d6d6dc]'"
    @click="emit('update:modelValue', !modelValue)"
  >
    <span
      class="inline-block size-[14px] rounded-full bg-white shadow-sm transition-transform duration-200 ease-[cubic-bezier(0.22,0.8,0.3,1)]"
      :class="modelValue ? 'translate-x-[14px]' : 'translate-x-[2px]'"
    />
  </button>
</template>
