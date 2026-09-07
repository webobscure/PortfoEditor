<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { isApiError } from '@/api/client'
import TemplateCard from '@/components/templates/TemplateCard.vue'
import TemplatePreviewModal from '@/components/templates/TemplatePreviewModal.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import SkeletonBlock from '@/components/ui/SkeletonBlock.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { useCatalogStore } from '@/stores/catalog'
import { usePortfoliosStore } from '@/stores/portfolios'
import { useToastStore } from '@/stores/toast'

import type { IconName } from '@/components/ui/icons'
import type { Template } from '@/types'

/**
 * Onboarding, not a form.
 *
 * Three short questions and a template choice, then a portfolio that already
 * has content in it. Nobody should have to fill in thirty fields before seeing
 * whether they like the result.
 */
const catalog = useCatalogStore()
const portfolios = usePortfoliosStore()
const auth = useAuthStore()
const toast = useToastStore()
const router = useRouter()
const route = useRoute()

const STEPS = ['What you do', 'About you', 'Template', 'Review'] as const

const step = ref(0)
const preset = ref('designer')
const fullName = ref('')
const title = ref('')
const intro = ref('')
const templateKey = ref('')
const previewing = ref<Template | null>(null)
const creating = ref(false)
const fieldErrors = ref<Record<string, string[]>>({})

const PRESET_ICONS: Record<string, IconName> = {
  developer: 'code',
  designer: 'palette',
  photographer: 'image',
  product: 'layers',
  other: 'sparkle',
}

onMounted(async () => {
  fullName.value = auth.user?.name ?? ''
  await catalog.load()

  const requested = String(route.query.template ?? '')

  if (requested && catalog.template(requested)) {
    templateKey.value = requested
  }
})

// Choosing a discipline picks the template that suits it, until the user
// overrides that choice themselves.
const templateTouched = ref(false)

watch(preset, (value) => {
  if (templateTouched.value) return

  const match = catalog.presets.find((item) => item.key === value)

  if (match) templateKey.value = match.template
})

watch(
  () => catalog.loaded,
  (loaded) => {
    if (!loaded || templateKey.value) return

    const match = catalog.presets.find((item) => item.key === preset.value)
    templateKey.value = match?.template ?? 'minimal'
  },
  { immediate: true },
)

const selectedTemplate = computed(() => catalog.template(templateKey.value) ?? null)

const canContinue = computed(() => {
  if (step.value === 0) return preset.value !== ''
  if (step.value === 1) return fullName.value.trim().length > 1
  if (step.value === 2) return templateKey.value !== ''

  return true
})

function next(): void {
  if (step.value < STEPS.length - 1) step.value += 1
}

function back(): void {
  if (step.value > 0) step.value -= 1
}

function chooseTemplate(template: Template): void {
  templateKey.value = template.key
  templateTouched.value = true
  previewing.value = null
}

