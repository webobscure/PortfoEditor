<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PortfolioSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PortfolioSection */
final class PortfolioSectionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $settings = $this->settings ?? [];

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'label' => $this->type->label(),
            'position' => $this->position,
            'enabled' => $this->enabled,
            // Named `content` rather than `data`: Laravel's resource envelope is
            // also called `data`, and a payload that already contains that key
            // is returned unwrapped, which would make this one endpoint's shape
            // differ from every other.
            'content' => $this->data ?? [],
            'settings' => $settings,
            // Surfaced explicitly so the editor can label seeded copy as a
            // sample instead of pretending the user wrote it.
            'is_placeholder' => (bool) ($settings['placeholder'] ?? false),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
