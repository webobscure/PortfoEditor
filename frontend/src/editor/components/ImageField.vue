<script setup lang="ts">
import { computed, ref } from 'vue'

import { isApiError } from '@/api/client'
import AppIcon from '@/components/ui/AppIcon.vue'
import { useEditorStore } from '@/stores/editor'
import { useToastStore } from '@/stores/toast'

/**
 * Upload-and-attach for a single image.
 *
 * The value stored on the section is a media id, never a URL, so moving the
 * storage disk later does not rewrite user content.
 */
const props = defineProps<{ modelValue: number | null; label: string; hint?: string }>()
const emit = defineEmits<{ 'update:modelValue': [number | null] }>()

const editor = useEditorStore()
const toast = useToastStore()

const input = ref<HTMLInputElement | null>(null)
const progress = ref<number | null>(null)

const image = computed(() => (props.modelValue ? editor.media[props.modelValue] : undefined))

async function onPick(event: Event): Promise<void> {
  const file = (event.target as HTMLInputElement).files?.[0]

  if (!file) return

  progress.value = 0

  try {
    const media = await editor.upload(file, (percent) => (progress.value = percent))
    emit('update:modelValue', media.id)
  } catch (error) {
    toast.error(
      'Не удалось загрузить',
      isApiError(error) ? error.message : 'Подойдёт JPEG, PNG или WebP до 8 МБ.',
    )
  } finally {
    progress.value = null

    if (input.value) input.value.value = ''
  }
}
</script>

<template>
  <div>
    <span class="field-label">{{ label }}</span>

    <div
      class="relative overflow-hidden rounded-[12px] border border-line bg-raised transition hover:border-[#d9d9de]"
    >
      <img v-if="image" :src="image.url" :alt="label" class="aspect-[4/3] w-full object-cover" />

      <button
        v-else
        type="button"
        class="flex aspect-[4/3] w-full flex-col items-center justify-center gap-1.5 text-ink-muted transition hover:text-ink-soft"
        @click="input?.click()"
      >
        <AppIcon name="upload" :size="18" />
        <span class="text-[12.5px]">Загрузить изображение</span>
        <span class="text-[11px] text-ink-faint">JPEG, PNG или WebP</span>
      </button>

      <div
        v-if="progress !== null"
        class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-surface/85"
      >
        <div class="h-1 w-24 overflow-hidden rounded-full bg-line">
          <div
            class="h-full bg-brand transition-[width] duration-200"
            :style="{ width: `${progress}%` }"
          />
        </div>
        <span class="text-[11.5px] text-ink-muted">Загрузка {{ progress }}%</span>
      </div>

      <div v-if="image" class="flex items-center gap-1 border-t border-line-soft bg-surface p-1.5">
        <button
          type="button"
          class="inline-flex h-6 items-center gap-1 rounded-md px-2 text-[12px] text-ink-soft transition hover:bg-line-soft"
          @click="input?.click()"
        >
          <AppIcon name="refresh" :size="13" />
          Заменить
        </button>
        <button
          type="button"
          class="inline-flex h-6 items-center gap-1 rounded-md px-2 text-[12px] text-ink-muted transition hover:bg-danger-soft hover:text-danger"
          @click="emit('update:modelValue', null)"
        >
          <AppIcon name="trash" :size="13" />
          Убрать
        </button>
      </div>
    </div>

    <p v-if="hint" class="mt-1.5 text-[12px] text-ink-muted">{{ hint }}</p>

    <input
      ref="input"
      type="file"
      accept="image/jpeg,image/png,image/webp"
      class="sr-only"
      :aria-label="label"
      @change="onPick"
    />
  </div>
</template>
