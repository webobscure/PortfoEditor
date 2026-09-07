<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import SitePreviewFrame from '@/components/portfolio/SitePreviewFrame.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import SkeletonBlock from '@/components/ui/SkeletonBlock.vue'
import { templatesApi } from '@/api/templates'
import { useCatalogStore } from '@/stores/catalog'

import type { IconName } from '@/components/ui/icons'

/**
 * Public front page.
 *
 * The template gallery here is the same live render the editor and the
 * dashboard use — a visitor who has not signed up yet is looking at the real
 * output, not a marketing mock-up of it. /api/templates and the demo route are
 * public precisely so this page can do that without a session.
 */
const catalog = useCatalogStore()

const activeTemplate = ref('')

onMounted(async () => {
  await catalog.load()

  if (!activeTemplate.value) {
    activeTemplate.value = catalog.templates[0]?.key ?? ''
  }
})

const current = computed(() => catalog.template(activeTemplate.value) ?? null)

const demoUrl = computed(() =>
  activeTemplate.value ? templatesApi.demoUrl(activeTemplate.value) : '',
)

const steps: { icon: IconName; title: string; body: string }[] = [
  {
    icon: 'user',
    title: 'Заполняете работы',
    body: 'Опыт, проекты, навыки, контакты. Поля уже заполнены примерами — их можно править, а не смотреть на пустой экран.',
  },
  {
    icon: 'palette',
    title: 'Выбираете шаблон',
    body: 'Содержимое переносится между шаблонами без потерь. Передумали на третий день — меняете шаблон, а не переписываете сайт.',
  },
  {
    icon: 'download',
    title: 'Скачиваете сайт',
    body: 'ZIP с HTML, CSS и шрифтами. Загружаете на любой хостинг — или открываете index.html прямо с диска.',
  },
]

const included: { icon: IconName; title: string; body: string }[] = [
  {
    icon: 'code',
    title: 'Чистая вёрстка',
    body: 'Один HTML-файл и один CSS. Ни сборки, ни зависимостей, ни JavaScript, если он не нужен самому шаблону.',
  },
  {
    icon: 'cloud',
    title: 'Свои шрифты в архиве',
    body: 'Веб-шрифты лежат внутри ZIP. Сайт открывается одинаково и на хостинге, и без интернета.',
  },
  {
    icon: 'layout',
    title: 'Честная адаптивность',
    body: 'Предпросмотр — это настоящий сайт в рамке нужной ширины, поэтому мобильная версия в редакторе такая же, как у посетителя.',
  },
  {
    icon: 'eyeOff',
    title: 'Без водяных знаков',
    body: 'В экспорте нет ни ссылки на нас, ни бейджа конструктора, ни счётчиков.',
  },
]

const faq: { q: string; a: string }[] = [
  {
    q: 'Что будет с сайтом, если я перестану пользоваться сервисом?',
    a: 'Ничего. Скачанный архив — обычные файлы на вашем хостинге. Они не обращаются к нашим серверам и продолжат работать, даже если сервис исчезнет.',
  },
  {
    q: 'Нужно ли платить за домен и хостинг?',
    a: 'Домен — по желанию. Статический сайт бесплатно размещают Netlify, GitHub Pages, Cloudflare Pages и другие; архив рассчитан именно на них.',
  },
  {
    q: 'Можно поменять шаблон после публикации?',
    a: 'Да, в один клик. Шаблон — это оформление, а содержимое хранится отдельно. Секция, которую новый шаблон не показывает, не удаляется: вернёте шаблон — вернётся и она.',
  },
  {
    q: 'Смогу ли я править HTML вручную?',
    a: 'Да, после скачивания это ваши файлы. Учтите только, что правки не вернутся в редактор: следующий экспорт соберётся заново из ваших данных.',
  },
  {
    q: 'Сайт найдут в поиске?',
    a: 'Страница отдаётся готовой разметкой с заголовком, описанием и Open Graph — без JavaScript-рендеринга, который поисковики читают хуже.',
  },
]
</script>

