<script setup lang="ts">
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import PanelSection from '@/editor/components/PanelSection.vue'
import RepeaterItem from '@/editor/components/RepeaterItem.vue'
import TagInput from '@/editor/components/TagInput.vue'

import { useSectionModel } from './useSectionModel'

import type { Section } from '@/types'

interface Role extends Record<string, unknown> {
  role: string
  company: string
  location: string
  start: string
  end: string
  current: boolean
  description: string
  tags: string[]
}

const props = defineProps<{ section: Section }>()
const { field, list } = useSectionModel(() => props.section)

const heading = field('heading', 'Experience')
const roles = list<Role>('items')

const blank: Role = {
  role: '',
  company: '',
  location: '',
  start: '',
  end: '',
  current: false,
  description: '',
  tags: [],
}
</script>

<template>
  <div>
    <PanelSection title="Section">
      <BaseInput v-model="heading" label="Heading" :maxlength="120" />
    </PanelSection>

    <PanelSection title="Roles">
      <EmptyState
        v-if="roles.items.value.length === 0"
        compact
        title="No roles yet"
        description="Start with your current position."
      />

      <div v-else class="space-y-2">
        <RepeaterItem
          v-for="(item, index) in roles.items.value"
          :key="index"
          :title="item.role || 'New role'"
          :subtitle="[item.company, item.start].filter(Boolean).join(' · ')"
          :can-move-up="index > 0"
          :can-move-down="index < roles.items.value.length - 1"
          @up="roles.move(index, index - 1)"
          @down="roles.move(index, index + 1)"
          @remove="roles.remove(index)"
        >
          <BaseInput
            :model-value="item.role"
            label="Role"
            placeholder="Senior Product Designer"
            @update:model-value="roles.update(index, { role: $event })"
          />
          <BaseInput
            :model-value="item.company"
            label="Company"
            @update:model-value="roles.update(index, { company: $event })"
          />
          <BaseInput
            :model-value="item.location"
            label="Location"
            placeholder="Remote"
            @update:model-value="roles.update(index, { location: $event })"
          />

          <div class="grid grid-cols-2 gap-2">
            <BaseInput
              :model-value="item.start"
              label="From"
              placeholder="2021"
              @update:model-value="roles.update(index, { start: $event })"
            />
            <BaseInput
              :model-value="item.end"
              label="To"
              placeholder="2024"
              :disabled="item.current"
              @update:model-value="roles.update(index, { end: $event })"
            />
          </div>

          <div class="flex items-center justify-between gap-3">
            <span class="text-[12.5px] text-ink-soft">Current role</span>
            <ToggleSwitch
              :model-value="item.current"
              label="Current role"
              @update:model-value="roles.update(index, { current: $event })"
            />
          </div>

          <BaseTextarea
            :model-value="item.description"
            label="What you did"
            :rows="4"
            :maxlength="1000"
            placeholder="Responsibilities, and one concrete result."
            @update:model-value="roles.update(index, { description: $event })"
          />

          <TagInput
            :model-value="item.tags ?? []"
            label="Tags"
            placeholder="Design systems"
            @update:model-value="roles.update(index, { tags: $event })"
          />
        </RepeaterItem>
      </div>

      <BaseButton
        size="sm"
        icon="plus"
        block
        :disabled="roles.items.value.length >= 20"
        @click="roles.add({ ...blank })"
      >
        Add role
      </BaseButton>
    </PanelSection>
  </div>
</template>
