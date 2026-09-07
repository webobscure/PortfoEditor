<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Portfolio;
use App\Services\Export\PortfolioRenderer;
use App\Services\Templates\RenderContext;
use App\Services\Templates\TemplateRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Server-rendered preview of a real portfolio.
 *
 * The editor does not need this to type — it renders locally from its own state
 * — but the "open preview in a new tab" action and the template gallery do, and
 * having it keeps a server-side proof that the export path renders correctly.
 */
final class PreviewController extends Controller
{
    public function __invoke(
        Request $request,
        Portfolio $portfolio,
        PortfolioRenderer $renderer,
        TemplateRegistry $templates,
    ): Response {
        $this->authorize('view', $portfolio);

        // ?template= lets the gallery show this portfolio's real content in a
        // different template without saving anything.
        $requested = $request->string('template')->toString();
        $templateKey = $templates->has($requested) ? $requested : $portfolio->template_key;

        $context = $renderer->context(
            portfolio: $portfolio->load('sections'),
            mode: RenderContext::MODE_PREVIEW,
            imageUrl: fn (Media $media): string => $media->url(),
            styleHref: route('templates.styles', $templateKey),
            fontBaseUrl: route('fonts.index'),
            templateKey: $templateKey,
        );

        return response($renderer->render($context), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }
}