<template>
  <div class="flex min-h-full flex-col bg-canvas">
    <header class="sticky top-0 z-30 border-b border-line bg-canvas/85 backdrop-blur-md">
      <div class="mx-auto flex h-14 max-w-6xl items-center gap-3 px-4 sm:px-6">
        <RouterLink :to="{ name: 'home' }" class="flex items-center gap-2">
          <span class="grid size-7 place-items-center rounded-[8px] bg-brand text-white">
            <svg viewBox="0 0 32 32" class="size-4" aria-hidden="true">
              <path
                d="M10 23V9h6.2a4.6 4.6 0 0 1 0 9.2H13"
                stroke="currentColor"
                stroke-width="3"
                fill="none"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </span>
          <span class="text-[14.5px] font-semibold tracking-[-0.015em]">Portfoedit</span>
        </RouterLink>

        <nav class="ml-4 hidden items-center gap-1 sm:flex">
          <a
            v-for="link in [
              { href: '#templates', label: 'Шаблоны' },
              { href: '#how', label: 'Как это работает' },
              { href: '#faq', label: 'Вопросы' },
            ]"
            :key="link.href"
            :href="link.href"
            class="rounded-[9px] px-2.5 py-1.5 text-[13px] text-ink-soft transition hover:bg-line-soft hover:text-ink"
          >
            {{ link.label }}
          </a>
        </nav>

        <div class="flex-1" />

        <RouterLink
          :to="{ name: 'login' }"
          class="rounded-[9px] px-2.5 py-1.5 text-[13px] text-ink-soft transition hover:bg-line-soft hover:text-ink"
        >
          Войти
        </RouterLink>
        <RouterLink :to="{ name: 'register' }">
          <BaseButton variant="primary">Начать бесплатно</BaseButton>
        </RouterLink>
      </div>
    </header>

    <main class="flex-1">
      <!-- Hero -->
      <section class="relative overflow-hidden">
        <div
          class="pointer-events-none absolute -top-40 left-1/2 size-[620px] -translate-x-1/2 rounded-full bg-brand/10 blur-[130px]"
          aria-hidden="true"
        />

        <div class="relative mx-auto max-w-6xl px-4 pt-16 pb-10 sm:px-6 sm:pt-24">
          <div class="mx-auto max-w-3xl text-center">
            <span
              class="inline-flex items-center gap-1.5 rounded-full border border-line bg-surface px-3 py-1 text-[12.5px] text-ink-soft shadow-soft"
            >
              <AppIcon name="sparkle" :size="13" class="text-brand" />
              Готовый сайт за вечер, без вёрстки
            </span>

            <h1
              class="mt-5 text-[38px] leading-[1.06] font-semibold tracking-[-0.03em] text-balance sm:text-[56px]"
            >
              Сайт-портфолио, который принадлежит вам
            </h1>

            <p
              class="mx-auto mt-5 max-w-xl text-[15.5px] leading-relaxed text-ink-soft text-pretty"
            >
              Заполните работы, выберите шаблон и скачайте чистые HTML и CSS. Разместите их где
              угодно: без привязки к платформе, без водяных знаков и без ежемесячной платы за то,
              чтобы сайт оставался в сети.
            </p>

            <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
              <RouterLink :to="{ name: 'register' }">
                <BaseButton variant="primary" size="lg" trailing-icon="arrowRight">
                  Создать портфолио
                </BaseButton>
              </RouterLink>
              <a href="#templates">
                <BaseButton size="lg" icon="eye">Посмотреть шаблоны</BaseButton>
              </a>
            </div>

            <p class="mt-3 text-[12.5px] text-ink-muted">Регистрация по почте. Карта не нужна.</p>
          </div>

          <!-- The hero image is the product's own output, rendered live. -->
          <div class="mt-12 sm:mt-16">
            <div
              class="mx-auto max-w-5xl overflow-hidden rounded-[18px] border border-line bg-surface shadow-lift"
            >
              <div class="flex items-center gap-1.5 border-b border-line-soft px-3.5 py-2.5">
                <span class="size-2.5 rounded-full bg-line" />
                <span class="size-2.5 rounded-full bg-line" />
                <span class="size-2.5 rounded-full bg-line" />
                <span class="ml-2 truncate text-[11.5px] text-ink-faint">
                  {{ current?.name ? `${current.name} — пример готового сайта` : 'Пример сайта' }}
                </span>
              </div>
              <SitePreviewFrame
                v-if="demoUrl"
                :key="demoUrl"
                :src="demoUrl"
                :ratio="0.62"
                :lazy="false"
              />
              <SkeletonBlock v-else height="420px" rounded="0" />
            </div>
          </div>
        </div>
      </section>

      <!-- Templates -->
      <section id="templates" class="scroll-mt-20 border-t border-line bg-surface">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
          <div class="max-w-2xl">
            <p class="panel-eyebrow">Шаблоны</p>
            <h2
              class="mt-2 text-[30px] leading-[1.12] font-semibold tracking-[-0.025em] sm:text-[36px]"
            >
              Разные дизайны, а не одна тема в трёх цветах
            </h2>
            <p class="mt-3 text-[14.5px] leading-relaxed text-ink-soft">
              Ниже — настоящие страницы, а не скриншоты. Переключайте и смотрите: содержимое одно и
              то же, меняется только оформление. Ровно это и происходит, когда вы меняете шаблон в
              редакторе.
            </p>
          </div>

          <div class="mt-8 flex flex-wrap gap-2">
            <button
              v-for="template in catalog.templates"
              :key="template.key"
              type="button"
              class="rounded-[10px] border px-3 py-1.5 text-[13px] transition"
              :class="
                activeTemplate === template.key
                  ? 'border-brand bg-brand-soft font-medium text-brand'
                  : 'border-line bg-surface text-ink-soft hover:border-[#d9d9de] hover:text-ink'
              "
              :aria-pressed="activeTemplate === template.key"
              @click="activeTemplate = template.key"
            >
              {{ template.name }}
            </button>

            <template v-if="!catalog.loaded">
              <SkeletonBlock v-for="n in 3" :key="n" height="34px" width="120px" rounded="10px" />
            </template>
          </div>

          <div class="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
            <div class="overflow-hidden rounded-[16px] border border-line shadow-soft">
              <SitePreviewFrame v-if="demoUrl" :key="demoUrl" :src="demoUrl" :ratio="0.7" />
              <SkeletonBlock v-else height="440px" rounded="0" />
            </div>

            <div class="flex flex-col justify-center">
              <h3 class="text-[19px] font-semibold tracking-[-0.02em]">
                {{ current?.name ?? '—' }}
              </h3>
              <p class="mt-2 text-[13.5px] leading-relaxed text-ink-soft">
                {{ current?.description ?? '' }}
              </p>

              <ul v-if="current?.tags?.length" class="mt-4 flex flex-wrap gap-1.5">
                <li
                  v-for="tag in current.tags"
                  :key="tag"
                  class="rounded-full border border-line bg-raised px-2 py-0.5 text-[11.5px] text-ink-muted"
                >
                  {{ tag }}
                </li>
              </ul>

              <RouterLink :to="{ name: 'register' }" class="mt-6">
                <BaseButton variant="primary" block trailing-icon="arrowRight">
                  Начать с этого шаблона
                </BaseButton>
              </RouterLink>
              <p class="mt-2 text-center text-[12px] text-ink-muted">
                Шаблон можно поменять в любой момент.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- How it works -->
      <section id="how" class="scroll-mt-20 border-t border-line">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
          <div class="max-w-2xl">
            <p class="panel-eyebrow">Как это работает</p>
            <h2
              class="mt-2 text-[30px] leading-[1.12] font-semibold tracking-[-0.025em] sm:text-[36px]"
            >
              Три шага и готовый сайт
            </h2>
          </div>

          <ol class="mt-8 grid gap-4 md:grid-cols-3">
            <li v-for="(step, index) in steps" :key="step.title" class="panel p-5">
              <div class="flex items-center gap-2.5">
                <span
                  class="grid size-9 place-items-center rounded-[10px] bg-brand-soft text-brand"
                >
                  <AppIcon :name="step.icon" :size="18" />
                </span>
                <span class="text-[12px] font-semibold text-ink-faint">0{{ index + 1 }}</span>
              </div>
              <h3 class="mt-3.5 text-[15.5px] font-semibold tracking-[-0.015em]">
                {{ step.title }}
              </h3>
              <p class="mt-1.5 text-[13.5px] leading-relaxed text-ink-soft">{{ step.body }}</p>
            </li>
          </ol>
        </div>
      </section>

      <!-- What the export contains -->
      <section class="border-t border-line bg-surface">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
          <div class="grid gap-10 lg:grid-cols-[minmax(0,420px)_minmax(0,1fr)]">
            <div>
              <p class="panel-eyebrow">Что вы получаете</p>
              <h2
                class="mt-2 text-[30px] leading-[1.12] font-semibold tracking-[-0.025em] sm:text-[36px]"
              >
                Папка с файлами, а не аккаунт
              </h2>
              <p class="mt-3 text-[14.5px] leading-relaxed text-ink-soft">
                Экспорт — самодостаточный статический сайт. Он не обращается к нашим серверам, не
                тянет шрифты со сторонних CDN и открывается даже из локальной папки.
              </p>

              <pre
                class="mt-6 overflow-x-auto rounded-[12px] border border-line bg-raised p-4 text-[12.5px] leading-relaxed text-ink-soft"
              ><code>portfolio.zip
