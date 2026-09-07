<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Portfolio;
use App\Services\Portfolio\PortfolioService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Portfolio */
final class PortfolioResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'template_key' => $this->template_key,
            'status' => $this->status->value,
            'preset' => $this->preset,

            // `settings` holds only what the user changed; `effective_settings`
            // is that merged over the template's defaults. The editor renders
            // from the effective set and writes back sparse overrides, which is
            // what lets a template switch restyle the page without discarding
            // a colour the user picked deliberately.
            'settings' => $this->settings ?? [],
            'effective_settings' => app(PortfolioService::class)->effectiveSettings($this->resource),

            'meta' => $this->meta ?? [],
            'sections' => PortfolioSectionResource::collection($this->whenLoaded('sections')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
