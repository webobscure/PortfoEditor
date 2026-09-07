import {
  attrs,
  escape,
  mailto,
  pad2,
  paragraphs,
  safeUrl,
  summarise,
  tag,
  tel,
  ucfirst,
  voidTag,
} from './html'

import type { SectionTypeKey } from '@/types'

/**
 * The template renderers that drive the live preview.
 *
 * These are ports of the PHP renderers in app/Services/Templates. The editor
 * renders locally so typing is instant, and the export renders on the server so
 * the download needs no JavaScript — but both produce the same document, and
 * the golden-file tests fail if they stop doing so.
 */

export const MODE_PREVIEW = 'preview'
export const MODE_EXPORT = 'export'

export interface RenderImage {
  url: string
  width: number | null
  height: number | null
  alt: string
}

export interface RenderSection {
  id: number | string
  type: SectionTypeKey
  data: Record<string, unknown>
  settings: Record<string, unknown>
}

export interface RenderContext {
  mode: string
  templateKey: string
  portfolio: { name: string; slug: string; meta: Record<string, unknown> }
  sections: RenderSection[]
  images: Record<number, RenderImage>
  themeCss: string
  fontCss: string
  styleHref: string
  scriptSrc?: string | null
  ogImageUrl?: string | null
  lang?: string
  /** Editor-only: the section currently selected, outlined in the preview. */
  selectedSectionId?: number | string | null
  /** Editor-only: enables click-to-select instrumentation. */
  interactive?: boolean
}

const SECTION_LABELS: Record<SectionTypeKey, string> = {
  hero: 'Hero',
  about: 'About',
  experience: 'Experience',
  education: 'Education',
  skills: 'Skills',
  projects: 'Projects',
  services: 'Services',
  achievements: 'Achievements',
  contacts: 'Contact',
  social_links: 'Social links',
}

export function sectionLabel(type: SectionTypeKey): string {
  return SECTION_LABELS[type] ?? type
}

// ------------------------------------------------------------- accessors

function str(source: Record<string, unknown>, key: string): string {
  const value = source[key]

  return value === null || value === undefined ? '' : String(value)
}

function rows(source: Record<string, unknown>, key = 'items'): Record<string, unknown>[] {
  const value = source[key]

  return Array.isArray(value)
    ? (value.filter((item) => item !== null && typeof item === 'object') as Record<
        string,
        unknown
      >[])
    : []
}

function strings(source: Record<string, unknown>, key: string): string[] {
  const value = source[key]

  return Array.isArray(value) ? value.map((item) => String(item ?? '')) : []
}

// ------------------------------------------------------------ base class

export class TemplateRenderer {
  bodyClass(): string {
    return 'pf'
  }

  render(ctx: RenderContext): string {
    return [
      '<!DOCTYPE html>',
      `<html lang="${escape(ctx.lang ?? 'en')}">`,
      '<head>',
      this.head(ctx),
      '</head>',
      `<body class="${escape(this.bodyClassFor(ctx))}">`,
      this.body(ctx),
      '</body>',
      '</html>',
      '',
    ].join('\n')
  }

  /** The editor adds a marker class so preview-only affordances can attach. */
  protected bodyClassFor(ctx: RenderContext): string {
    return ctx.interactive ? `${this.bodyClass()} pf-editing` : this.bodyClass()
  }

  protected head(ctx: RenderContext): string {
    const title = documentTitle(ctx)
    const description = summarise(documentDescription(ctx))

    const lines = [
      '<meta charset="utf-8">',
      '<meta name="viewport" content="width=device-width, initial-scale=1">',
      `<title>${escape(title)}</title>`,
    ]

    if (description !== '') {
      lines.push(voidTag('meta', { name: 'description', content: description }))
    }

    lines.push(voidTag('meta', { property: 'og:type', content: 'website' }))
    lines.push(voidTag('meta', { property: 'og:title', content: title }))

    if (description !== '') {
      lines.push(voidTag('meta', { property: 'og:description', content: description }))
    }

    if (ctx.ogImageUrl) {
      lines.push(voidTag('meta', { property: 'og:image', content: ctx.ogImageUrl }))
      lines.push(voidTag('meta', { name: 'twitter:card', content: 'summary_large_image' }))
    } else {
      lines.push(voidTag('meta', { name: 'twitter:card', content: 'summary' }))
    }

    lines.push(`<style>${ctx.fontCss}</style>`)
    lines.push(`<style>${ctx.themeCss}</style>`)
    lines.push(voidTag('link', { rel: 'stylesheet', href: ctx.styleHref }))

    return lines.join('\n')
  }