├── index.html
├── README.txt
└── assets/
    ├── css/styles.css
    ├── fonts/*.woff2
    └── images/*.webp</code></pre>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div v-for="item in included" :key="item.title" class="panel p-5">
                <span
                  class="grid size-9 place-items-center rounded-[10px] bg-line-soft text-ink-soft"
                >
                  <AppIcon :name="item.icon" :size="18" />
                </span>
                <h3 class="mt-3.5 text-[14.5px] font-semibold tracking-[-0.015em]">
                  {{ item.title }}
                </h3>
                <p class="mt-1.5 text-[13px] leading-relaxed text-ink-soft">{{ item.body }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- FAQ -->
      <section id="faq" class="scroll-mt-20 border-t border-line">
        <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6 sm:py-20">
          <p class="panel-eyebrow">Вопросы</p>
          <h2
            class="mt-2 text-[30px] leading-[1.12] font-semibold tracking-[-0.025em] sm:text-[36px]"
          >
            Что обычно спрашивают
          </h2>

          <div class="mt-8 divide-y divide-line-soft border-y border-line-soft">
            <details v-for="item in faq" :key="item.q" class="group py-4">
              <summary
                class="flex cursor-pointer list-none items-start gap-3 text-[14.5px] font-medium"
              >
                <span class="flex-1">{{ item.q }}</span>
                <AppIcon
                  name="chevronDown"
                  :size="16"
                  class="mt-0.5 shrink-0 text-ink-muted transition-transform group-open:rotate-180"
                />
              </summary>
              <p class="mt-2.5 pr-7 text-[13.5px] leading-relaxed text-ink-soft">{{ item.a }}</p>
            </details>
          </div>
        </div>
      </section>

      <!-- Closing call to action -->
      <section class="border-t border-line">
        <div class="mx-auto max-w-6xl px-4 pt-4 pb-20 sm:px-6">
          <div
            class="relative overflow-hidden rounded-[20px] bg-ink px-6 py-14 text-center sm:px-12"
          >
            <div
              class="pointer-events-none absolute -top-24 -left-16 size-[420px] rounded-full bg-brand/35 blur-[110px]"
              aria-hidden="true"
            />
            <div
              class="pointer-events-none absolute right-0 -bottom-32 size-[380px] rounded-full bg-[#2dd4bf]/20 blur-[120px]"
              aria-hidden="true"
            />

            <div class="relative">
              <h2
                class="text-[28px] leading-[1.1] font-semibold tracking-[-0.025em] text-white sm:text-[38px]"
              >
                Первое портфолио займёт около минуты
              </h2>
              <p class="mx-auto mt-3 max-w-lg text-[14.5px] leading-relaxed text-white/60">
                Секции уже заполнены примерами — вы правите текст и сразу видите результат, а не
                собираете страницу с нуля.
              </p>
              <RouterLink :to="{ name: 'register' }" class="mt-7 inline-block">
                <BaseButton variant="primary" size="lg" trailing-icon="arrowRight">
                  Создать портфолио
                </BaseButton>
              </RouterLink>
            </div>
          </div>
        </div>
      </section>
    </main>

    <footer class="border-t border-line bg-surface">
      <div
        class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-7 text-[12.5px] text-ink-muted sm:px-6"
      >
        <span class="font-medium text-ink-soft">Portfoedit</span>
        <a href="#templates" class="transition hover:text-ink">Шаблоны</a>
        <a href="#how" class="transition hover:text-ink">Как это работает</a>
        <a href="#faq" class="transition hover:text-ink">Вопросы</a>
        <div class="flex-1" />
        <RouterLink :to="{ name: 'login' }" class="transition hover:text-ink">Войти</RouterLink>
      </div>
    </footer>
  </div>
</template>