async function create(): Promise<void> {
  creating.value = true
  fieldErrors.value = {}

  try {
    const portfolio = await portfolios.create({
      name: fullName.value.trim() || 'My portfolio',
      preset: preset.value,
      template_key: templateKey.value,
      profile: {
        name: fullName.value.trim(),
        title: title.value.trim(),
        intro: intro.value.trim(),
      },
    })

    router.push({ name: 'editor', params: { id: portfolio.id } })
  } catch (error) {
    if (isApiError(error)) {
      fieldErrors.value = error.errors
      toast.error('Could not create the portfolio', error.message)
    } else {
      toast.error('Could not create the portfolio', 'Please try again.')
    }

    creating.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div class="mx-auto max-w-4xl">
      <!-- Stepper -->
      <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[12.5px]">
        <li v-for="(label, index) in STEPS" :key="label" class="flex items-center gap-2">
          <span
            class="grid size-5 place-items-center rounded-full text-[11px] font-semibold transition"
            :class="
              index < step
                ? 'bg-brand text-white'
                : index === step
                  ? 'bg-brand-soft text-brand ring-1 ring-brand-ring'
                  : 'bg-line-soft text-ink-faint'
            "
          >
            <AppIcon v-if="index < step" name="check" :size="12" />
            <template v-else>{{ index + 1 }}</template>
          </span>
          <span :class="index === step ? 'font-medium text-ink' : 'text-ink-muted'">
            {{ label }}
          </span>
          <AppIcon
            v-if="index < STEPS.length - 1"
            name="chevronRight"
            :size="13"
            class="text-ink-faint"
          />
        </li>
      </ol>

      <!-- Step 1 -->
      <section v-if="step === 0" class="mt-8">
        <h1 class="text-[26px] font-semibold tracking-[-0.025em]">What are you creating?</h1>
        <p class="mt-1 text-[13.5px] text-ink-muted">
          This decides the starting sections and sample copy. You can change everything later.
        </p>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
          <button
            v-for="option in catalog.presets"
            :key="option.key"
            type="button"
            class="panel flex items-start gap-3 p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-pop"
            :class="preset === option.key ? 'border-brand ring-1 ring-brand-ring' : ''"
            :aria-pressed="preset === option.key"
            @click="preset = option.key"
          >
            <span
              class="grid size-9 shrink-0 place-items-center rounded-[10px] transition"
              :class="preset === option.key ? 'bg-brand text-white' : 'bg-line-soft text-ink-muted'"
            >
              <AppIcon :name="PRESET_ICONS[option.key] ?? 'sparkle'" :size="18" />
            </span>
            <span class="min-w-0">
              <span class="block text-[14px] font-medium">{{ option.name }}</span>
              <span class="mt-0.5 block text-[12.5px] text-ink-muted">
                {{ option.description }}
              </span>
            </span>
          </button>

          <template v-if="!catalog.loaded">
            <SkeletonBlock v-for="n in 4" :key="n" height="82px" rounded="14px" />
          </template>
        </div>
      </section>

      <!-- Step 2 -->
      <section v-else-if="step === 1" class="mt-8 max-w-xl">
        <h1 class="text-[26px] font-semibold tracking-[-0.025em]">Tell us the basics</h1>
        <p class="mt-1 text-[13.5px] text-ink-muted">
          Three fields. Everything else is already filled in with sample content you can edit.
        </p>

        <div class="mt-6 space-y-4">
          <BaseInput
            v-model="fullName"
            label="Name"
            placeholder="Alex Morgan"
            :error="fieldErrors.name?.[0]"
            required
          />
          <BaseInput
            v-model="title"
            label="Professional title"
            placeholder="Product designer"
            hint="Appears under your name and in the page title."
          />
          <BaseTextarea
            v-model="intro"
            label="Short introduction"
            :rows="4"
            :maxlength="600"
            placeholder="One or two sentences about the work you want more of."
            hint="Leave it blank and we will start you with sample copy."
          />
        </div>
      </section>

      <!-- Step 3 -->
      <section v-else-if="step === 2" class="mt-8">
        <h1 class="text-[26px] font-semibold tracking-[-0.025em]">Choose a template</h1>
        <p class="mt-1 text-[13.5px] text-ink-muted">
          These are live renderings, not screenshots. Open one full screen to look properly.
        </p>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
          <TemplateCard
            v-for="template in catalog.templates"
            :key="template.key"
            :template="template"
            :preset="preset"
            :selected="templateKey === template.key"
            action-label="Select"
            @preview="previewing = template"
            @choose="chooseTemplate(template)"
          />
        </div>
      </section>

      <!-- Step 4 -->
      <section v-else class="mt-8 max-w-xl">
        <h1 class="text-[26px] font-semibold tracking-[-0.025em]">Ready to build</h1>
        <p class="mt-1 text-[13.5px] text-ink-muted">
          We will create your portfolio with a hero, about, work, skills and contact section already
          filled in.
        </p>

        <dl class="panel mt-6 divide-y divide-line-soft">
          <div
            v-for="row in [
              { label: 'Name', value: fullName || '—' },
              { label: 'Title', value: title || 'Sample content' },
              {
                label: 'Type',
                value: catalog.presets.find((p) => p.key === preset)?.name ?? preset,
              },
              { label: 'Template', value: selectedTemplate?.name ?? templateKey },
            ]"
            :key="row.label"
            class="flex items-baseline gap-4 px-4 py-3"
          >
            <dt class="w-28 shrink-0 text-[12.5px] text-ink-muted">{{ row.label }}</dt>
            <dd class="min-w-0 flex-1 truncate text-[13.5px]">{{ row.value }}</dd>
          </div>
        </dl>
      </section>

      <!-- Footer -->
      <div class="mt-8 flex items-center justify-between gap-3">
        <BaseButton
          v-if="step > 0"
          variant="ghost"
          icon="arrowLeft"
          :disabled="creating"
          @click="back"
        >
          Back
        </BaseButton>
        <span v-else />

        <BaseButton
          v-if="step < STEPS.length - 1"
          variant="primary"
          trailing-icon="arrowRight"
          :disabled="!canContinue"
          @click="next"
        >
          Continue
        </BaseButton>
        <BaseButton v-else variant="primary" size="lg" :loading="creating" @click="create">
          Create portfolio
        </BaseButton>
      </div>
    </div>

    <TemplatePreviewModal
      :open="previewing !== null"
      :template="previewing"
      :preset="preset"
      action-label="Select this template"
      @close="previewing = null"
      @choose="chooseTemplate"
    />
  </AppLayout>
</template>
