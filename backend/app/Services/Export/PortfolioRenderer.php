<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Enums\SectionType;
use App\Models\Media;
use App\Models\Portfolio;
use App\Services\Templates\FontCatalog;
use App\Services\Templates\RenderContext;
use App\Services\Templates\Template;
use App\Services\Templates\TemplateRegistry;
use App\Services\Templates\ThemeCompiler;
use App\Services\Templates\ThemeSchema;
use App\Support\MediaReferences;
use Closure;

/**
 * Builds the render context for a portfolio and hands it to the template.
 *
 * This is the only place that turns Eloquent models into renderer input, and it
 * is shared by every consumer — the preview endpoint, the export pipeline and
 * the test suite — so all three necessarily agree on what the page contains.
 */
final class PortfolioRenderer
{
    public function __construct(
        private readonly TemplateRegistry $templates,
        private readonly ThemeSchema $schema,
        private readonly ThemeCompiler $compiler,
        private readonly FontCatalog $fonts,
    ) {}

    /**
     * @param  Closure(Media): string  $imageUrl  how a stored file is addressed in this mode
     */
    public function context(
        Portfolio $portfolio,
        string $mode,
        Closure $imageUrl,
        string $styleHref,
        string $fontBaseUrl,
        ?string $scriptSrc = null,
        ?string $templateKey = null,
    ): RenderContext {
        $template = $this->templates->resolve($templateKey ?? $portfolio->template_key);
        $settings = $this->schema->merge($portfolio->settings ?? [], $template->defaultSettings);

        $sections = $this->sectionsFor($portfolio, $template);
        $images = $this->imagesFor($portfolio, $sections, $imageUrl);

        $ogImage = null;
        $ogMediaId = $portfolio->meta['og_image_media_id'] ?? null;

        if ($ogMediaId !== null && isset($images[(int) $ogMediaId])) {
            $ogImage = $images[(int) $ogMediaId]['url'];
        }

        return new RenderContext(
            mode: $mode,
            templateKey: $template->key,
            portfolio: [
                'name' => $portfolio->name,
                'slug' => $portfolio->slug,
                'meta' => $portfolio->meta ?? [],
            ],
            sections: $sections,
            settings: $settings,
            images: $images,
            themeCss: $this->compiler->compile($settings),
            fontCss: $this->fonts->faceCss($this->compiler->fontSlugs($settings), $fontBaseUrl),
            styleHref: $styleHref,
            scriptSrc: $template->hasScript() ? $scriptSrc : null,
            ogImageUrl: $ogImage,
        );
    }

    public function render(RenderContext $context): string
    {
        return $this->templates->resolve($context->templateKey)->renderer()->render($context);
    }

    /**
     * Enabled sections the template can display, in portfolio order.
     *
     * A section the template does not support is skipped here and nowhere else:
     * the row keeps its data, so switching back restores the section intact.
     *
     * @return array<int, array{id:int,type:SectionType,data:array<string,mixed>,settings:array<string,mixed>}>
     */
    private function sectionsFor(Portfolio $portfolio, Template $template): array
    {
        $sections = $portfolio->relationLoaded('sections')
            ? $portfolio->sections
            : $portfolio->sections()->get();

        $out = [];

        foreach ($sections->sortBy('position') as $section) {
            if (! $section->enabled || ! $template->supports($section->type)) {
                continue;
            }

            $out[] = [
                'id' => $section->id,
                'type' => $section->type,
                'data' => $section->data ?? [],
                'settings' => $section->settings ?? [],
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, array{data:array<string,mixed>}>  $sections
     * @param  Closure(Media): string  $imageUrl
     * @return array<int, array{url:string,width:int|null,height:int|null,alt:string}>
     */
    private function imagesFor(Portfolio $portfolio, array $sections, Closure $imageUrl): array
    {
        $ids = MediaReferences::collect(array_column($sections, 'data'));

        foreach (MediaReferences::collect([$portfolio->meta ?? []]) as $id) {
            $ids[] = $id;
        }

        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return [];
        }

        // Scoped to the portfolio's owner: a crafted media id in someone else's
        // payload can never pull a file that is not theirs into the page.
        $media = Media::query()
            ->whereIn('id', $ids)
            ->where('user_id', $portfolio->user_id)
            ->get();

        $images = [];

        foreach ($media as $item) {
            $images[$item->id] = [
                'url' => $imageUrl($item),
                'width' => $item->width,
                'height' => $item->height,
                'alt' => '',
            ];
        }

        return $images;
    }
}
