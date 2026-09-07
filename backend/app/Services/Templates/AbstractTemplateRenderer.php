<?php

declare(strict_types=1);

namespace App\Services\Templates;

use App\Enums\SectionType;
use App\Support\Html;

/**
 * Base renderer: the document shell plus a semantic default for every section
 * type. Concrete templates override the sections whose *structure* differs and
 * let their stylesheet do the rest.
 *
 * The output is deliberately deterministic — same input, same bytes — because
 * the golden-file tests compare it against the TypeScript renderer that drives
 * the live preview.
 */
abstract class AbstractTemplateRenderer
{
    /** Body class that scopes the template's stylesheet. */
    abstract public function bodyClass(): string;

    public function render(RenderContext $ctx): string
    {
        return implode("\n", [
            '<!DOCTYPE html>',
            '<html lang="'.Html::e($ctx->lang).'">',
            '<head>',
            $this->head($ctx),
            '</head>',
            '<body class="'.Html::e($this->bodyClass()).'">',
            $this->body($ctx),
            '</body>',
            '</html>',
            '',
        ]);
    }

    protected function head(RenderContext $ctx): string
    {
        $title = $ctx->documentTitle();
        $description = Html::summarise($ctx->documentDescription());

        $lines = [
            '<meta charset="utf-8">',
            '<meta name="viewport" content="width=device-width, initial-scale=1">',
            '<title>'.Html::e($title).'</title>',
        ];

        if ($description !== '') {
            $lines[] = Html::void('meta', ['name' => 'description', 'content' => $description]);
        }

        $lines[] = Html::void('meta', ['property' => 'og:type', 'content' => 'website']);
        $lines[] = Html::void('meta', ['property' => 'og:title', 'content' => $title]);

        if ($description !== '') {
            $lines[] = Html::void('meta', ['property' => 'og:description', 'content' => $description]);
        }

        if ($ctx->ogImageUrl) {
            $lines[] = Html::void('meta', ['property' => 'og:image', 'content' => $ctx->ogImageUrl]);
            $lines[] = Html::void('meta', ['name' => 'twitter:card', 'content' => 'summary_large_image']);
        } else {
            $lines[] = Html::void('meta', ['name' => 'twitter:card', 'content' => 'summary']);
        }

        $lines[] = '<style>'.$ctx->fontCss.'</style>';
        $lines[] = '<style>'.$ctx->themeCss.'</style>';
        $lines[] = Html::void('link', ['rel' => 'stylesheet', 'href' => $ctx->styleHref]);

        return implode("\n", $lines);
    }

    protected function body(RenderContext $ctx): string
    {
        $parts = [
            $this->header($ctx),
            '<main class="pf-main" id="content">',
            $this->sections($ctx),
            '</main>',
            $this->footer($ctx),
        ];

        if ($ctx->scriptSrc) {
            $parts[] = Html::tag('script', ['src' => $ctx->scriptSrc, 'defer' => true]);
        }

        return implode("\n", array_values(array_filter($parts, static fn (string $p) => $p !== '')));
    }

    /** Sticky in-page navigation built from the enabled sections. */
    protected function header(RenderContext $ctx): string
    {
        $links = [];

        foreach ($ctx->sections as $section) {
            /** @var SectionType $type */
            $type = $section['type'];

            if (in_array($type, [SectionType::Hero, SectionType::SocialLinks], true)) {
                continue;
            }

            $label = $this->navLabel($type, $section['data']);

            if ($label === '') {
                continue;
            }

            $links[] = Html::tag('a', ['class' => 'pf-nav__link', 'href' => '#'.$this->anchor($type)], Html::e($label));
        }

        if ($links === []) {
            return '';
        }

        $brand = Html::tag('a', ['class' => 'pf-nav__brand', 'href' => '#top'], Html::e($ctx->name()));

        return '<header class="pf-nav" id="top">'
            .'<div class="pf-nav__inner">'
            .$brand
            .'<nav class="pf-nav__links" aria-label="Sections">'.implode('', $links).'</nav>'
            .'</div>'
            .'</header>';
    }

