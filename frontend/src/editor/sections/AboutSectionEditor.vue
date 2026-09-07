<script setup lang="ts">
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ImageField from '@/editor/components/ImageField.vue'
import PanelSection from '@/editor/components/PanelSection.vue'
import RepeaterItem from '@/editor/components/RepeaterItem.vue'

import { useSectionModel } from './useSectionModel'

import type { Section } from '@/types'

interface Highlight extends Record<string, unknown> {
  label: string
  value: string
}

const props = defineProps<{ section: Section }>()
const { field, list } = useSectionModel(() => props.section)

const heading = field('heading', 'About')
const body = field('body', '')
const photo = field<number | null>('photo_media_id', null)
const highlights = list<Highlight>('highlights')
</script>

<template>
  <div>
    <PanelSection title="Content">
      <BaseInput v-model="heading" label="Heading" :maxlength="120" />
      <BaseTextarea
        v-model="body"
        label="About you"
        :rows="9"
        :maxlength="3000"
        placeholder="Where you come from, what you are good at, what you are looking for."
      />
      <ImageField v-model="photo" label="Photo" />
    </PanelSection>

    <PanelSection title="Highlights">
      <EmptyState
        v-if="highlights.items.value.length === 0"
        compact
        title="No highlights yet"
        description="Short numbers that anchor your experience — years, projects, clients."
      />

      <div v-else class="space-y-2">
        <RepeaterItem
          v-for="(highlight, index) in highlights.items.value"
          :key="index"
          :title="highlight.value || 'Highlight'"
          :subtitle="highlight.label"
          :can-move-up="index > 0"
          :can-move-down="index < highlights.items.value.length - 1"
          @up="highlights.move(index, index - 1)"
          @down="highlights.move(index, index + 1)"
          @remove="highlights.remove(index)"
        >
          <BaseInput
            :model-value="highlight.value"
            label="Value"
            placeholder="10"
            :maxlength="60"
            @update:model-value="highlights.update(index, { value: $event })"
          />
          <BaseInput
            :model-value="highlight.label"
            label="Label"
            placeholder="Years designing"
            :maxlength="60"
            @update:model-value="highlights.update(index, { label: $event })"
          />
        </RepeaterItem>
      </div>

      <BaseButton
        size="sm"
        icon="plus"
        block
        :disabled="highlights.items.value.length >= 6"
        @click="highlights.add({ label: '', value: '' })"
      >
        Add highlight
      </BaseButton>
    </PanelSection>
  </div>
</template>
