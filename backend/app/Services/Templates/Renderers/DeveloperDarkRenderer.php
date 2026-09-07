<?php

declare(strict_types=1);

namespace App\Services\Templates\Renderers;

use App\Enums\SectionType;
use App\Services\Templates\AbstractTemplateRenderer;
use App\Services\Templates\RenderContext;
use App\Support\Html;

/**
 * Developer adds two structural ideas on top of the semantic defaults:
 * numbered section headings (01, 02, …) and an availability pill in the hero,
 * pulled from the contacts section so the user fills it in exactly once.
 */
final class DeveloperDarkRenderer extends AbstractTemplateRenderer
{
    private int $sectionIndex = 0;

    public function bodyClass(): string
    {
        return 'pf pf-t-developer-dark';
    }

    public function render(RenderContext $ctx): string
    {
        $this->sectionIndex = 0;

        return parent::render($ctx);
    }

    /** @param array<string, mixed> $data */
    protected function heading(array $data, string $fallback, string $class = 'pf-section__title'): string
    {
        $heading = trim((string) ($data['heading'] ?? ''));
        $heading = $heading !== '' ? $heading : $fallback;

        return '<h2'.Html::attrs([
            'class' => $class,
            'data-index' => sprintf('%02d', ++$this->sectionIndex),
        ]).'>'.Html::e($heading).'</h2>';
    }

    /** @param array<string, mixed> $data */
    protected function hero(array $data, RenderContext $ctx): string
    {
        $name = (string) ($data['name'] ?? '');
        $title = (string) ($data['title'] ?? '');
        $intro = (string) ($data['intro'] ?? '');

        if (trim($name.$title.$intro) === '') {
            return '';
        }

        $photo = $this->figure($ctx->image($data['photo_media_id'] ?? null), $name, 'pf-hero__photo');
        $layout = $photo !== '' ? ' has-photo' : '';
        $alignment = ($data['alignment'] ?? 'left') === 'center' ? 'is-center' : 'is-left';

        $body = '<div class="pf-hero__text">'
            .$this->availabilityPill($ctx)
            .($title !== '' ? Html::tag('p', ['class' => 'pf-hero__eyebrow'], Html::e($title)) : '')
            .Html::tag('h1', ['class' => 'pf-hero__name'], Html::e($name))
            .Html::paragraphs($intro, 'pf-hero__intro')
            .$this->ctaRow($data, $ctx)
            .$this->inlineSocial($data, $ctx)
            .'</div>';

        return '<div class="pf-shell pf-hero '.$alignment.$layout.'">'.$body.$photo.'</div>';
    }

    /** The "available for work" chip, sourced from the contacts section. */
    private function availabilityPill(RenderContext $ctx): string
    {
        foreach ($ctx->sections as $section) {
            if ($section['type'] !== SectionType::Contacts) {
                continue;
            }

            $availability = trim((string) ($section['data']['availability'] ?? ''));

            if ($availability !== '') {
                return Html::tag('p', ['class' => 'pf-hero__status'], Html::e($availability));
            }
        }

        return '';
    }
}
