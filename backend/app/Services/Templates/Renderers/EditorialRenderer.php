<?php

declare(strict_types=1);

namespace App\Services\Templates\Renderers;

use App\Services\Templates\AbstractTemplateRenderer;
use App\Services\Templates\RenderContext;
use App\Support\Html;

/**
 * Editorial presents work as case-study spreads: a running number beside the
 * title, the image full width, and the metadata pushed into a ruled aside the
 * way a print feature carries its credits.
 */
final class EditorialRenderer extends AbstractTemplateRenderer
{
    public function bodyClass(): string
    {
        return 'pf pf-t-editorial';
    }

    /** @param array<string, mixed> $data */
    protected function projects(array $data, RenderContext $ctx): string
    {
        $cards = '';
        $index = 0;

        foreach ($this->items($data) as $item) {
            $card = $this->caseStudy($item, $index + 1, $ctx);

            if ($card === '') {
                continue;
            }

            $index++;
            $cards .= $card;
        }

        if ($cards === '') {
            return '';
        }

        return '<div class="pf-shell">'
            .$this->heading($data, 'Selected work')
            .Html::paragraphs((string) ($data['intro'] ?? ''), 'pf-section__intro')
            .'<div class="pf-projects">'.$cards.'</div>'
            .'</div>';
    }

    /** @param array<string, mixed> $item */
    private function caseStudy(array $item, int $index, RenderContext $ctx): string
    {
        $title = (string) ($item['title'] ?? '');

        if (trim($title) === '') {
            return '';
        }

        $header = '<div class="pf-project__header">'
            .Html::tag('p', ['class' => 'pf-project__index'], sprintf('%02d', $index))
            .Html::tag('h3', ['class' => 'pf-project__title'], Html::e($title))
            .'</div>';

        $image = $this->figure($ctx->image($item['image_media_id'] ?? null), $title, 'pf-project__media');

        $aside = '';
        $year = trim((string) ($item['year'] ?? ''));

        if ($year !== '') {
            $aside .= '<div>'
                .Html::tag('p', ['class' => 'pf-project__label'], 'Year')
                .Html::tag('p', ['class' => 'pf-project__year'], Html::e($year))
                .'</div>';
        }

        $tech = '';

        foreach ((array) ($item['technologies'] ?? []) as $technology) {
            $tech .= Html::tag('li', ['class' => 'pf-chip'], Html::e((string) $technology));
        }

        if ($tech !== '') {
            $aside .= '<div>'
                .Html::tag('p', ['class' => 'pf-project__label'], 'Discipline')
                .'<ul class="pf-chips">'.$tech.'</ul>'
                .'</div>';
        }

        $links = '';

        foreach ([['url', 'View the work'], ['github_url', 'Source']] as [$key, $label]) {
            $href = Html::url($item[$key] ?? null);

            if ($href === '') {
                continue;
            }

            $links .= Html::tag('a', [
                'class' => 'pf-project__link',
                'href' => $href,
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
            ], Html::e($label));
        }

        if ($links !== '') {
            $aside .= '<div class="pf-project__links">'.$links.'</div>';
        }

        return '<article class="pf-project">'
            .$header
            .$image
            .'<div class="pf-project__body">'
            .Html::paragraphs((string) ($item['description'] ?? ''), 'pf-project__text')
            .($aside !== '' ? '<div class="pf-project__aside">'.$aside.'</div>' : '')
            .'</div>'
            .'</article>';
    }
}
