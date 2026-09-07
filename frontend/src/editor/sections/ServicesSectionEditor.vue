<script setup lang="ts">
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import PanelSection from '@/editor/components/PanelSection.vue'
import RepeaterItem from '@/editor/components/RepeaterItem.vue'

import { useSectionModel } from './useSectionModel'

import type { Section } from '@/types'

interface Service extends Record<string, unknown> {
  title: string
  description: string
  price: string
}

const props = defineProps<{ section: Section }>()
const { field, list } = useSectionModel(() => props.section)

const heading = field('heading', 'Services')
const services = list<Service>('items')
</script>

<template>
  <div>
    <PanelSection title="Section">
      <BaseInput v-model="heading" label="Heading" :maxlength="120" />
    </PanelSection>

    <PanelSection title="Services">
      <EmptyState
        v-if="services.items.value.length === 0"
        compact
        title="No services yet"
        description="What you offer, and roughly what it costs."
      />

      <div v-else class="space-y-2">
        <RepeaterItem
          v-for="(item, index) in services.items.value"
          :key="index"
          :title="item.title || 'New service'"
          :subtitle="item.price"
          :can-move-up="index > 0"
          :can-move-down="index < services.items.value.length - 1"
          @up="services.move(index, index - 1)"
          @down="services.move(index, index + 1)"
          @remove="services.remove(index)"
        >
          <BaseInput
            :model-value="item.title"
            label="Title"
            @update:model-value="services.update(index, { title: $event })"
          />
          <BaseTextarea
            :model-value="item.description"
            label="Description"
            :rows="4"
            :maxlength="600"
            @update:model-value="services.update(index, { description: $event })"
          />
          <BaseInput
            :model-value="item.price"
            label="Price"
            placeholder="From €900 / day"
            :maxlength="40"
            @update:model-value="services.update(index, { price: $event })"
          />
        </RepeaterItem>
      </div>

      <BaseButton
        size="sm"
        icon="plus"
        block
        :disabled="services.items.value.length >= 12"
        @click="services.add({ title: '', description: '', price: '' })"
      >
        Add service
      </BaseButton>
    </PanelSection>
  </div>
</template>