  protected body(ctx: RenderContext): string {
    const parts = [
      this.header(ctx),
      '<main class="pf-main" id="content">',
      this.sections(ctx),
      '</main>',
      this.footer(ctx),
    ]

    if (ctx.scriptSrc) {
      parts.push(tag('script', { src: ctx.scriptSrc, defer: true }))
    }

    return parts.filter((part) => part !== '').join('\n')
  }

  protected header(ctx: RenderContext): string {
    const links: string[] = []

    for (const section of ctx.sections) {
      if (section.type === 'hero' || section.type === 'social_links') continue

      const label = this.navLabel(section.type, section.data)

      if (label === '') continue

      links.push(
        tag('a', { class: 'pf-nav__link', href: '#' + anchor(section.type) }, escape(label)),
      )
    }

    if (links.length === 0) return ''

    const brand = tag('a', { class: 'pf-nav__brand', href: '#top' }, escape(portfolioName(ctx)))

    return (
      '<header class="pf-nav" id="top">' +
      '<div class="pf-nav__inner">' +
      brand +
      '<nav class="pf-nav__links" aria-label="Sections">' +
      links.join('') +
      '</nav>' +
      '</div>' +
      '</header>'
    )
  }

  protected footer(ctx: RenderContext): string {
    const year = new Date().getFullYear()

    return (
      '<footer class="pf-footer">' +
      '<div class="pf-shell">' +
      tag('p', { class: 'pf-footer__text' }, `© ${year} ` + escape(portfolioName(ctx))) +
      '</div>' +
      '</footer>'
    )
  }

  protected sections(ctx: RenderContext): string {
    const out: string[] = []

    for (const section of ctx.sections) {
      const inner = this.renderSection(section.type, section.data, ctx)

      if (inner.trim() === '') continue

      const classes = ['pf-section', `pf-section--${section.type}`]

      if (ctx.interactive && String(ctx.selectedSectionId ?? '') === String(section.id)) {
        classes.push('is-selected')
      }

      out.push(
        '<section' +
          attrs({
            id: anchor(section.type),
            class: classes.join(' '),
            'data-pf-section': String(section.id),
            'data-pf-type': section.type,
          }) +
          '>' +
          inner +
          '</section>',
      )
    }

    return out.join('\n')
  }

  protected renderSection(
    type: SectionTypeKey,
    data: Record<string, unknown>,
    ctx: RenderContext,
  ): string {
    switch (type) {
      case 'hero':
        return this.hero(data, ctx)
      case 'about':
        return this.about(data, ctx)
      case 'experience':
        return this.experience(data, ctx)
      case 'education':
        return this.education(data, ctx)
      case 'skills':
        return this.skills(data, ctx)
      case 'projects':
        return this.projects(data, ctx)
      case 'services':
        return this.services(data, ctx)
      case 'achievements':
        return this.achievements(data, ctx)
      case 'contacts':
        return this.contacts(data, ctx)
      case 'social_links':
        return this.socialLinks(data, ctx)
      default:
        return ''
    }
  }

  // ---------------------------------------------------------- defaults

  protected hero(data: Record<string, unknown>, ctx: RenderContext): string {
    const name = str(data, 'name')
    const title = str(data, 'title')
    const intro = str(data, 'intro')

    if ((name + title + intro).trim() === '') return ''

    const photo = this.figure(image(ctx, data['photo_media_id']), name, 'pf-hero__photo')
    const alignment = data['alignment'] === 'center' ? 'is-center' : 'is-left'
    const layout = photo !== '' ? ' has-photo' : ''

    const body =
      '<div class="pf-hero__text">' +
      (title !== '' ? tag('p', { class: 'pf-hero__eyebrow' }, escape(title)) : '') +
      tag('h1', { class: 'pf-hero__name' }, escape(name)) +
      paragraphs(intro, 'pf-hero__intro') +
      this.ctaRow(data) +
      this.inlineSocial(data, ctx) +
      '</div>'

    return `<div class="pf-shell pf-hero ${alignment}${layout}">` + body + photo + '</div>'
  }

