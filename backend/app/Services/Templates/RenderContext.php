<?php

declare(strict_types=1);

namespace App\Services\Templates;

use App\Enums\SectionType;

/**
 * Everything a renderer is allowed to know.
 *
 * Renderers never touch Eloquent, the filesystem or the request. They receive
 * this immutable bag, which is built identically for the live preview and for a
 * static export — the only differences are the asset URLs and the `preview`
 * flag that enables the editor's click-to-select instrumentation.
 */
final class RenderContext
{
    public const MODE_PREVIEW = 'preview';

    public const MODE_EXPORT = 'export';

    /**
     * @param  array<string, mixed>  $portfolio  name, slug, meta
     * @param  array<int, array{id:int|string,type:SectionType,data:array<string,mixed>,settings:array<string,mixed>}>  $sections
     * @param  array<string, mixed>  $settings  merged theme settings
     * @param  array<int, array{url:string,width:int|null,height:int|null,alt:string}>  $images  keyed by media id
     */
    public function __construct(
        public readonly string $mode,
        public readonly string $templateKey,
        public readonly array $portfolio,
        public readonly array $sections,
        public readonly array $settings,
        public readonly array $images,
        public readonly string $themeCss,
        public readonly string $fontCss,
        public readonly string $styleHref,
        public readonly ?string $scriptSrc = null,
        public readonly ?string $ogImageUrl = null,
        public readonly string $lang = 'en',
    ) {}

    public function isPreview(): bool
    {
        return $this->mode === self::MODE_PREVIEW;
    }

    /** @return array{url:string,width:int|null,height:int|null,alt:string}|null */
    public function image(int|string|null $mediaId): ?array
    {
        if ($mediaId === null || $mediaId === '') {
            return null;
        }

        return $this->images[(int) $mediaId] ?? null;
    }

    public function name(): string
    {
        foreach ($this->sections as $section) {
            if ($section['type'] === SectionType::Hero) {
                $name = trim((string) ($section['data']['name'] ?? ''));

                if ($name !== '') {
                    return $name;
                }
            }
        }

        return (string) ($this->portfolio['name'] ?? 'Portfolio');
    }

    public function documentTitle(): string
    {
        $meta = $this->portfolio['meta'] ?? [];
        $title = trim((string) ($meta['title'] ?? ''));

        if ($title !== '') {
            return $title;
        }

        $role = '';

        foreach ($this->sections as $section) {
            if ($section['type'] === SectionType::Hero) {
                $role = trim((string) ($section['data']['title'] ?? ''));
                break;
            }
        }

        return $role !== '' ? $this->name().' — '.$role : $this->name();
    }

    public function documentDescription(): string
    {
        $meta = $this->portfolio['meta'] ?? [];
        $description = trim((string) ($meta['description'] ?? ''));

        if ($description !== '') {
            return $description;
        }

        foreach ($this->sections as $section) {
            if ($section['type'] === SectionType::Hero) {
                $intro = trim((string) ($section['data']['intro'] ?? ''));

                if ($intro !== '') {
                    return $intro;
                }
            }
        }

        return '';
    }
}
