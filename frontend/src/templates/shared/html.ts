/**
 * Escaping and markup helpers.
 *
 * A direct port of App\Support\Html on the backend. The two implementations are
 * held together by the golden-file tests: PHP writes the expected document for
 * the demo portfolio, and the TypeScript suite asserts this renderer produces
 * the same bytes. If one side changes, the other side's suite fails.
 */

const SAFE_SCHEMES = ['http', 'https', 'mailto', 'tel']

// Matching control characters is the point: they are what a crafted URL
// uses to hide a scheme from a naive check.
// eslint-disable-next-line no-control-regex
const CONTROL_CHARACTERS = /[\u0000-\u001F\u007F]/g

/** htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') */
export function escape(value: string | null | undefined): string {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

/**
 * Normalise a user-supplied URL, returning '' when it is unusable.
 *
 * The validator already rejects anything that is not http/https, but the
 * renderer repeats the check because seeds, imports and future features write
 * to the same columns.
 */
export function safeUrl(value: unknown): string {
  let raw = String(value ?? '').trim()

  if (raw === '') return ''

  raw = raw.replace(CONTROL_CHARACTERS, '')

  if (raw.startsWith('//')) return ''

  const match = /^([A-Za-z][A-Za-z0-9+.-]*):/.exec(raw)

  if (!match) {
    return 'https://' + raw.replace(/^\/+/, '')
  }

  return SAFE_SCHEMES.includes(match[1].toLowerCase()) ? raw : ''
}

export function mailto(email: unknown): string {
  const value = String(email ?? '').trim()

  return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(value) ? `mailto:${value}` : ''
}

export function tel(phone: unknown): string {
  const digits = String(phone ?? '').replace(/[^0-9+]/g, '')

  return digits === '' ? '' : `tel:${digits}`
}

export type AttrValue = string | boolean | null | undefined

/** Attribute string in insertion order; '' / null / false drop the attribute. */
export function attrs(attributes: Record<string, AttrValue>): string {
  const parts: string[] = []

  for (const [name, value] of Object.entries(attributes)) {
    if (value === null || value === undefined || value === false || value === '') continue

    if (value === true) {
      parts.push(name)
      continue
    }

    parts.push(`${name}="${escape(String(value))}"`)
  }

  return parts.length === 0 ? '' : ' ' + parts.join(' ')
}

export function tag(
  name: string,
  attributes: Record<string, AttrValue> = {},
  children = '',
): string {
  return `<${name}${attrs(attributes)}>${children}</${name}>`
}

export function voidTag(name: string, attributes: Record<string, AttrValue> = {}): string {
  return `<${name}${attrs(attributes)}>`
}

/**
 * Render plain text as paragraphs. Blank lines separate paragraphs, single
 * newlines become <br>. No user markup survives this.
 */
export function paragraphs(text: unknown, className = ''): string {
  const value = String(text ?? '').trim()

  if (value === '') return ''

  const normalised = value.replace(/\r\n|\r/g, '\n')
  const blocks = normalised.split(/\n{2,}/)
  const attr = className !== '' ? ` class="${escape(className)}"` : ''
  const out: string[] = []

  for (const block of blocks) {
    const trimmed = block.trim()

    if (trimmed === '') continue

    out.push(
      `<p${attr}>` +
        trimmed
          .split('\n')
          .map((line) => escape(line.trim()))
          .join('<br>') +
        '</p>',
    )
  }

  return out.join('')
}

/** Collapse to a single line for meta tags. */
export function summarise(text: unknown, length = 160): string {
  const clean = String(text ?? '')
    .replace(/\s+/g, ' ')
    .trim()

  const characters = [...clean]

  if (clean === '' || characters.length <= length) return clean

  return (
    characters
      .slice(0, length - 1)
      .join('')
      .replace(/\s+$/, '') + '…'
  )
}

export function pad2(value: number): string {
  return value < 10 ? `0${value}` : String(value)
}

export function ucfirst(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1)
}