  protected about(data: Record<string, unknown>, ctx: RenderContext): string {
    const body = paragraphs(str(data, 'body'), 'pf-about__body')

    if (body === '') return ''

    let highlights = ''

    for (const highlight of rows(data, 'highlights')) {
      highlights +=
        '<div class="pf-highlight">' +
        tag('dt', { class: 'pf-highlight__value' }, escape(str(highlight, 'value'))) +
        tag('dd', { class: 'pf-highlight__label' }, escape(str(highlight, 'label'))) +
        '</div>'
    }

    const photo = this.figure(
      image(ctx, data['photo_media_id']),
      str(data, 'heading') || 'About',
      'pf-about__photo',
    )

    return (
      '<div class="pf-shell pf-about">' +
      this.heading(data, 'About') +
      '<div class="pf-about__grid">' +
      '<div class="pf-about__content">' +
      body +
      (highlights !== '' ? '<dl class="pf-highlights">' + highlights + '</dl>' : '') +
      '</div>' +
      photo +
      '</div>' +
      '</div>'
    )
  }

  protected experience(data: Record<string, unknown>, _ctx: RenderContext): string {
    let list = ''

    for (const item of rows(data)) {
      const period = this.period(item)
      const meta = [str(item, 'company'), str(item, 'location')].filter((v) => v.trim() !== '')
      let tags = ''

      for (const value of strings(item, 'tags')) {
        tags += tag('li', { class: 'pf-tag' }, escape(value))
      }

      list +=
        '<li class="pf-entry">' +
        (period !== '' ? tag('p', { class: 'pf-entry__period' }, escape(period)) : '') +
        '<div class="pf-entry__body">' +
        tag('h3', { class: 'pf-entry__title' }, escape(str(item, 'role'))) +
        (meta.length > 0 ? tag('p', { class: 'pf-entry__meta' }, escape(meta.join(' · '))) : '') +
        paragraphs(str(item, 'description'), 'pf-entry__text') +
        (tags !== '' ? '<ul class="pf-tags">' + tags + '</ul>' : '') +
        '</div>' +
        '</li>'
    }

    if (list === '') return ''

    return (
      '<div class="pf-shell">' +
      this.heading(data, 'Experience') +
      '<ul class="pf-entries">' +
      list +
      '</ul></div>'
    )
  }

  protected education(data: Record<string, unknown>, _ctx: RenderContext): string {
    let list = ''

    for (const item of rows(data)) {
      const period = this.period(item)
      const meta = [str(item, 'institution'), str(item, 'location')].filter((v) => v.trim() !== '')

      list +=
        '<li class="pf-entry">' +
        (period !== '' ? tag('p', { class: 'pf-entry__period' }, escape(period)) : '') +
        '<div class="pf-entry__body">' +
        tag('h3', { class: 'pf-entry__title' }, escape(str(item, 'degree'))) +
        (meta.length > 0 ? tag('p', { class: 'pf-entry__meta' }, escape(meta.join(' · '))) : '') +
        paragraphs(str(item, 'description'), 'pf-entry__text') +
        '</div>' +
        '</li>'
    }

    if (list === '') return ''

    return (
      '<div class="pf-shell">' +
      this.heading(data, 'Education') +
      '<ul class="pf-entries">' +
      list +
      '</ul></div>'
    )
  }

  protected skills(data: Record<string, unknown>, _ctx: RenderContext): string {
    let groups = ''

    for (const group of rows(data, 'groups')) {
      let chips = ''

      for (const skill of strings(group, 'items')) {
        chips += tag('li', { class: 'pf-chip' }, escape(skill))
      }

      if (chips === '') continue

      groups +=
        '<div class="pf-skillgroup">' +
        tag('h3', { class: 'pf-skillgroup__name' }, escape(str(group, 'name'))) +
        '<ul class="pf-chips">' +
        chips +
        '</ul>' +
        '</div>'
    }

    if (groups === '') return ''

    return (
      '<div class="pf-shell">' +
      this.heading(data, 'Skills') +
      '<div class="pf-skills">' +
      groups +
      '</div></div>'
    )
  }

  protected projects(data: Record<string, unknown>, ctx: RenderContext): string {
    let cards = ''

    for (const item of rows(data)) {
      cards += this.projectCard(item, ctx)
    }

    if (cards === '') return ''

    return (
      '<div class="pf-shell">' +
      this.heading(data, 'Selected work') +
      paragraphs(str(data, 'intro'), 'pf-section__intro') +
      '<div class="pf-projects">' +
      cards +
      '</div>' +
      '</div>'
    )
  }

