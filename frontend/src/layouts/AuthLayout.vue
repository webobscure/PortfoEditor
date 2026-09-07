<script setup lang="ts">
defineProps<{ title: string; subtitle?: string }>()
</script>

<template>
  <div class="grid min-h-full lg:grid-cols-[1fr_minmax(0,520px)]">
    <!-- The marketing half is hidden on small screens: the form is the job. -->
    <aside
      class="relative hidden overflow-hidden bg-ink px-12 py-14 text-white/90 lg:flex lg:flex-col lg:justify-between"
    >
      <div
        class="pointer-events-none absolute -top-24 -left-16 size-[420px] rounded-full bg-brand/35 blur-[110px]"
      />
      <div
        class="pointer-events-none absolute right-0 -bottom-32 size-[380px] rounded-full bg-[#2dd4bf]/20 blur-[120px]"
      />

      <div class="relative flex items-center gap-2">
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
        <span class="text-[14.5px] font-semibold tracking-[-0.015em] text-white">Portfoedit</span>
      </div>

      <div class="relative max-w-md">
        <h1 class="text-[38px] leading-[1.08] font-semibold tracking-[-0.03em] text-white">
          Сайт-портфолио, который принадлежит вам. За один вечер.
        </h1>
        <p class="mt-4 text-[15px] leading-relaxed text-white/60">
          Заполните работы, выберите шаблон и скачайте чистые HTML и CSS — разместите их где угодно.
          Без привязки к платформе, без водяных знаков конструктора и без ежемесячной платы за то,
          чтобы сайт оставался в сети.
        </p>
      </div>

      <ul class="relative flex flex-wrap gap-x-6 gap-y-2 text-[13px] text-white/50">
        <li>Три готовых шаблона</li>
        <li>Живой предпросмотр</li>
        <li>Статический экспорт</li>
      </ul>
    </aside>

    <div class="flex items-center justify-center px-5 py-12 sm:px-10">
      <div class="w-full max-w-[380px]">
        <h2 class="text-[24px] font-semibold tracking-[-0.02em]">{{ title }}</h2>
        <p v-if="subtitle" class="mt-1.5 text-[13.5px] text-ink-muted">{{ subtitle }}</p>

        <div class="mt-7">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>
