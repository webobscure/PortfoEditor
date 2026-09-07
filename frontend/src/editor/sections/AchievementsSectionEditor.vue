<script setup lang="ts">
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import PanelSection from '@/editor/components/PanelSection.vue'
import RepeaterItem from '@/editor/components/RepeaterItem.vue'

import { useSectionModel } from './useSectionModel'

import type { Section } from '@/types'

interface Achievement extends Record<string, unknown> {
  title: string
  issuer: string
  date: string
  url: string
  description: string
}

const props = defineProps<{ section: Section }>()
const { field, list } = useSectionModel(() => props.section)

const heading = field('heading', 'Achievements')
const items = list<Achievement>('items')

const blank: Achievement = { title: '', issuer: '', date: '', url: '', description: '' }
</script>

<template>
  <div>
    <PanelSection title="Section">
      <BaseInput v-model="heading" label="Heading" :maxlength="120" />
    </PanelSection>

    <PanelSection title="Entries">
      <EmptyState
        v-if="items.items.value.length === 0"
        compact
        icon="award"
        title="Nothing here yet"
        description="Awards, talks, exhibitions, publications."
      />

      <div v-else class="space-y-2">
        <RepeaterItem
          v-for="(item, index) in items.items.value"
          :key="index"
          :title="item.title || 'New entry'"
          :subtitle="[item.issuer, item.date].filter(Boolean).join(' · ')"
          :can-move-up="index > 0"
          :can-move-down="index < items.items.value.length - 1"
          @up="items.move(index, index - 1)"
          @down="items.move(index, index + 1)"
          @remove="items.remove(index)"
        >
          <BaseInput
            :model-value="item.title"
            label="Title"
            @update:model-value="items.update(index, { title: $event })"
          />
          <BaseInput
            :model-value="item.issuer"
            label="Issuer or venue"
            @update:model-value="items.update(index, { issuer: $event })"
          />
          <BaseInput
            :model-value="item.date"
            label="Date"
            placeholder="2024"
            @update:model-value="items.update(index, { date: $event })"
          />
          <BaseInput
            :model-value="item.url"
            label="Link"
            type="url"
            @update:model-value="items.update(index, { url: $event })"
          />
          <BaseTextarea
            :model-value="item.description"
            label="Note"
            :rows="3"
            :maxlength="600"
            @update:model-value="items.update(index, { description: $event })"
          />
        </RepeaterItem>
      </div>

      <BaseButton
        size="sm"
        icon="plus"
        block
        :disabled="items.items.value.length >= 16"
        @click="items.add({ ...blank })"
      >
        Add entry
      </BaseButton>
    </PanelSection>
  </div>
</template>