  protected projectCard(item: Record<string, unknown>, ctx: RenderContext): string {
    const title = str(item, 'title')

    if (title.trim() === '') return ''

    const media = this.figure(image(ctx, item['image_media_id']), title, 'pf-project__media')

    let tech = ''

    for (const technology of strings(item, 'technologies')) {
      tech += tag('li', { class: 'pf-chip' }, escape(technology))
    }

    let links = ''

    for (const [key, label] of [
      ['url', 'Visit'],
      ['github_url', 'Source'],
    ] as const) {
      const href = safeUrl(item[key])

      if (href === '') continue

      links += tag(
        'a',
        { class: 'pf-project__link', href, target: '_blank', rel: 'noopener noreferrer' },
        escape(label) + '<span aria-hidden="true"> ↗</span>',
      )
    }

    const year = str(item, 'year')

    return (
      '<article class="pf-project">' +
      media +
      '<div class="pf-project__body">' +
      (year !== '' ? tag('p', { class: 'pf-project__year' }, escape(year)) : '') +
      tag('h3', { class: 'pf-project__title' }, escape(title)) +
      paragraphs(str(item, 'description'), 'pf-project__text') +
      (tech !== '' ? '<ul class="pf-chips">' + tech + '</ul>' : '') +
      (links !== '' ? '<div class="pf-project__links">' + links + '</div>' : '') +
      '</div>' +
      '</article>'
    )
  }

  protected services(data: Record<string, unknown>, _ctx: RenderContext): string {
    let cards = ''

    for (const item of rows(data)) {
      const title = str(item, 'title')

      if (title.trim() === '') continue

      const price = str(item, 'price')

      cards +=
        '<article class="pf-service">' +
        tag('h3', { class: 'pf-service__title' }, escape(title)) +
        paragraphs(str(item, 'description'), 'pf-service__text') +
        (price !== '' ? tag('p', { class: 'pf-service__price' }, escape(price)) : '') +
        '</article>'
    }

    if (cards === '') return ''

    return (
      '<div class="pf-shell">' +
      this.heading(data, 'Services') +
      '<div class="pf-services">' +
      cards +
      '</div></div>'
    )
  }

  protected achievements(data: Record<string, unknown>, _ctx: RenderContext): string {
    let list = ''

    for (const item of rows(data)) {
      const title = str(item, 'title')

      if (title.trim() === '') continue

      const href = safeUrl(item['url'])
      const heading =
        href !== ''
          ? tag('a', { href, target: '_blank', rel: 'noopener noreferrer' }, escape(title))
          : escape(title)

      const meta = [str(item, 'issuer'), str(item, 'date')].filter((v) => v.trim() !== '')

      list +=
        '<li class="pf-award">' +
        tag('h3', { class: 'pf-award__title' }, heading) +
        (meta.length > 0 ? tag('p', { class: 'pf-award__meta' }, escape(meta.join(' · '))) : '') +
        paragraphs(str(item, 'description'), 'pf-award__text') +
        '</li>'
    }

    if (list === '') return ''

    return (
      '<div class="pf-shell">' +
      this.heading(data, 'Achievements') +
      '<ul class="pf-awards">' +
      list +
      '</ul></div>'
    )
  }

  protected contacts(data: Record<string, unknown>, _ctx: RenderContext): string {
    const email = str(data, 'email')
    const emailHref = mailto(email)
    let list = ''

    const entries: [string, string, string][] = [
      ['Email', email, emailHref],
      ['Phone', str(data, 'phone'), tel(data['phone'])],
      ['Location', str(data, 'location'), ''],
      ['Availability', str(data, 'availability'), ''],
    ]

    for (const [label, value, href] of entries) {
      if (value.trim() === '') continue

      const rendered =
        href !== '' ? tag('a', { class: 'pf-contact__link', href }, escape(value)) : escape(value)

      list +=
        '<div class="pf-contact">' +
        tag('dt', { class: 'pf-contact__label' }, escape(label)) +
        tag('dd', { class: 'pf-contact__value' }, rendered) +
        '</div>'
    }

    const intro = paragraphs(str(data, 'intro'), 'pf-section__intro')

    if (list === '' && intro === '') return ''

    let cta = ''
    const ctaText = str(data, 'cta_text')

    if (emailHref !== '' && ctaText.trim() !== '') {
      cta = tag('a', { class: 'pf-btn pf-btn--primary', href: emailHref }, escape(ctaText))
    }

    return (
      '<div class="pf-shell pf-contacts">' +
      this.heading(data, 'Get in touch') +
      intro +
      (list !== '' ? '<dl class="pf-contacts__grid">' + list + '</dl>' : '') +
      cta +
      '</div>'
    )
  }

