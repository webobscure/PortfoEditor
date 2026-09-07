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
  hero: { label: 'Обложка', icon: 'sparkle', description: 'Имя, профессия и первое впечатление.' },
  about: { label: 'О себе', icon: 'user', description: 'Короткий текст о вас.' },
  experience: { label: 'Опыт', icon: 'briefcase', description: 'Должности и чем вы занимались.' },
  education: { label: 'Образование', icon: 'graduation', description: 'Дипломы и курсы.' },
  skills: { label: 'Навыки', icon: 'layers', description: 'Сгруппированные умения или стек.' },
  projects: { label: 'Проекты', icon: 'folder', description: 'Избранные работы.' },
  services: {
    label: 'Услуги',
    icon: 'sliders',
    description: 'Что вы предлагаете и за сколько.',
  },
  achievements: {
    label: 'Достижения',
    icon: 'award',
    description: 'Награды, доклады, публикации.',
  },
  contacts: { label: 'Контакты', icon: 'mail', description: 'Как с вами связаться.' },
  social_links: { label: 'Соцсети', icon: 'link', description: 'Профили на других площадках.' },
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
