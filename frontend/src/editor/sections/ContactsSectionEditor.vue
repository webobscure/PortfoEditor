<script setup lang="ts">
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import PanelSection from '@/editor/components/PanelSection.vue'

import { useSectionModel } from './useSectionModel'

import type { Section } from '@/types'

const props = defineProps<{ section: Section }>()
const { field } = useSectionModel(() => props.section)

const heading = field('heading', 'Связаться')
const intro = field('intro', '')
const email = field('email', '')
const phone = field('phone', '')
const location = field('location', '')
const availability = field('availability', '')
const ctaText = field('cta_text', 'Написать письмо')
</script>

<template>
  <div>
    <PanelSection title="Содержимое">
      <BaseInput v-model="heading" label="Заголовок" :maxlength="120" />
      <BaseTextarea
        v-model="intro"
        label="Вступление"
        :rows="4"
        :maxlength="600"
        placeholder="К чему вы открыты и как быстро отвечаете."
      />
    </PanelSection>

    <PanelSection title="Подробности">
      <BaseInput v-model="email" label="Почта" type="email" placeholder="you@example.com" />
      <BaseInput v-model="phone" label="Телефон" placeholder="+7 900 000-00-00" />
      <BaseInput v-model="location" label="Город" placeholder="Москва" />
      <BaseInput
        v-model="availability"
        label="Доступность"
        placeholder="Беру проекты с апреля"
        hint="Некоторые шаблоны показывают это бейджем на обложке."
      />
      <BaseInput v-model="ctaText" label="Текст кнопки" :maxlength="40" />
    </PanelSection>
  </div>
</template>
