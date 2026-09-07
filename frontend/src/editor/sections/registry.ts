import { defineAsyncComponent, type Component } from 'vue'

import type { IconName } from '@/components/ui/icons'
import type { SectionTypeKey } from '@/types'

/**
 * Section registry.
 *
 * Each section type owns a dedicated editor component rather than sharing one
 * mega-form full of conditionals: the fields for a project list have nothing in
 * common with the fields for a hero, and pretending otherwise is how builder
 * UIs end up feeling generic.
 *
 * Adding a type means adding a row here and one component. Nothing else in the
 * editor needs to know it exists.
 */

export interface SectionMeta {
  label: string
  icon: IconName
  description: string
}

export const SECTION_META: Record<SectionTypeKey, SectionMeta> = {
  hero: { label: 'Hero', icon: 'sparkle', description: 'Name, title and the first impression.' },
  about: { label: 'About', icon: 'user', description: 'A short piece about you.' },
  experience: { label: 'Experience', icon: 'briefcase', description: 'Roles and what you did.' },
  education: { label: 'Education', icon: 'graduation', description: 'Degrees and courses.' },
  skills: { label: 'Skills', icon: 'layers', description: 'Grouped capabilities or stack.' },
  projects: { label: 'Projects', icon: 'folder', description: 'Selected work.' },
  services: {
    label: 'Services',
    icon: 'sliders',
    description: 'What you offer, and for how much.',
  },
  achievements: {
    label: 'Achievements',
    icon: 'award',
    description: 'Awards, talks, publications.',
  },
  contacts: { label: 'Contact', icon: 'mail', description: 'How people reach you.' },
  social_links: { label: 'Social links', icon: 'link', description: 'Profiles elsewhere.' },
}

/** Lazy so the editor's initial bundle carries only what is on screen. */
export const SECTION_EDITORS: Record<SectionTypeKey, Component> = {
  hero: defineAsyncComponent(() => import('./HeroSectionEditor.vue')),
  about: defineAsyncComponent(() => import('./AboutSectionEditor.vue')),
  experience: defineAsyncComponent(() => import('./ExperienceSectionEditor.vue')),
  education: defineAsyncComponent(() => import('./EducationSectionEditor.vue')),
  skills: defineAsyncComponent(() => import('./SkillsSectionEditor.vue')),
  projects: defineAsyncComponent(() => import('./ProjectsSectionEditor.vue')),
  services: defineAsyncComponent(() => import('./ServicesSectionEditor.vue')),
  achievements: defineAsyncComponent(() => import('./AchievementsSectionEditor.vue')),
  contacts: defineAsyncComponent(() => import('./ContactsSectionEditor.vue')),
  social_links: defineAsyncComponent(() => import('./SocialLinksSectionEditor.vue')),
}

export const SECTION_ORDER: SectionTypeKey[] = [
  'hero',
  'about',
  'experience',
  'projects',
  'skills',
  'education',
  'services',
  'achievements',
  'contacts',
  'social_links',
]
