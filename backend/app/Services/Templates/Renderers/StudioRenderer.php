<?php

declare(strict_types=1);

namespace App\Services\Templates\Renderers;

use App\Services\Templates\AbstractTemplateRenderer;
use App\Services\Templates\RenderContext;
use App\Support\Html;

/**
 * Studio — poster typography and work laid out as a stagger, not a grid.
 *
 * The one structural idea is the case row: every project is a full-width band
 * carrying an oversized index numeral, and consecutive rows mirror each other
 * so the page reads as an alternating rhythm rather than a column of cards.
 * The mirroring is a class on the article, so the layout stays CSS's problem
 * and the markup stays deterministic — the golden-file parity test compares
 * this output byte for byte against the TypeScript renderer.
 *
 * Everything else deliberately reuses the shared markup: a template earns its
 * character from type and space, and each divergence in markup is one more
 * place the preview and the export can drift apart.
 */
final class StudioRenderer extends AbstractTemplateRenderer
{
    public function bodyClass(): string
    {
        return 'pf pf-t-studio';
    }

    /** @param array<string, mixed> $data */
    protected function projects(array $data, RenderContext $ctx): string
    {
        $rows = '';
        $index = 0;

        foreach ($this->items($data) as $item) {
            $row = $this->caseRow($item, $index + 1, $ctx);

            if ($row === '') {
                continue;
            }

            $index++;
            $rows .= $row;
        }

        if ($rows === '') {
            return '';
        }

        return '<div class="pf-shell">'
            .$this->heading($data, 'Избранные работы')
            .Html::paragraphs((string) ($data['intro'] ?? ''), 'pf-section__intro')
            .'<div class="pf-projects">'.$rows.'</div>'
            .'</div>';
    }

    /** @param array<string, mixed> $item */
    private function caseRow(array $item, int $index, RenderContext $ctx): string
    {
        $title = (string) ($item['title'] ?? '');

        if (trim($title) === '') {
            return '';
        }

        $image = $this->figure($ctx->image($item['image_media_id'] ?? null), $title, 'pf-project__media');

        $tech = '';

        foreach ((array) ($item['technologies'] ?? []) as $technology) {
            $tech .= Html::tag('li', ['class' => 'pf-chip'], Html::e((string) $technology));
        }

        $links = '';

        foreach ([['url', 'Смотреть работу'], ['github_url', 'Исходный код']] as [$key, $label]) {
            $href = Html::url($item[$key] ?? null);

            if ($href === '') {
                continue;
            }

            $links .= Html::tag('a', [
                'class' => 'pf-project__link',
                'href' => $href,
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
            ], Html::e($label).'<span aria-hidden="true"> ↗</span>');
        }

        $year = (string) ($item['year'] ?? '');

        // Even rows mirror the odd ones. Counting from the rendered rows rather
        // than the source array keeps the rhythm intact when an untitled item
        // is skipped.
        $classes = 'pf-project pf-project--case'.($index % 2 === 0 ? ' pf-project--mirrored' : '');

        return '<article class="'.$classes.'">'
            .'<div class="pf-project__rail">'
            .Html::tag('p', ['class' => 'pf-project__index'], sprintf('%02d', $index))
            .($year !== '' ? Html::tag('p', ['class' => 'pf-project__year'], Html::e($year)) : '')
            .($tech !== '' ? '<ul class="pf-chips">'.$tech.'</ul>' : '')
            .'</div>'
            .$image
            .'<div class="pf-project__body">'
            .Html::tag('h3', ['class' => 'pf-project__title'], Html::e($title))
            .Html::paragraphs((string) ($item['description'] ?? ''), 'pf-project__text')
            .($links !== '' ? '<div class="pf-project__links">'.$links.'</div>' : '')
            .'</div>'
            .'</article>';
    }
}
