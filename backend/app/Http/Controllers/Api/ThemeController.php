<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\SectionType;
use App\Http\Controllers\Controller;
use App\Services\Portfolio\PortfolioPresets;
use App\Services\Portfolio\SectionSchemaRegistry;
use App\Services\Templates\ThemeSchema;
use Illuminate\Http\JsonResponse;

/**
 * The editor's vocabulary, served from the server.
 *
 * Font stacks, allowed scales, section types and social platforms all come from
 * here rather than being duplicated in the frontend, so the theme compiler in
 * TypeScript and the one in PHP cannot disagree about what a font is.
 */
final class ThemeController extends Controller
{
    public function __invoke(
        ThemeSchema $schema,
        SectionSchemaRegistry $sections,
        PortfolioPresets $presets,
    ): JsonResponse {
        return response()->json([
            'data' => array_merge($schema->options(), [
                'section_types' => array_map(
                    fn (SectionType $type) => [
                        'type' => $type->value,
                        'label' => $type->label(),
                        'singleton' => $type->isSingleton(),
                        'defaults' => $sections->defaults($type),
                    ],
                    SectionType::cases()
                ),
                'social_platforms' => $sections->socialPlatforms(),
                'presets' => $presets->all(),
            ]),
        ]);
    }
}