  protected socialLinks(data: Record<string, unknown>, _ctx: RenderContext): string {
    const links = this.socialList(data, 'pf-social__link')

    if (links === '') return ''

    return (
      '<div class="pf-shell"><nav class="pf-social" aria-label="Social links">' +
      links +
      '</nav></div>'
    )
  }

  // ----------------------------------------------------------- helpers

  protected heading(
    data: Record<string, unknown>,
    fallback: string,
    className = 'pf-section__title',
  ): string {
    const heading = str(data, 'heading').trim() || fallback

    return tag('h2', { class: className }, escape(heading))
  }

  protected period(item: Record<string, unknown>): string {
    const start = str(item, 'start').trim()
    const end = item['current'] ? 'Present' : str(item, 'end').trim()

    return [start, end]
      .filter((v) => v !== '')
      .join(' — ')
      .trim()
  }

  protected figure(img: RenderImage | null, alt: string, className: string): string {
    if (!img) return ''

    const rendered = voidTag('img', {
      src: img.url,
      alt: img.alt !== '' ? img.alt : alt,
      width: img.width !== null ? String(img.width) : null,
      height: img.height !== null ? String(img.height) : null,
      loading: 'lazy',
      decoding: 'async',
    })

    return `<figure class="${escape(className)}">${rendered}</figure>`
  }

  protected ctaRow(data: Record<string, unknown>): string {
    let buttons = ''

    for (const [textKey, urlKey, className] of [
      ['cta_text', 'cta_url', 'pf-btn pf-btn--primary'],
      ['secondary_cta_text', 'secondary_cta_url', 'pf-btn pf-btn--ghost'],
    ] as const) {
      const text = str(data, textKey).trim()
      const href = safeUrl(data[urlKey])

      if (text === '' || href === '') continue

      buttons += tag(
        'a',
        { class: className, href, target: '_blank', rel: 'noopener noreferrer' },
        escape(text),
      )
    }

    return buttons === '' ? '' : '<div class="pf-hero__actions">' + buttons + '</div>'
  }

  protected inlineSocial(data: Record<string, unknown>, ctx: RenderContext): string {
    if (!data['show_social']) return ''

    for (const section of ctx.sections) {
      if (section.type !== 'social_links') continue

      const links = this.socialList(section.data, 'pf-hero__social-link')

      return links === ''
        ? ''
        : '<nav class="pf-hero__social" aria-label="Social links">' + links + '</nav>'
    }

    return ''
  }

  protected socialList(data: Record<string, unknown>, className: string): string {
    let links = ''

    for (const item of rows(data)) {
      const href = safeUrl(item['url'])

      if (href === '') continue

      const platform = str(item, 'platform') || 'website'
      const label = str(item, 'label').trim() || ucfirst(platform)

      links += tag(
        'a',
        {
          class: className,
          href,
          target: '_blank',
          rel: 'noopener noreferrer',
          'data-platform': platform,
        },
        escape(label),
      )
    }

    return links
  }

  protected navLabel(type: SectionTypeKey, data: Record<string, unknown>): string {
    return str(data, 'heading').trim() || sectionLabel(type)
  }
}

// -------------------------------------------------------------- concrete

/**
 * Minimal presents work as a numbered sequence of large, near-full-bleed cases
 * rather than a grid of tiles.
 */
class MinimalRenderer extends TemplateRenderer {
  bodyClass(): string {
    return 'pf pf-t-minimal'
  }

  protected projects(data: Record<string, unknown>, ctx: RenderContext): string {
    let cards = ''
    let index = 0

    for (const item of rows(data)) {
      const card = this.numberedProject(item, index + 1, ctx)

      if (card === '') continue

      index++
      cards += card
    }

    if (cards === '') return ''

    return (
      '<div class="pf-shell">' +
      this.heading(data, 'Selected work') +
      paragraphs(str(data, 'intro'), 'pf-section__intro') +
      '<div class="pf-projects">' +
      cards +
      '</div>' +
      '</div>'
    )
  }

