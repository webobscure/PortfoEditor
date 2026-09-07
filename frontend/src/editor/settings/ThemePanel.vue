<script setup lang="ts">
import { computed } from 'vue'

import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import ColorField from '@/components/ui/ColorField.vue'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'
import PanelSection from '@/editor/components/PanelSection.vue'
import { useCatalogStore } from '@/stores/catalog'
import { useEditorStore } from '@/stores/editor'

import type { ColorScheme, ThemeColors } from '@/types'

/**
 * Project-level settings — shown whenever no section is selected.
 *
 * Every control writes a sparse override. A value the user never touched keeps
 * following the template, which is what makes switching templates restyle the
 * page instead of leaving a half-old palette behind.
 */
const editor = useEditorStore()
const catalog = useCatalogStore()

const settings = computed(() => editor.effectiveSettings)
const template = computed(() => catalog.template(editor.activeTemplateKey))

const headingFonts = computed(() =>
  catalog.fonts
    .filter((font) => font.roles.includes('heading'))
    .map((font) => ({ value: font.key, label: `${font.name} · ${font.category}` })),
)

const bodyFonts = computed(() =>
  catalog.fonts
    .filter((font) => font.roles.includes('body'))
    .map((font) => ({ value: font.key, label: `${font.name} · ${font.category}` })),
)

const swatches = computed(() =>
  (template.value?.color_schemes ?? []).flatMap((scheme) => scheme.colors.accent),
)

function applyScheme(scheme: ColorScheme): void {
  editor.patchSettings('colors', scheme.colors)
}

function setColor(key: keyof ThemeColors, value: string): void {
  editor.patchSettings('colors', { [key]: value })
}

const isActiveScheme = (scheme: ColorScheme) =>
  scheme.colors.accent === settings.value.colors.accent &&
  scheme.colors.background === settings.value.colors.background
</script>

<template>
  <div>
    <PanelSection title="Colour scheme">
      <div v-if="template?.color_schemes?.length" class="grid grid-cols-3 gap-2">
        <button
          v-for="scheme in template.color_schemes"
          :key="scheme.key"
          type="button"
          class="rounded-[11px] border p-1.5 transition hover:shadow-soft"
          :class="isActiveScheme(scheme) ? 'border-brand ring-1 ring-brand-ring' : 'border-line'"
          :aria-pressed="isActiveScheme(scheme)"
          @click="applyScheme(scheme)"
        >
          <span
            class="flex h-10 items-end gap-0.5 overflow-hidden rounded-[7px] p-1"
            :style="{ background: scheme.colors.background }"
          >
            <span
              class="h-3.5 flex-1 rounded-[3px]"
              :style="{ background: scheme.colors.accent }"
            />
            <span class="h-2 flex-1 rounded-[3px]" :style="{ background: scheme.colors.muted }" />
            <span class="h-5 flex-1 rounded-[3px]" :style="{ background: scheme.colors.text }" />
          </span>
          <span class="mt-1.5 block truncate text-[11.5px] text-ink-soft">{{ scheme.name }}</span>
        </button>
      </div>
    </PanelSection>

    <PanelSection title="Colours">
      <ColorField
        :model-value="settings.colors.accent"
        label="Accent"
        :presets="swatches"
        @update:model-value="setColor('accent', $event)"
      />
      <ColorField
        :model-value="settings.colors.background"
        label="Background"
        @update:model-value="setColor('background', $event)"
      />
      <ColorField
        :model-value="settings.colors.surface"
        label="Surface"
        @update:model-value="setColor('surface', $event)"
      />
      <ColorField
        :model-value="settings.colors.text"
        label="Text"
        @update:model-value="setColor('text', $event)"
      />
      <ColorField
        :model-value="settings.colors.muted"
        label="Muted text"
        @update:model-value="setColor('muted', $event)"
      />
      <ColorField
        :model-value="settings.colors.border"
        label="Borders"
        @update:model-value="setColor('border', $event)"
      />
    </PanelSection>

    <PanelSection title="Typography">
      <BaseSelect
        :model-value="settings.typography.heading_font"
        label="Headings"
        :options="headingFonts"
        @update:model-value="editor.patchSettings('typography', { heading_font: $event })"
      />
      <BaseSelect
        :model-value="settings.typography.body_font"
        label="Body"
        :options="bodyFonts"
        @update:model-value="editor.patchSettings('typography', { body_font: $event })"
      />
      <div>
        <span class="field-label">Text size</span>
        <SegmentedControl
          :model-value="settings.typography.scale"
          label="Text size"
          compact
          :options="[
            { value: 'compact', label: 'S' },
            { value: 'default', label: 'M' },
            { value: 'large', label: 'L' },
          ]"
          @update:model-value="
            editor.patchSettings('typography', { scale: $event as 'compact' | 'default' | 'large' })
          "
        />
      </div>
    </PanelSection>

    <PanelSection title="Layout">
      <div>
        <span class="field-label">Section spacing</span>
        <SegmentedControl
          :model-value="settings.layout.section_spacing"
          label="Section spacing"
          compact
          :options="[
            { value: 'compact', label: 'Tight' },
            { value: 'default', label: 'Normal' },
            { value: 'spacious', label: 'Airy' },
          ]"
          @update:model-value="
            editor.patchSettings('layout', {
              section_spacing: $event as 'compact' | 'default' | 'spacious',
            })
          "
        />
      </div>
      <div>
        <span class="field-label">Content width</span>
        <SegmentedControl
          :model-value="settings.layout.content_width"
          label="Content width"
          compact
          :options="[
            { value: 'narrow', label: 'Narrow' },
            { value: 'default', label: 'Default' },
            { value: 'wide', label: 'Wide' },
          ]"
          @update:model-value="
            editor.patchSettings('layout', {
              content_width: $event as 'narrow' | 'default' | 'wide',
            })
          "
        />
      </div>
      <div>
        <span class="field-label">Buttons</span>
        <SegmentedControl
          :model-value="settings.buttons.style"
          label="Button style"
          compact
          :options="[
            { value: 'rounded', label: 'Rounded' },
            { value: 'square', label: 'Square' },
            { value: 'pill', label: 'Pill' },
          ]"
          @update:model-value="
            editor.patchSettings('buttons', { style: $event as 'rounded' | 'square' | 'pill' })
          "
        />
      </div>
    </PanelSection>

    <PanelSection title="Search &amp; sharing" :default-open="false">
      <BaseInput
        :model-value="editor.portfolio?.meta.title ?? ''"
        label="Page title"
        :maxlength="70"
        placeholder="Alex Morgan — Product designer"
        hint="Leave blank to use your name and title."
        @update:model-value="editor.patchMeta({ title: $event })"
      />
      <BaseTextarea
        :model-value="editor.portfolio?.meta.description ?? ''"
        label="Meta description"
        :rows="3"
        :maxlength="180"
        placeholder="Shown in search results and link previews."
        @update:model-value="editor.patchMeta({ description: $event })"
      />
    </PanelSection>
  </div>
</template>
