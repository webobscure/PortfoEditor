<script setup lang="ts">
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import ImageField from '@/editor/components/ImageField.vue'
import PanelSection from '@/editor/components/PanelSection.vue'

import { useSectionModel } from './useSectionModel'

import type { Section } from '@/types'

const props = defineProps<{ section: Section }>()
const { field } = useSectionModel(() => props.section)

const name = field('name', '')
const title = field('title', '')
const intro = field('intro', '')
const photo = field<number | null>('photo_media_id', null)
const ctaText = field('cta_text', '')
const ctaUrl = field('cta_url', '')
const secondaryText = field('secondary_cta_text', '')
const secondaryUrl = field('secondary_cta_url', '')
const alignment = field('alignment', 'left')
const showSocial = field('show_social', true)
</script>

<template>
  <div>
    <PanelSection title="Знакомство">
      <BaseInput v-model="name" label="Имя" placeholder="Александра Морозова" :maxlength="120" />
      <BaseInput
        v-model="title"
        label="Профессия"
        placeholder="Продуктовый дизайнер"
        :maxlength="160"
      />
      <BaseTextarea
        v-model="intro"
        label="Вступление"
        :rows="5"
        :maxlength="600"
        placeholder="Одно-два предложения о работе, которой хочется больше."
        hint="Пустая строка начинает новый абзац."
      />
      <ImageField
        v-model="photo"
        label="Фото"
        hint="Лучше всего подходят квадратные изображения."
      />
    </PanelSection>

    <PanelSection title="Кнопка действия">
      <BaseInput
        v-model="ctaText"
        label="Текст кнопки"
        placeholder="Смотреть работы"
        :maxlength="40"
      />
      <BaseInput
        v-model="ctaUrl"
        label="Ссылка кнопки"
        type="url"
        placeholder="https://example.com/work"
      />
      <div class="divider" />
      <BaseInput
        v-model="secondaryText"
        label="Текст второй кнопки"
        placeholder="Скачать резюме"
        :maxlength="40"
      />
      <BaseInput
        v-model="secondaryUrl"
        label="Ссылка второй кнопки"
        type="url"
        placeholder="https://example.com/cv.pdf"
      />
    </PanelSection>

    <PanelSection title="Раскладка">
      <div>
        <span class="field-label">Alignment</span>
        <SegmentedControl
          v-model="alignment"
          label="Выравнивание обложки"
          :options="[
            { value: 'left', label: 'Left' },
            { value: 'center', label: 'Centred' },
          ]"
        />
      </div>

      <div class="flex items-center justify-between gap-3">
        <span class="text-[12.5px] text-ink-soft">Show social links</span>
        <ToggleSwitch v-model="showSocial" label="Показывать соцсети на обложке" />
      </div>
    </PanelSection>
  </div>
</template>