  private numberedProject(
    item: Record<string, unknown>,
    index: number,
    ctx: RenderContext,
  ): string {
    const title = str(item, 'title')

    if (title.trim() === '') return ''

    const media = this.figure(image(ctx, item['image_media_id']), title, 'pf-project__media')

    let tech = ''

    for (const technology of strings(item, 'technologies')) {
      tech += tag('li', { class: 'pf-chip' }, escape(technology))
    }

    let links = ''

    for (const [key, label] of [
      ['url', 'View project'],
      ['github_url', 'Source'],
    ] as const) {
      const href = safeUrl(item[key])

      if (href === '') continue

      links += tag(
        'a',
        { class: 'pf-project__link', href, target: '_blank', rel: 'noopener noreferrer' },
        escape(label) + '<span aria-hidden="true"> ↗</span>',
      )
    }

    const year = str(item, 'year')

    return (
      '<article class="pf-project">' +
      media +
      '<div class="pf-project__body">' +
      '<div>' +
      tag('p', { class: 'pf-project__index' }, pad2(index)) +
      tag('h3', { class: 'pf-project__title' }, escape(title)) +
      (year !== '' ? tag('p', { class: 'pf-project__year' }, escape(year)) : '') +
      '</div>' +
      '<div>' +
      paragraphs(str(item, 'description'), 'pf-project__text') +
      (tech !== '' ? '<ul class="pf-chips">' + tech + '</ul>' : '') +
      (links !== '' ? '<div class="pf-project__links">' + links + '</div>' : '') +
      '</div>' +
      '</div>' +
      '</article>'
    )
  }
}

/**
 * Developer numbers its section headings and lifts the availability line out of
 * the contacts section into a hero status pill, so it is written once.
 */
class DeveloperDarkRenderer extends TemplateRenderer {
  private sectionIndex = 0

  bodyClass(): string {
    return 'pf pf-t-developer-dark'
  }

  render(ctx: RenderContext): string {
    this.sectionIndex = 0

    return super.render(ctx)
  }

  protected heading(
    data: Record<string, unknown>,
    fallback: string,
    className = 'pf-section__title',
  ): string {
    const heading = str(data, 'heading').trim() || fallback

    return (
      '<h2' +
      attrs({ class: className, 'data-index': pad2(++this.sectionIndex) }) +
      '>' +
      escape(heading) +
      '</h2>'
    )
  }

  protected hero(data: Record<string, unknown>, ctx: RenderContext): string {
    const name = str(data, 'name')
    const title = str(data, 'title')
    const intro = str(data, 'intro')

    if ((name + title + intro).trim() === '') return ''

    const photo = this.figure(image(ctx, data['photo_media_id']), name, 'pf-hero__photo')
    const layout = photo !== '' ? ' has-photo' : ''
    const alignment = data['alignment'] === 'center' ? 'is-center' : 'is-left'

    const body =
      '<div class="pf-hero__text">' +
      this.availabilityPill(ctx) +
      (title !== '' ? tag('p', { class: 'pf-hero__eyebrow' }, escape(title)) : '') +
      tag('h1', { class: 'pf-hero__name' }, escape(name)) +
      paragraphs(intro, 'pf-hero__intro') +
      this.ctaRow(data) +
      this.inlineSocial(data, ctx) +
      '</div>'

    return `<div class="pf-shell pf-hero ${alignment}${layout}">` + body + photo + '</div>'
  }

  private availabilityPill(ctx: RenderContext): string {
    for (const section of ctx.sections) {
      if (section.type !== 'contacts') continue

      const availability = str(section.data, 'availability').trim()

      if (availability !== '') {
        return tag('p', { class: 'pf-hero__status' }, escape(availability))
      }
    }

    return ''
  }
}

/**
 * Editorial presents work as case-study spreads: a running number beside the
 * title and the metadata pushed into a ruled aside.
 */
class EditorialRenderer extends TemplateRenderer {
  bodyClass(): string {
    return 'pf pf-t-editorial'
  }

