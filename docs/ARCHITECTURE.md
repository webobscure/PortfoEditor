# Portfoedit — Architecture

SaaS portfolio builder. Laravel 12 API + Vue 3 SPA. Users edit structured content,
pick a template, see a live preview, download a self-contained static site.

---

## 1. Repository layout

```
/backend            Laravel 12 API (PHP 8.3+, PostgreSQL, Sanctum)
/frontend           Vue 3 + Vite + TS SPA
/docs               architecture, decisions
```

Two directories, one repo. Rationale: the two halves have completely different
toolchains (composer vs npm) and different deploy targets (PHP-FPM vs static CDN).
A JS monorepo tool (turbo/pnpm workspaces) would only wrap the frontend half and
buys nothing. Vite is pointed at the backend template directory with an alias, so
shared assets need no build-time copy step.

---

## 2. Core principle — content is data, never HTML

A portfolio is `Portfolio` + ordered `PortfolioSection[]`. Section payload lives in
a validated JSONB `data` column. No user HTML, no user JS, ever — not in v1, not
behind a flag. Everything the user types is escaped at render time on both the
preview and export paths.

Because content is template-agnostic, switching `template_key` is a single column
write. No data migration, no loss. A template that does not support a section type
simply does not render it; the row stays in the database untouched.

---

## 3. Render layer — the parity problem

Requirement: the live preview and the exported ZIP must look **identical**.
Naively that means two renderers that drift apart within a week.

### Decision

| Layer | Single source | Consumed by |
|---|---|---|
| Template CSS | `backend/resources/templates/<key>/styles.css` | preview (Vite alias, `?raw`) **and** export (file copy) |
| Template JS | `.../script.js` | same, copied verbatim, optional per template |
| Theme tokens | `theme.tokens.json` + a token compiler | `ThemeCompiler.php` and `themeCompiler.ts` |
| Markup | template renderer | `Renderer.php` (export) and `renderer.ts` (preview) |

CSS and JS are physically the same bytes on both paths — zero drift by construction.
Only markup exists twice, and that duplication is held in place by a **golden-file
parity test**:

```
backend/tests/Fixtures/golden/<template>.html   <- committed
Pest:   PHP renderer(demo portfolio)  === golden
Vitest: TS  renderer(demo portfolio)  === golden
```

Both suites assert against the *same* file. Change one renderer, the other suite
goes red. Drift becomes a build failure instead of a support ticket.

### Why the preview is an iframe

The preview renders the **exact export HTML string** into a sandboxed iframe.

* Device switching must be honest. If the preview were a scaled `<div>`, the
  template's own media queries would evaluate against the browser window, not the
  simulated 390px viewport. An iframe sized 1440/768/390 and then CSS-`transform`
  scaled into the canvas evaluates media queries correctly.
* Template CSS cannot collide with the editor's Tailwind styles.
* What you see is literally the artifact that gets zipped.

Updates are patched into the live iframe document with `morphdom`, so scroll
position, focus and CSS transitions survive a keystroke. No reload flash.

Interaction bridge: the renderer stamps `data-pf-section="<id>"` on each section
root. A small script injected only in preview mode (never in export) forwards
clicks up via `postMessage`, giving click-section-in-preview -> select-in-editor,
and the host scrolls the iframe when a sidebar row is clicked.

---

## 4. Database schema

PostgreSQL. `json()` columns map to `jsonb` on pgsql.

### users
`id, name, email (unique), password, avatar_media_id?, timestamps`
Stock Laravel. Sanctum SPA cookie auth, no token table needed for the SPA, but
`personal_access_tokens` stays for future API clients.

### portfolios
```
id            bigint pk
user_id       bigint fk -> users, cascade delete
name          varchar(120)
slug          varchar(80) unique          -- globally unique: reserved now so
                                          -- slug.example.com publishing needs
                                          -- no migration later
template_key  varchar(64)                 -- code-defined, not a fk
status        varchar(16)  draft|published
settings      jsonb                       -- theme: colors/typography/layout/buttons
meta          jsonb                       -- seo: title, description, og image
published_at  timestamp null              -- future publishing
timestamps, soft deletes
index (user_id, updated_at desc)
```
`template_key` is a plain string, not a foreign key, because templates are
code-defined (see §6). A `templates` table would need a migration for every design
change and would let the DB drift from what the code can actually render.

### portfolio_sections
```
id           bigint pk
portfolio_id bigint fk -> portfolios, cascade delete
type         varchar(32)      -- hero|about|experience|...
position     int
enabled      bool default true
data         jsonb            -- shape validated per type, server side
settings     jsonb            -- per-section overrides
timestamps
index (portfolio_id, position)
```
No unique constraint on `(portfolio_id, type)`. Duplicate sections of one type
(two project galleries) are a plausible v2 feature and the constraint would be a
migration to remove. Uniqueness where it matters (one hero) is enforced in the
service layer.

### media
```
id            bigint pk
user_id       bigint fk       -- ownership check without joining portfolios
portfolio_id  bigint fk null  -- null = account-level (avatar)
disk          varchar(32)     -- 'public' now, 's3' later, per row
path          varchar(255)    -- random name, never user-supplied
mime, size, width, height
original_name varchar(255)    -- display only, never used on disk
timestamps
```
`disk` per row, so a future migration to R2 does not invalidate existing rows —
old rows keep serving from `public` while new ones land on `s3`.
Files are never stored in the database; the DB holds a pointer.