    protected function footer(RenderContext $ctx): string
    {
        $year = date('Y');

        return '<footer class="pf-footer">'
            .'<div class="pf-shell">'
            .Html::tag('p', ['class' => 'pf-footer__text'], '© '.$year.' '.Html::e($ctx->name()))
            .'</div>'
            .'</footer>';
    }

    protected function sections(RenderContext $ctx): string
    {
        $out = [];

        foreach ($ctx->sections as $section) {
            /** @var SectionType $type */
            $type = $section['type'];
            $inner = $this->renderSection($type, $section['data'], $ctx);

            if (trim($inner) === '') {
                continue;
            }

            $attributes = [
                'id' => $this->anchor($type),
                'class' => 'pf-section pf-section--'.$type->value,
                'data-pf-section' => (string) $section['id'],
                'data-pf-type' => $type->value,
            ];

            $out[] = '<section'.Html::attrs($attributes).'>'.$inner.'</section>';
        }

        return implode("\n", $out);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function renderSection(SectionType $type, array $data, RenderContext $ctx): string
    {
        return match ($type) {
            SectionType::Hero => $this->hero($data, $ctx),
            SectionType::About => $this->about($data, $ctx),
            SectionType::Experience => $this->experience($data, $ctx),
            SectionType::Education => $this->education($data, $ctx),
            SectionType::Skills => $this->skills($data, $ctx),
            SectionType::Projects => $this->projects($data, $ctx),
            SectionType::Services => $this->services($data, $ctx),
            SectionType::Achievements => $this->achievements($data, $ctx),
            SectionType::Contacts => $this->contacts($data, $ctx),
            SectionType::SocialLinks => $this->socialLinks($data, $ctx),
        };
    }

    // ------------------------------------------------------------- defaults

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
        $alignment = ($data['alignment'] ?? 'left') === 'center' ? 'is-center' : 'is-left';
        $layout = $photo !== '' ? ' has-photo' : '';

        $body = '<div class="pf-hero__text">'
            .($title !== '' ? Html::tag('p', ['class' => 'pf-hero__eyebrow'], Html::e($title)) : '')
            .Html::tag('h1', ['class' => 'pf-hero__name'], Html::e($name))
            .Html::paragraphs($intro, 'pf-hero__intro')
            .$this->ctaRow($data, $ctx)
            .$this->inlineSocial($data, $ctx)
            .'</div>';

        return '<div class="pf-shell pf-hero '.$alignment.$layout.'">'.$body.$photo.'</div>';
    }

    /** @param array<string, mixed> $data */
    protected function about(array $data, RenderContext $ctx): string
    {
        $body = Html::paragraphs((string) ($data['body'] ?? ''), 'pf-about__body');

        if ($body === '') {
            return '';
        }

        $highlights = '';

        foreach ($this->items($data, 'highlights') as $highlight) {
            $highlights .= '<div class="pf-highlight">'
                .Html::tag('dt', ['class' => 'pf-highlight__value'], Html::e((string) ($highlight['value'] ?? '')))
                .Html::tag('dd', ['class' => 'pf-highlight__label'], Html::e((string) ($highlight['label'] ?? '')))
                .'</div>';
        }

        $photo = $this->figure($ctx->image($data['photo_media_id'] ?? null), (string) ($data['heading'] ?? 'About'), 'pf-about__photo');

        return '<div class="pf-shell pf-about">'
            .$this->heading($data, 'About')
            .'<div class="pf-about__grid">'
            .'<div class="pf-about__content">'.$body
            .($highlights !== '' ? '<dl class="pf-highlights">'.$highlights.'</dl>' : '')
            .'</div>'
            .$photo
            .'</div>'
            .'</div>';
    }

