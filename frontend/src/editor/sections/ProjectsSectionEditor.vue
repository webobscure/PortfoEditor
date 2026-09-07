<script setup lang="ts">
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ImageField from '@/editor/components/ImageField.vue'
import PanelSection from '@/editor/components/PanelSection.vue'
import RepeaterItem from '@/editor/components/RepeaterItem.vue'
import TagInput from '@/editor/components/TagInput.vue'

import { useSectionModel } from './useSectionModel'

import type { Section } from '@/types'

interface Project extends Record<string, unknown> {
  title: string
  description: string
  image_media_id: number | null
  technologies: string[]
  url: string
  github_url: string
  year: string
  featured: boolean
}

const props = defineProps<{ section: Section }>()
const { field, list } = useSectionModel(() => props.section)

const heading = field('heading', 'Selected work')
const intro = field('intro', '')
const projects = list<Project>('items')

const blank: Project = {
  title: '',
  description: '',
  image_media_id: null,
  technologies: [],
  url: '',
  github_url: '',
  year: String(new Date().getFullYear()),
  featured: false,
}
</script>

<template>
  <div>
    <PanelSection title="Section">
      <BaseInput v-model="heading" label="Heading" :maxlength="120" />
      <BaseTextarea
        v-model="intro"
        label="Intro"
        :rows="3"
        :maxlength="400"
        placeholder="Optional line above the work."
      />
    </PanelSection>

    <PanelSection title="Projects">
      <EmptyState
        v-if="projects.items.value.length === 0"
        compact
        icon="folder"
        title="No projects yet"
        description="Three strong pieces beat ten average ones."
      />

      <div v-else class="space-y-2">
        <RepeaterItem
          v-for="(item, index) in projects.items.value"
          :key="index"
          :title="item.title || 'New project'"
          :subtitle="
            [item.year, ...(item.technologies ?? []).slice(0, 2)].filter(Boolean).join(' · ')
          "
          :can-move-up="index > 0"
          :can-move-down="index < projects.items.value.length - 1"
          @up="projects.move(index, index - 1)"
          @down="projects.move(index, index + 1)"
          @remove="projects.remove(index)"
        >
          <BaseInput
            :model-value="item.title"
            label="Title"
            @update:model-value="projects.update(index, { title: $event })"
          />
          <BaseTextarea
            :model-value="item.description"
            label="Description"
            :rows="5"
            :maxlength="800"
            placeholder="The problem, what you did, and what changed."
            @update:model-value="projects.update(index, { description: $event })"
          />
          <ImageField
            :model-value="item.image_media_id"
            label="Cover image"
            @update:model-value="projects.update(index, { image_media_id: $event })"
          />
          <TagInput
            :model-value="item.technologies ?? []"
            label="Technologies"
            placeholder="Figma"
            @update:model-value="projects.update(index, { technologies: $event })"
          />
          <BaseInput
            :model-value="item.year"
            label="Year"
            placeholder="2024"
            @update:model-value="projects.update(index, { year: $event })"
          />
          <BaseInput
            :model-value="item.url"
            label="Link"
            type="url"
            placeholder="https://example.com/project"
            @update:model-value="projects.update(index, { url: $event })"
          />
          <BaseInput
            :model-value="item.github_url"
            label="Source code"
            type="url"
            placeholder="https://github.com/you/project"
            @update:model-value="projects.update(index, { github_url: $event })"
          />
        </RepeaterItem>
      </div>

      <BaseButton
        size="sm"
        icon="plus"
        block
        :disabled="projects.items.value.length >= 24"
        @click="projects.add({ ...blank })"
      >
        Add project
      </BaseButton>
    </PanelSection>
  </div>
</template>
