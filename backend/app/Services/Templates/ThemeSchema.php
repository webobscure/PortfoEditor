<?php

declare(strict_types=1);

namespace App\Services\Templates;

/**
 * The controlled schema behind the `portfolios.settings` JSON column.
 *
 * Settings are stored as JSON because the shape genuinely varies per template,
 * but the *allowed* shape is fixed here, validated on write, and exposed to the
 * client through GET /api/theme/options so the editor builds its controls from
 * the server's vocabulary instead of a hardcoded copy.
 */
final class ThemeSchema
{
    public const TYPE_SCALES = ['compact' => 0.94, 'default' => 1.0, 'large' => 1.08];

    public const SPACING_SCALES = ['compact' => 0.72, 'default' => 1.0, 'spacious' => 1.32];

    public const CONTENT_WIDTHS = ['narrow' => 720, 'default' => 960, 'wide' => 1180];

    public const BUTTON_RADII = ['rounded' => '10px', 'square' => '2px', 'pill' => '999px'];

    public function __construct(private readonly FontCatalog $fonts) {}

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'colors' => [
                'accent' => '#4f46e5',
                'background' => '#ffffff',
                'surface' => '#f7f7f8',
                'text' => '#101014',
                'muted' => '#6b6b76',
                'border' => '#e6e6ea',
            ],
            'typography' => [
                'heading_font' => 'inter',
                'body_font' => 'inter',
                'scale' => 'default',
            ],
            'layout' => [
                'section_spacing' => 'default',
                'content_width' => 'default',
            ],
            'buttons' => [
                'style' => 'rounded',
            ],
            'template' => [],
        ];
    }

    /**
     * Validation rules for a settings payload, rooted at $prefix.
     *
     * @return array<string, mixed>
     */
    public function rules(string $prefix = 'settings'): array
    {
        $hex = ['string', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $fontKeys = implode(',', $this->fonts->keys());

        $rules = [
            'colors' => ['sometimes', 'array'],
            'colors.accent' => array_merge(['sometimes'], $hex),
            'colors.background' => array_merge(['sometimes'], $hex),
            'colors.surface' => array_merge(['sometimes'], $hex),
            'colors.text' => array_merge(['sometimes'], $hex),
            'colors.muted' => array_merge(['sometimes'], $hex),
            'colors.border' => array_merge(['sometimes'], $hex),

            'typography' => ['sometimes', 'array'],
            'typography.heading_font' => ['sometimes', 'string', 'in:'.$fontKeys],
            'typography.body_font' => ['sometimes', 'string', 'in:'.$fontKeys],
            'typography.scale' => ['sometimes', 'string', 'in:'.implode(',', array_keys(self::TYPE_SCALES))],

            'layout' => ['sometimes', 'array'],
            'layout.section_spacing' => ['sometimes', 'string', 'in:'.implode(',', array_keys(self::SPACING_SCALES))],
            'layout.content_width' => ['sometimes', 'string', 'in:'.implode(',', array_keys(self::CONTENT_WIDTHS))],

            'buttons' => ['sometimes', 'array'],
            'buttons.style' => ['sometimes', 'string', 'in:'.implode(',', array_keys(self::BUTTON_RADII))],

            // Template-specific knobs. Values are constrained to scalars so a
            // template can never smuggle structured data into the theme.
            'template' => ['sometimes', 'array'],
            'template.*' => ['nullable', 'string', 'max:64'],
        ];

        $prefixed = [];

        foreach ($rules as $key => $rule) {
            $prefixed[$prefix.'.'.$key] = $rule;
        }

        return $prefixed;
    }

    /**
     * Merge user settings over template defaults over schema defaults.
     *
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $templateDefaults
     * @return array<string, mixed>
     */
    public function merge(array $settings, array $templateDefaults = []): array
    {
        $merged = $this->defaults();

        foreach ([$templateDefaults, $settings] as $layer) {
            foreach ($layer as $group => $values) {
                if (! array_key_exists($group, $merged)) {
                    continue;
                }

                if (is_array($values)) {
                    $merged[$group] = array_replace($merged[$group], array_filter(
                        $values,
                        static fn ($value) => $value !== null && $value !== ''
                    ));
                }
            }
        }

        return $merged;
    }

    /**
     * Options payload for the editor UI.
     *
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'fonts' => array_values($this->fonts->all()),
            'typography_scales' => array_keys(self::TYPE_SCALES),
            'section_spacings' => array_keys(self::SPACING_SCALES),
            'content_widths' => array_keys(self::CONTENT_WIDTHS),
            'button_styles' => array_keys(self::BUTTON_RADII),
            'defaults' => $this->defaults(),
        ];
    }
}