    /** @param array<string, mixed> $data */
    protected function experience(array $data, RenderContext $ctx): string
    {
        $rows = '';

        foreach ($this->items($data) as $item) {
            $period = $this->period($item);
            $meta = array_values(array_filter([
                (string) ($item['company'] ?? ''),
                (string) ($item['location'] ?? ''),
            ], static fn (string $v) => trim($v) !== ''));

            $tags = '';

            foreach ((array) ($item['tags'] ?? []) as $tag) {
                $tags .= Html::tag('li', ['class' => 'pf-tag'], Html::e((string) $tag));
            }

            $rows .= '<li class="pf-entry">'
                .($period !== '' ? Html::tag('p', ['class' => 'pf-entry__period'], Html::e($period)) : '')
                .'<div class="pf-entry__body">'
                .Html::tag('h3', ['class' => 'pf-entry__title'], Html::e((string) ($item['role'] ?? '')))
                .($meta !== [] ? Html::tag('p', ['class' => 'pf-entry__meta'], Html::e(implode(' · ', $meta))) : '')
                .Html::paragraphs((string) ($item['description'] ?? ''), 'pf-entry__text')
                .($tags !== '' ? '<ul class="pf-tags">'.$tags.'</ul>' : '')
                .'</div>'
                .'</li>';
        }

        if ($rows === '') {
            return '';
        }

        return '<div class="pf-shell">'.$this->heading($data, 'Experience').'<ul class="pf-entries">'.$rows.'</ul></div>';
    }

    /** @param array<string, mixed> $data */
    protected function education(array $data, RenderContext $ctx): string
    {
        $rows = '';

        foreach ($this->items($data) as $item) {
            $period = $this->period($item);
            $meta = array_values(array_filter([
                (string) ($item['institution'] ?? ''),
                (string) ($item['location'] ?? ''),
            ], static fn (string $v) => trim($v) !== ''));

            $rows .= '<li class="pf-entry">'
                .($period !== '' ? Html::tag('p', ['class' => 'pf-entry__period'], Html::e($period)) : '')
                .'<div class="pf-entry__body">'
                .Html::tag('h3', ['class' => 'pf-entry__title'], Html::e((string) ($item['degree'] ?? '')))
                .($meta !== [] ? Html::tag('p', ['class' => 'pf-entry__meta'], Html::e(implode(' · ', $meta))) : '')
                .Html::paragraphs((string) ($item['description'] ?? ''), 'pf-entry__text')
                .'</div>'
                .'</li>';
        }

        if ($rows === '') {
            return '';
        }

        return '<div class="pf-shell">'.$this->heading($data, 'Education').'<ul class="pf-entries">'.$rows.'</ul></div>';
    }

    /** @param array<string, mixed> $data */
    protected function skills(array $data, RenderContext $ctx): string
    {
        $groups = '';

        foreach ($this->items($data, 'groups') as $group) {
            $chips = '';

            foreach ((array) ($group['items'] ?? []) as $skill) {
                $chips .= Html::tag('li', ['class' => 'pf-chip'], Html::e((string) $skill));
            }

            if ($chips === '') {
                continue;
            }

            $groups .= '<div class="pf-skillgroup">'
                .Html::tag('h3', ['class' => 'pf-skillgroup__name'], Html::e((string) ($group['name'] ?? '')))
                .'<ul class="pf-chips">'.$chips.'</ul>'
                .'</div>';
        }

        if ($groups === '') {
            return '';
        }

        return '<div class="pf-shell">'.$this->heading($data, 'Skills').'<div class="pf-skills">'.$groups.'</div></div>';
    }