### portfolio_exports
```
id, portfolio_id fk, status queued|processing|completed|failed,
disk, path, size, error text null, expires_at, timestamps
```
Present from day one even though MVP export is synchronous. The row is what makes
moving to a queue a no-op: the controller already returns an export record and the
frontend already polls it.

---

## 5. Backend structure

```
app/
  Http/
    Controllers/Api/     thin: authorize, validate, call service, return resource
    Requests/            one FormRequest per write endpoint
    Resources/           API shape, never leak model internals
  Models/
  Policies/              PortfolioPolicy, MediaPolicy
  Services/
    Portfolio/           PortfolioService, SectionService, SectionDataValidator
    Templates/           TemplateRegistry, Template, ThemeCompiler
    Export/              PortfolioExportService, PortfolioRenderer,
                         AssetCollector, ZipBuilder
    Media/               MediaService, ImageProcessor
  Support/
resources/templates/<key>/   template.json, styles.css, script.js?, Renderer.php,
                             preview.webp, thumb.webp
```

Section validation is a registry: `SectionSchemaRegistry` maps a type to a rule
array. `POST/PATCH` of a section merges the base rules with the type's rules, so
an unknown key is rejected rather than silently persisted. This is what keeps
`jsonb` from becoming a dumping ground.

---

## 6. Template system

Code-defined, discovered from disk, cached. `template.json`:

```json
{
  "key": "minimal",
  "name": "Minimal",
  "description": "...",
  "tags": ["designer", "product"],
  "supported_sections": ["hero","about",...],
  "fonts": { "heading": [...], "body": [...] },
  "color_schemes": [ { "key": "paper", "name": "Paper", "tokens": {...} } ],
  "defaults": { "settings": { ... } },
  "capabilities": { "script": false }
}
```

`GET /api/templates` returns this list; the frontend needs no hardcoded knowledge
of any template. Adding a fourth template = one directory + one Vue-free TS
renderer module. No core code changes.

---

## 7. Export pipeline

```
Portfolio -> load sections+settings -> TemplateRegistry::resolve
  -> PortfolioRenderer::render(mode: export)   escaped HTML
  -> AssetCollector                            css, optional js, referenced media
  -> ZipBuilder                                stream to temp, store on disk
  -> portfolio_exports row -> signed download URL
```

Output:
```
index.html
assets/css/styles.css
assets/js/app.js        (only if the template declares capabilities.script)
assets/images/*.webp
```
No CDN, no API calls, no service URLs, no npm. Image paths in the rendered HTML
are rewritten from storage URLs to relative `assets/images/...` by the
AssetCollector before the HTML is written, so the archive is position-independent
and opens from `file://`.

MVP runs it synchronously (a portfolio is a handful of KB plus images). The
service is queue-shaped already: `ExportPortfolioJob` wraps the same service call,
flipping to async is a config change, not a rewrite.

---

## 8. Frontend structure

```
src/
  api/            axios client, one module per resource. No fetch in components.
  components/ui/  design system: Button, Input, Select, Popover, Modal, Drawer,
                  Tooltip, Toast, Skeleton, EmptyState, Switch, Segmented
  composables/    useAutosave, useUndoRedo, useToast, useShortcuts, useDeviceFrame
  layouts/        AppLayout (dashboard chrome), EditorLayout (3 columns)
  pages/          Auth/, Dashboard, Templates, Portfolio/Create, Editor
  stores/         auth, portfolios, editor, templates, ui
  editor/
    components/   SectionList, SectionRow, Toolbar, TemplatePanel
    sections/     HeroSectionEditor.vue, AboutSectionEditor.vue, ...
                  + registry.ts mapping type -> component + icon + label
    preview/      PreviewCanvas.vue, PreviewFrame.vue, bridge.ts
    settings/     ThemePanel, ColorControls, TypographyControls, LayoutControls
  templates/      <key>/renderer.ts  (mirrors the PHP renderer)
                  shared/ html.ts (escape + tag helpers), tokens.ts
  types/
```

One editor component per section type, chosen through a registry — never one
mega-form with a thousand `v-if`s. Adding a section type touches the registry and
adds two files (Vue editor + renderer branch).

The editor store is the single writer of portfolio state. Autosave observes it
(debounce 700ms, coalescing, retry with backoff, `beforeunload` guard). Undo/redo
is a bounded snapshot history over the same store.

---

## 9. Security

* No user HTML or JS. Every string is escaped at render time on both paths.
* Uploads: mime sniffed server side (not from the request header), size capped,
  re-encoded through Intervention Image, random storage filenames.
* Policies on every portfolio/section/media route; ownership never inferred from
  the request body.
* Rate limits on auth, upload and export.
* Export HTML has no `<script>` unless the template ships one, and template
  scripts are developer-authored files, never user content.

---

## 10. Roadmap

1. Backend foundation: schema, models, policies, Sanctum
2. Template registry + theme compiler + 3 templates (CSS + PHP renderer)
3. Portfolio/section API + validation registry + demo seeder
4. Frontend foundation: design system, auth, routing
5. Dashboard + create flow
6. Editor shell, section registry, live preview iframe
7. Theme panel, template switcher
8. Media upload
9. Autosave, undo/redo, shortcuts
10. Export service + ZIP
11. Tests: Pest (auth, CRUD, ownership, reorder, template switch, upload, export,
    render parity) + Vitest (stores, composables, renderer parity)
