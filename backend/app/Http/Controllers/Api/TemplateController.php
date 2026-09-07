<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\SectionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateResource;
use App\Models\Media;
use App\Models\Portfolio;
use App\Models\PortfolioSection;
use App\Services\Export\PortfolioRenderer;
use App\Services\Portfolio\PortfolioPresets;
use App\Services\Portfolio\SectionSchemaRegistry;
use App\Services\Templates\RenderContext;
use App\Services\Templates\Template;
use App\Services\Templates\TemplateAssets;
use App\Services\Templates\TemplateRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class TemplateController extends Controller
{
    public function __construct(
        private readonly TemplateRegistry $templates,
        private readonly TemplateAssets $assets,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => TemplateResource::collection(array_values($this->templates->all()))->resolve(),
        ]);
    }

    public function show(string $template): TemplateResource
    {
        return TemplateResource::make($this->resolve($template));
    }

    /**
     * The stylesheet the preview iframe loads.
     *
     * It is byte-identical to the one written into an export, because both come
     * from TemplateAssets::stylesheet().
     */
    public function styles(string $template): Response
    {
        $css = $this->assets->stylesheet($this->resolve($template));

        return response($css, 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    /**
     * A live-rendered demo page for the template gallery.
     *
     * Rendering the real template with real demo content beats a screenshot:
     * it can never go stale, and it is the same renderer the editor and the
     * export use, so what the gallery shows is what the user gets.
     */
    public function demo(
        Request $request,
        string $template,
        PortfolioRenderer $renderer,
        PortfolioPresets $presets,
        SectionSchemaRegistry $schemas,
    ): Response {
        $resolved = $this->resolve($template);
        $preset = $request->string('preset')->toString();
        $preset = $presets->exists($preset) ? $preset : 'designer';

        $portfolio = $this->demoPortfolio($resolved->key, $preset, $presets, $schemas);

        $context = $renderer->context(
            portfolio: $portfolio,
            mode: RenderContext::MODE_PREVIEW,
            imageUrl: fn (Media $media): string => $media->url(),
            styleHref: route('templates.styles', $resolved->key),
            fontBaseUrl: route('fonts.index'),
        );

        return response($renderer->render($context), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            // Framed by the gallery on the app's own origin only.
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    public function preview(string $template): SymfonyResponse
    {
        $resolved = $this->resolve($template);
        $path = $resolved->previewPath();

        abort_unless(is_file($path), 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }

    /** An in-memory portfolio: nothing is written to the database. */
    private function demoPortfolio(
        string $templateKey,
        string $preset,
        PortfolioPresets $presets,
        SectionSchemaRegistry $schemas,
    ): Portfolio {
        $portfolio = new Portfolio([
            'name' => 'Demo',
            'slug' => 'demo',
            'template_key' => $templateKey,
            'settings' => [],
            'meta' => [],
        ]);
        $portfolio->id = 0;
        $portfolio->user_id = 0;

        $sections = collect();
        $position = 0;

        foreach ($presets->sections($preset) as $definition) {
            /** @var SectionType $type */
            $type = $definition['type'];

            $section = new PortfolioSection([
                'type' => $type,
                'position' => $position,
                'enabled' => true,
                'data' => $schemas->normalise($type, $definition['data']),
                'settings' => [],
            ]);
            $section->id = ++$position;

            $sections->push($section);
        }

        $portfolio->setRelation('sections', $sections);

        return $portfolio;
    }

    private function resolve(string $key): Template
    {
        return $this->templates->find($key) ?? abort(404, 'Template not found.');
    }
}