  protected projects(data: Record<string, unknown>, ctx: RenderContext): string {
    let cards = ''
    let index = 0

    for (const item of rows(data)) {
      const card = this.caseStudy(item, index + 1, ctx)

      if (card === '') continue

      index++
      cards += card
    }

    if (cards === '') return ''

    return (
      '<div class="pf-shell">' +
      this.heading(data, 'Selected work') +
      paragraphs(str(data, 'intro'), 'pf-section__intro') +
      '<div class="pf-projects">' +
      cards +
      '</div>' +
      '</div>'
    )
  }

  private caseStudy(item: Record<string, unknown>, index: number, ctx: RenderContext): string {
    const title = str(item, 'title')

    if (title.trim() === '') return ''

    const header =
      '<div class="pf-project__header">' +
      tag('p', { class: 'pf-project__index' }, pad2(index)) +
      tag('h3', { class: 'pf-project__title' }, escape(title)) +
      '</div>'

    const media = this.figure(image(ctx, item['image_media_id']), title, 'pf-project__media')

    let aside = ''
    const year = str(item, 'year').trim()

    if (year !== '') {
      aside +=
        '<div>' +
        tag('p', { class: 'pf-project__label' }, 'Year') +
        tag('p', { class: 'pf-project__year' }, escape(year)) +
        '</div>'
    }

    let tech = ''

    for (const technology of strings(item, 'technologies')) {
      tech += tag('li', { class: 'pf-chip' }, escape(technology))
    }

    if (tech !== '') {
      aside +=
        '<div>' +
        tag('p', { class: 'pf-project__label' }, 'Discipline') +
        '<ul class="pf-chips">' +
        tech +
        '</ul>' +
        '</div>'
    }

    let links = ''

    for (const [key, label] of [
      ['url', 'View the work'],
      ['github_url', 'Source'],
    ] as const) {
      const href = safeUrl(item[key])

      if (href === '') continue

      links += tag(
        'a',
        { class: 'pf-project__link', href, target: '_blank', rel: 'noopener noreferrer' },
        escape(label),
      )
    }

    if (links !== '') {
      aside += '<div class="pf-project__links">' + links + '</div>'
    }

    return (
      '<article class="pf-project">' +
      header +
      media +
      '<div class="pf-project__body">' +
      paragraphs(str(item, 'description'), 'pf-project__text') +
      (aside !== '' ? '<div class="pf-project__aside">' + aside + '</div>' : '') +
      '</div>' +
      '</article>'
    )
  }
}

// ------------------------------------------------------------- registry

const RENDERERS: Record<string, () => TemplateRenderer> = {
  minimal: () => new MinimalRenderer(),
  'developer-dark': () => new DeveloperDarkRenderer(),
  editorial: () => new EditorialRenderer(),
}

/**
 * Resolve a renderer, falling back to Minimal.
 *
 * A template key the client does not recognise must never blank the preview —
 * the backend applies the same fallback.
 */
export function rendererFor(key: string): TemplateRenderer {
  return (RENDERERS[key] ?? RENDERERS['minimal'])()
}

export function renderDocument(ctx: RenderContext): string {
  return rendererFor(ctx.templateKey).render(ctx)
}

// -------------------------------------------------------------- context

function image(ctx: RenderContext, mediaId: unknown): RenderImage | null {
  if (mediaId === null || mediaId === undefined || mediaId === '') return null

  return ctx.images[Number(mediaId)] ?? null
}

function anchor(type: SectionTypeKey): string {
  return 'section-' + type.replace(/_/g, '-')
}

export function portfolioName(ctx: RenderContext): string {
  for (const section of ctx.sections) {
    if (section.type !== 'hero') continue

    const name = str(section.data, 'name').trim()

    if (name !== '') return name
  }

  return ctx.portfolio.name || 'Portfolio'
}

export function documentTitle(ctx: RenderContext): string {
  const title = String(ctx.portfolio.meta['title'] ?? '').trim()

  if (title !== '') return title

  let role = ''

  for (const section of ctx.sections) {
    if (section.type === 'hero') {
      role = str(section.data, 'title').trim()
      break
    }
  }

  const name = portfolioName(ctx)

  return role !== '' ? `${name} — ${role}` : name
}

export function documentDescription(ctx: RenderContext): string {
  const description = String(ctx.portfolio.meta['description'] ?? '').trim()

  if (description !== '') return description

  for (const section of ctx.sections) {
    if (section.type !== 'hero') continue

    const intro = str(section.data, 'intro').trim()

    if (intro !== '') return intro
  }

  return ''
}
