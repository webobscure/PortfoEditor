<script setup lang="ts">
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import PanelSection from '@/editor/components/PanelSection.vue'
import RepeaterItem from '@/editor/components/RepeaterItem.vue'

import { useSectionModel } from './useSectionModel'

import type { Section } from '@/types'

interface Entry extends Record<string, unknown> {
  degree: string
  institution: string
  location: string
  start: string
  end: string
  description: string
}

const props = defineProps<{ section: Section }>()
const { field, list } = useSectionModel(() => props.section)

const heading = field('heading', 'Образование')
const entries = list<Entry>('items')

const blank: Entry = {
  degree: '',
  institution: '',
  location: '',
  start: '',
  end: '',
  description: '',
}
</script>

<template>
  <div>
    <PanelSection title="Секция">
      <BaseInput v-model="heading" label="Заголовок" :maxlength="120" />
    </PanelSection>

    <PanelSection title="Записи">
      <EmptyState v-if="entries.items.value.length === 0" compact title="Здесь пока пусто" />

      <div v-else class="space-y-2">
        <RepeaterItem
          v-for="(item, index) in entries.items.value"
          :key="index"
          :title="item.degree || 'Новая запись'"
          :subtitle="item.institution"
          :can-move-up="index > 0"
          :can-move-down="index < entries.items.value.length - 1"
          @up="entries.move(index, index - 1)"
          @down="entries.move(index, index + 1)"
          @remove="entries.remove(index)"
        >
          <BaseInput
            :model-value="item.degree"
            label="Квалификация"
            placeholder="Бакалавр, прикладная информатика"
            @update:model-value="entries.update(index, { degree: $event })"
          />
          <BaseInput
            :model-value="item.institution"
            label="Учебное заведение"
            @update:model-value="entries.update(index, { institution: $event })"
          />
          <BaseInput
            :model-value="item.location"
            label="Город"
            @update:model-value="entries.update(index, { location: $event })"
          />
          <div class="grid grid-cols-2 gap-2">
            <BaseInput
              :model-value="item.start"
              label="С"
              @update:model-value="entries.update(index, { start: $event })"
            />
            <BaseInput
              :model-value="item.end"
              label="по"
              @update:model-value="entries.update(index, { end: $event })"
            />
          </div>
          <BaseTextarea
            :model-value="item.description"
            label="Заметки"
            :rows="3"
            :maxlength="600"
            @update:model-value="entries.update(index, { description: $event })"
          />
        </RepeaterItem>
      </div>

      <BaseButton
        size="sm"
        icon="plus"
        block
        :disabled="entries.items.value.length >= 12"
        @click="entries.add({ ...blank })"
      >
        Добавить запись
      </BaseButton>
    </PanelSection>
  </div>
</template>
