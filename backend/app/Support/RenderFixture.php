<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\SectionType;
use App\Models\Portfolio;
use App\Models\PortfolioSection;
use App\Services\Export\PortfolioRenderer;
use App\Services\Templates\FontCatalog;
use App\Services\Templates\RenderContext;
use App\Services\Templates\TemplateRegistry;
use App\Services\Templates\ThemeCompiler;
use App\Services\Templates\ThemeSchema;

/**
 * The shared fixture behind the render-parity tests.
 *
 * One JSON document describes a portfolio; PHP renders it into a golden HTML
 * file and the Vitest suite renders the same JSON with the TypeScript renderer
 * and compares against the same golden file. Neither renderer can drift without
 * turning the other suite red.
 */
final class RenderFixture
{
    public static function path(string $name): string
    {
        return base_path('tests/Fixtures/'.$name);
    }

    /** @return array<string, mixed> */
    public static function portfolio(): array
    {
        return json_decode((string) file_get_contents(self::path('portfolio.json')), true);
    }

    /** @return array<int, string> */
    public static function templateKeys(): array
    {
        return app(TemplateRegistry::class)->keys();
    }

    /** Renders the fixture through the PHP renderer in export mode. */
    public static function render(string $templateKey): string
    {
        return self::renderWith($templateKey);
    }

    /**
     * Render the fixture, optionally rewriting the sections first.
     *
     * The hook exists for the escaping tests: they need hostile content in the
     * payload without a second copy of the fixture.
     *
     * @param  (callable(array<int, array<string, mixed>>): array<int, array<string, mixed>>)|null  $mutate
     */
    public static function renderWith(string $templateKey, ?callable $mutate = null): string
    {
        $fixture = self::portfolio();
        $fixture['sections'] = $mutate ? $mutate($fixture['sections']) : $fixture['sections'];

        $portfolio = new Portfolio([
            'name' => $fixture['name'],
            'slug' => $fixture['slug'],
            'template_key' => $templateKey,
            'settings' => $fixture['settings'],
            'meta' => $fixture['meta'],
        ]);
        $portfolio->id = 1;
        $portfolio->user_id = 1;

        $sections = collect();

        foreach ($fixture['sections'] as $index => $section) {
            $model = new PortfolioSection([
                'type' => SectionType::from($section['type']),
                'position' => $index,
                'enabled' => true,
                'data' => $section['content'],
                'settings' => [],
            ]);
            $model->id = $index + 1;
            $sections->push($model);
        }

        $portfolio->setRelation('sections', $sections);

        $context = app(PortfolioRenderer::class)->context(
            portfolio: $portfolio,
            mode: RenderContext::MODE_EXPORT,
            imageUrl: fn ($media) => 'assets/images/'.$media->exportFilename(),
            styleHref: 'assets/css/styles.css',
            fontBaseUrl: 'assets/fonts',
        );

        return app(PortfolioRenderer::class)->render($context);
    }

    /**
     * The font data the TypeScript side needs to build identical @font-face
     * rules and font stacks.
     *
     * @return array<string, mixed>
     */
    public static function fonts(): array
    {
        $catalog = app(FontCatalog::class);
        $schema = app(ThemeSchema::class);
        $compiler = app(ThemeCompiler::class);

        return [
            'families' => array_values($catalog->all()),
            'faces' => $catalog->manifest(),
            'defaults' => $schema->defaults(),
            'template_defaults' => collect(app(TemplateRegistry::class)->all())
                ->map(fn ($template) => $template->defaultSettings)
                ->all(),
            'compiled' => $compiler->compile($schema->defaults()),
        ];
    }
}
