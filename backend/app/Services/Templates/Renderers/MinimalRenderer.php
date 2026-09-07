<?php

declare(strict_types=1);

namespace App\Services\Templates\Renderers;

use App\Services\Templates\AbstractTemplateRenderer;
use App\Services\Templates\RenderContext;
use App\Support\Html;

/**
 * Minimal keeps the semantic defaults and adds one structural idea of its own:
 * work is presented as a numbered sequence of large, near-full-bleed cases
 * rather than a grid of tiles.
 */
final class MinimalRenderer extends AbstractTemplateRenderer
{
    public function bodyClass(): string
    {
        return 'pf pf-t-minimal';
    }

    /** @param array<string, mixed> $data */
    protected function projects(array $data, RenderContext $ctx): string
    {
        $cards = '';
        $index = 0;

        foreach ($this->items($data) as $item) {
            $card = $this->numberedProject($item, ++$index, $ctx);

            if ($card === '') {
                $index--;

                continue;
            }

            $cards .= $card;
        }

        if ($cards === '') {
            return '';
        }

        return '<div class="pf-shell">'
            .$this->heading($data, 'Избранные работы')
            .Html::paragraphs((string) ($data['intro'] ?? ''), 'pf-section__intro')
            .'<div class="pf-projects">'.$cards.'</div>'
            .'</div>';
    }

    /** @param array<string, mixed> $item */
    private function numberedProject(array $item, int $index, RenderContext $ctx): string
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

        foreach ([['url', 'Открыть проект'], ['github_url', 'Исходный код']] as [$key, $label]) {
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

        return '<article class="pf-project">'
            .$image
            .'<div class="pf-project__body">'
            .'<div>'
            .Html::tag('p', ['class' => 'pf-project__index'], sprintf('%02d', $index))
            .Html::tag('h3', ['class' => 'pf-project__title'], Html::e($title))
            .($year !== '' ? Html::tag('p', ['class' => 'pf-project__year'], Html::e($year)) : '')
            .'</div>'
            .'<div>'
            .Html::paragraphs((string) ($item['description'] ?? ''), 'pf-project__text')
            .($tech !== '' ? '<ul class="pf-chips">'.$tech.'</ul>' : '')
            .($links !== '' ? '<div class="pf-project__links">'.$links.'</div>' : '')
            .'</div>'
            .'</div>'
            .'</article>';
    }
}
