<script setup lang="ts">
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import PanelSection from '@/editor/components/PanelSection.vue'
import RepeaterItem from '@/editor/components/RepeaterItem.vue'
import TagInput from '@/editor/components/TagInput.vue'

import { useSectionModel } from './useSectionModel'

import type { Section } from '@/types'

interface Group extends Record<string, unknown> {
  name: string
  items: string[]
}

const props = defineProps<{ section: Section }>()
const { field, list } = useSectionModel(() => props.section)

const heading = field('heading', 'Skills')
const groups = list<Group>('groups')
</script>

<template>
  <div>
    <PanelSection title="Section">
      <BaseInput v-model="heading" label="Heading" :maxlength="120" />
    </PanelSection>

    <PanelSection title="Groups">
      <EmptyState
        v-if="groups.items.value.length === 0"
        compact
        title="No groups yet"
        description="Group skills so the list scans — languages, tools, methods."
      />

      <div v-else class="space-y-2">
        <RepeaterItem
          v-for="(group, index) in groups.items.value"
          :key="index"
          :title="group.name || 'New group'"
          :subtitle="`${(group.items ?? []).length} items`"
          :can-move-up="index > 0"
          :can-move-down="index < groups.items.value.length - 1"
          @up="groups.move(index, index - 1)"
          @down="groups.move(index, index + 1)"
          @remove="groups.remove(index)"
        >
          <BaseInput
            :model-value="group.name"
            label="Group name"
            placeholder="Design"
            :maxlength="60"
            @update:model-value="groups.update(index, { name: $event })"
          />
          <TagInput
            :model-value="group.items ?? []"
            label="Skills"
            placeholder="Prototyping"
            @update:model-value="groups.update(index, { items: $event })"
          />
        </RepeaterItem>
      </div>

      <BaseButton
        size="sm"
        icon="plus"
        block
        :disabled="groups.items.value.length >= 8"
        @click="groups.add({ name: '', items: [] })"
      >
        Add group
      </BaseButton>
    </PanelSection>
  </div>
</template>
