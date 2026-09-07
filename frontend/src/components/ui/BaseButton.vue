<script setup lang="ts">
import { computed } from 'vue'

import AppIcon from './AppIcon.vue'

import type { IconName } from './icons'

/**
 * The one button.
 *
 * Variants are deliberately few: a single solid action per screen, everything
 * else quiet. That restraint is most of what keeps the editor from reading as
 * an admin panel.
 */
const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
    size?: 'sm' | 'md' | 'lg'
    icon?: IconName
    trailingIcon?: IconName
    loading?: boolean
    disabled?: boolean
    block?: boolean
    type?: 'button' | 'submit'
  }>(),
  { variant: 'secondary', size: 'md', type: 'button' },
)

const classes = computed(() => {
  const base = [
    'inline-flex items-center justify-center gap-1.5 font-medium whitespace-nowrap',
    'rounded-[10px] transition-all duration-150 ease-[cubic-bezier(0.22,0.8,0.3,1)]',
    'disabled:opacity-50 disabled:pointer-events-none select-none',
  ]

  const sizes = {
    sm: 'h-7 px-2.5 text-[12.5px]',
    md: 'h-9 px-3.5 text-[13.5px]',
    lg: 'h-11 px-5 text-[14.5px]',
  }

  const variants = {
    primary: 'bg-brand text-white shadow-soft hover:bg-brand-hover active:scale-[0.985]',
    secondary:
      'bg-surface text-ink border border-line shadow-soft hover:border-[#d9d9de] hover:bg-raised active:scale-[0.985]',
    ghost: 'text-ink-soft hover:bg-line-soft hover:text-ink',
    danger: 'bg-danger-soft text-danger hover:bg-[#fbdedc]',
  }

  return [...base, sizes[props.size], variants[props.variant], props.block ? 'w-full' : ''].join(
    ' ',
  )
})
</script>

<template>
  <button :type="type" :class="classes" :disabled="disabled || loading">
    <span
      v-if="loading"
      class="size-3.5 animate-spin rounded-full border-[1.6px] border-current border-t-transparent"
      aria-hidden="true"
    />
    <AppIcon v-else-if="icon" :name="icon" :size="size === 'lg' ? 17 : 15" />
    <slot />
    <AppIcon v-if="trailingIcon && !loading" :name="trailingIcon" :size="15" />
  </button>
</template>
