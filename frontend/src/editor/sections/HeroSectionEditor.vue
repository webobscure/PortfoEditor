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
    <PanelSection title="Introduction">
      <BaseInput v-model="name" label="Name" placeholder="Alex Morgan" :maxlength="120" />
      <BaseInput
        v-model="title"
        label="Job title"
        placeholder="Product designer"
        :maxlength="160"
      />
      <BaseTextarea
        v-model="intro"
        label="Intro"
        :rows="5"
        :maxlength="600"
        placeholder="One or two sentences about the work you want more of."
        hint="A blank line starts a new paragraph."
      />
      <ImageField v-model="photo" label="Photo" hint="Square images work best here." />
    </PanelSection>

    <PanelSection title="Call to action">
      <BaseInput v-model="ctaText" label="Button label" placeholder="View work" :maxlength="40" />
      <BaseInput
        v-model="ctaUrl"
        label="Button link"
        type="url"
        placeholder="https://example.com/work"
      />
      <div class="divider" />
      <BaseInput
        v-model="secondaryText"
        label="Secondary label"
        placeholder="Download CV"
        :maxlength="40"
      />
      <BaseInput
        v-model="secondaryUrl"
        label="Secondary link"
        type="url"
        placeholder="https://example.com/cv.pdf"
      />
    </PanelSection>

    <PanelSection title="Layout">
      <div>
        <span class="field-label">Alignment</span>
        <SegmentedControl
          v-model="alignment"
          label="Hero alignment"
          :options="[
            { value: 'left', label: 'Left' },
            { value: 'center', label: 'Centred' },
          ]"
        />
      </div>

      <div class="flex items-center justify-between gap-3">
        <span class="text-[12.5px] text-ink-soft">Show social links</span>
        <ToggleSwitch v-model="showSocial" label="Show social links in the hero" />
      </div>
    </PanelSection>
  </div>
</template>
