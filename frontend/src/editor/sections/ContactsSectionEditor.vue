<script setup lang="ts">
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import PanelSection from '@/editor/components/PanelSection.vue'

import { useSectionModel } from './useSectionModel'

import type { Section } from '@/types'

const props = defineProps<{ section: Section }>()
const { field } = useSectionModel(() => props.section)

const heading = field('heading', 'Get in touch')
const intro = field('intro', '')
const email = field('email', '')
const phone = field('phone', '')
const location = field('location', '')
const availability = field('availability', '')
const ctaText = field('cta_text', 'Send an email')
</script>

<template>
  <div>
    <PanelSection title="Content">
      <BaseInput v-model="heading" label="Heading" :maxlength="120" />
      <BaseTextarea
        v-model="intro"
        label="Intro"
        :rows="4"
        :maxlength="600"
        placeholder="What you are open to, and how quickly you reply."
      />
    </PanelSection>

    <PanelSection title="Details">
      <BaseInput v-model="email" label="Email" type="email" placeholder="you@example.com" />
      <BaseInput v-model="phone" label="Phone" placeholder="+351 000 000 000" />
      <BaseInput v-model="location" label="Location" placeholder="Lisbon, Portugal" />
      <BaseInput
        v-model="availability"
        label="Availability"
        placeholder="Taking projects from April"
        hint="Some templates show this as a status badge in the hero."
      />
      <BaseInput v-model="ctaText" label="Button label" :maxlength="40" />
    </PanelSection>
  </div>
</template>