    /** @param array<string, mixed> $data */
    protected function projects(array $data, RenderContext $ctx): string
    {
        $cards = '';

        foreach ($this->items($data) as $item) {
            $cards .= $this->projectCard($item, $ctx);
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
    protected function projectCard(array $item, RenderContext $ctx): string
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

        foreach ([['url', 'Visit'], ['github_url', 'Source']] as [$key, $label]) {
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
            .($year !== '' ? Html::tag('p', ['class' => 'pf-project__year'], Html::e($year)) : '')
            .Html::tag('h3', ['class' => 'pf-project__title'], Html::e($title))
            .Html::paragraphs((string) ($item['description'] ?? ''), 'pf-project__text')
            .($tech !== '' ? '<ul class="pf-chips">'.$tech.'</ul>' : '')
            .($links !== '' ? '<div class="pf-project__links">'.$links.'</div>' : '')
            .'</div>'
            .'</article>';
    }

    /** @param array<string, mixed> $data */
    protected function services(array $data, RenderContext $ctx): string
    {
        $cards = '';

        foreach ($this->items($data) as $item) {
            $title = (string) ($item['title'] ?? '');

            if (trim($title) === '') {
                continue;
            }

            $price = (string) ($item['price'] ?? '');

            $cards .= '<article class="pf-service">'
                .Html::tag('h3', ['class' => 'pf-service__title'], Html::e($title))
                .Html::paragraphs((string) ($item['description'] ?? ''), 'pf-service__text')
                .($price !== '' ? Html::tag('p', ['class' => 'pf-service__price'], Html::e($price)) : '')
                .'</article>';
        }

        if ($cards === '') {
            return '';
        }

        return '<div class="pf-shell">'.$this->heading($data, 'Services').'<div class="pf-services">'.$cards.'</div></div>';
    }

    /** @param array<string, mixed> $data */
    protected function achievements(array $data, RenderContext $ctx): string
    {
        $rows = '';

        foreach ($this->items($data) as $item) {
            $title = (string) ($item['title'] ?? '');

            if (trim($title) === '') {
                continue;
            }

            $href = Html::url($item['url'] ?? null);
            $heading = $href !== ''
                ? Html::tag('a', ['href' => $href, 'target' => '_blank', 'rel' => 'noopener noreferrer'], Html::e($title))
                : Html::e($title);

            $meta = array_values(array_filter([
                (string) ($item['issuer'] ?? ''),
                (string) ($item['date'] ?? ''),
            ], static fn (string $v) => trim($v) !== ''));

            $rows .= '<li class="pf-award">'
                .Html::tag('h3', ['class' => 'pf-award__title'], $heading)
                .($meta !== [] ? Html::tag('p', ['class' => 'pf-award__meta'], Html::e(implode(' · ', $meta))) : '')
                .Html::paragraphs((string) ($item['description'] ?? ''), 'pf-award__text')
                .'</li>';
        }

        if ($rows === '') {
            return '';
        }

        return '<div class="pf-shell">'.$this->heading($data, 'Achievements').'<ul class="pf-awards">'.$rows.'</ul></div>';
    }

    /** @param array<string, mixed> $data */
    protected function contacts(array $data, RenderContext $ctx): string
    {
        $email = (string) ($data['email'] ?? '');
        $mailto = Html::mailto($email);
        $rows = '';

        foreach ([
            ['Email', $email, $mailto],
            ['Phone', (string) ($data['phone'] ?? ''), Html::tel($data['phone'] ?? null)],
            ['Location', (string) ($data['location'] ?? ''), ''],
            ['Availability', (string) ($data['availability'] ?? ''), ''],
        ] as [$label, $value, $href]) {
            if (trim($value) === '') {
                continue;
            }

            $rendered = $href !== ''
                ? Html::tag('a', ['class' => 'pf-contact__link', 'href' => $href], Html::e($value))
                : Html::e($value);

            $rows .= '<div class="pf-contact">'
                .Html::tag('dt', ['class' => 'pf-contact__label'], Html::e($label))
                .Html::tag('dd', ['class' => 'pf-contact__value'], $rendered)
                .'</div>';
        }

        $intro = Html::paragraphs((string) ($data['intro'] ?? ''), 'pf-section__intro');

        if ($rows === '' && $intro === '') {
            return '';
        }

        $cta = '';
        $ctaText = (string) ($data['cta_text'] ?? '');

        if ($mailto !== '' && trim($ctaText) !== '') {
            $cta = Html::tag('a', ['class' => 'pf-btn pf-btn--primary', 'href' => $mailto], Html::e($ctaText));
        }

        return '<div class="pf-shell pf-contacts">'
            .$this->heading($data, 'Get in touch')
            .$intro
            .($rows !== '' ? '<dl class="pf-contacts__grid">'.$rows.'</dl>' : '')
            .$cta
            .'</div>';
    }

    /** @param array<string, mixed> $data */
    protected function socialLinks(array $data, RenderContext $ctx): string
    {
        $links = $this->socialList($data, 'pf-social__link');

        if ($links === '') {
            return '';
        }

        return '<div class="pf-shell"><nav class="pf-social" aria-label="Social links">'.$links.'</nav></div>';
    }

    // -------------------------------------------------------------- helpers

    /** @param array<string, mixed> $data */
    protected function heading(array $data, string $fallback, string $class = 'pf-section__title'): string
    {
        $heading = trim((string) ($data['heading'] ?? ''));
        $heading = $heading !== '' ? $heading : $fallback;

        return Html::tag('h2', ['class' => $class], Html::e($heading));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    protected function items(array $data, string $key = 'items'): array
    {
        $items = $data[$key] ?? [];

        return is_array($items)
            ? array_values(array_filter($items, 'is_array'))
            : [];
    }

    /** @param array<string, mixed> $item */
    protected function period(array $item): string
    {
        $start = trim((string) ($item['start'] ?? ''));
        $end = ($item['current'] ?? false) ? 'Present' : trim((string) ($item['end'] ?? ''));

        return trim(implode(' — ', array_values(array_filter([$start, $end], static fn (string $v) => $v !== ''))));
    }

    /** @param array{url:string,width:int|null,height:int|null,alt:string}|null $image */
    protected function figure(?array $image, string $alt, string $class): string
    {
        if ($image === null) {
            return '';
        }

        $img = Html::void('img', [
            'src' => $image['url'],
            'alt' => $image['alt'] !== '' ? $image['alt'] : $alt,
            'width' => $image['width'] !== null ? (string) $image['width'] : null,
            'height' => $image['height'] !== null ? (string) $image['height'] : null,
            'loading' => 'lazy',
            'decoding' => 'async',
        ]);

        return '<figure class="'.Html::e($class).'">'.$img.'</figure>';
    }

    /** @param array<string, mixed> $data */
    protected function ctaRow(array $data, RenderContext $ctx): string
    {
        $buttons = '';

        foreach ([
            ['cta_text', 'cta_url', 'pf-btn pf-btn--primary'],
            ['secondary_cta_text', 'secondary_cta_url', 'pf-btn pf-btn--ghost'],
        ] as [$textKey, $urlKey, $class]) {
            $text = trim((string) ($data[$textKey] ?? ''));
            $href = Html::url($data[$urlKey] ?? null);

            if ($text === '' || $href === '') {
                continue;
            }

            $buttons .= Html::tag('a', [
                'class' => $class,
                'href' => $href,
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
            ], Html::e($text));
        }

        return $buttons === '' ? '' : '<div class="pf-hero__actions">'.$buttons.'</div>';
    }

    /**
     * Social links repeated inside the hero when the hero asks for them.
     *
     * @param  array<string, mixed>  $data
     */
    protected function inlineSocial(array $data, RenderContext $ctx): string
    {
        if (! ($data['show_social'] ?? false)) {
            return '';
        }

        foreach ($ctx->sections as $section) {
            if ($section['type'] === SectionType::SocialLinks) {
                $links = $this->socialList($section['data'], 'pf-hero__social-link');

                return $links === ''
                    ? ''
                    : '<nav class="pf-hero__social" aria-label="Social links">'.$links.'</nav>';
            }
        }

        return '';
    }

    /** @param array<string, mixed> $data */
    protected function socialList(array $data, string $class): string
    {
        $links = '';

        foreach ($this->items($data) as $item) {
            $href = Html::url($item['url'] ?? null);

            if ($href === '') {
                continue;
            }

            $platform = (string) ($item['platform'] ?? 'website');
            $label = trim((string) ($item['label'] ?? ''));
            $label = $label !== '' ? $label : ucfirst($platform);

            $links .= Html::tag('a', [
                'class' => $class,
                'href' => $href,
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
                'data-platform' => $platform,
            ], Html::e($label));
        }

        return $links;
    }

    protected function anchor(SectionType $type): string
    {
        return 'section-'.str_replace('_', '-', $type->value);
    }

    /** @param array<string, mixed> $data */
    protected function navLabel(SectionType $type, array $data): string
    {
        $heading = trim((string) ($data['heading'] ?? ''));

        return $heading !== '' ? $heading : $type->label();
    }
}
