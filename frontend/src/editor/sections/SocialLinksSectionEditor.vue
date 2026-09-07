<script setup lang="ts">
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import PanelSection from '@/editor/components/PanelSection.vue'
import RepeaterItem from '@/editor/components/RepeaterItem.vue'
import { useCatalogStore } from '@/stores/catalog'

import { useSectionModel } from './useSectionModel'

import type { Section } from '@/types'

interface Link extends Record<string, unknown> {
  platform: string
  url: string
  label: string
}

const props = defineProps<{ section: Section }>()
const { list } = useSectionModel(() => props.section)
const catalog = useCatalogStore()

const links = list<Link>('items')

const platformOptions = () =>
  (catalog.options?.social_platforms ?? []).map((platform) => ({
    value: platform,
    label: platform.charAt(0).toUpperCase() + platform.slice(1),
  }))
</script>

<template>
  <div>
    <PanelSection title="Profiles">
      <EmptyState
        v-if="links.items.value.length === 0"
        compact
        icon="link"
        title="No links yet"
        description="Two or three good ones beat a full row of icons."
      />

      <div v-else class="space-y-2">
        <RepeaterItem
          v-for="(item, index) in links.items.value"
          :key="index"
          :title="item.label || item.platform || 'New link'"
          :subtitle="item.url"
          :can-move-up="index > 0"
          :can-move-down="index < links.items.value.length - 1"
          @up="links.move(index, index - 1)"
          @down="links.move(index, index + 1)"
          @remove="links.remove(index)"
        >
          <BaseSelect
            :model-value="item.platform"
            label="Platform"
            :options="platformOptions()"
            @update:model-value="links.update(index, { platform: $event })"
          />
          <BaseInput
            :model-value="item.url"
            label="URL"
            type="url"
            placeholder="https://github.com/you"
            @update:model-value="links.update(index, { url: $event })"
          />
          <BaseInput
            :model-value="item.label"
            label="Label"
            placeholder="GitHub"
            :maxlength="40"
            @update:model-value="links.update(index, { label: $event })"
          />
        </RepeaterItem>
      </div>

      <BaseButton
        size="sm"
        icon="plus"
        block
        :disabled="links.items.value.length >= 10"
        @click="links.add({ platform: 'website', url: '', label: '' })"
      >
        Add link
      </BaseButton>
    </PanelSection>
  </div>
</template>
