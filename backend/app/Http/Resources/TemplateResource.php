<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\Templates\Template;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Template */
final class TemplateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Template $template */
        $template = $this->resource;

        return array_merge($template->toArray(), [
            // Everything the client needs to render a preview itself: the
            // stylesheet URL and a live-rendered demo page, so the gallery
            // shows the real template rather than a screenshot that ages.
            'style_url' => route('templates.styles', $template->key),
            'demo_url' => route('templates.demo', $template->key),
        ]);
    }
}
