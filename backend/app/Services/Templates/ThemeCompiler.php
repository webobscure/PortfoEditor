<?php

declare(strict_types=1);

namespace App\Services\Templates;

use App\Support\Color;

/**
 * Turns validated settings into the CSS custom properties every template is
 * written against.
 *
 * This is the contract between the theme UI and the stylesheets: a template
 * never reads settings, it only consumes these variables. Adding a template
 * therefore cannot require changes to the settings model.
 *
 * The variable order below is fixed and mirrored exactly by
 * frontend/src/templates/shared/theme.ts, because the golden-file tests diff
 * the two compilers' output character for character.
 */
final class ThemeCompiler
{
    public function __construct(
        private readonly ThemeSchema $schema,
        private readonly FontCatalog $fonts,
    ) {}

    /**
     * @param  array<string, mixed>  $settings  already merged with template defaults
     * @return array<string, string>
     */
    public function variables(array $settings): array
    {
        $merged = $this->schema->merge($settings);

        /** @var array<string, string> $colors */
        $colors = $merged['colors'];
        $typography = $merged['typography'];
        $layout = $merged['layout'];
        $buttons = $merged['buttons'];

        $accent = $colors['accent'];

        return [
            '--pf-accent' => $accent,
            '--pf-accent-strong' => Color::scale($accent, 0.86),
            '--pf-accent-soft' => Color::rgba($accent, 0.12),
            '--pf-accent-contrast' => Color::readableOn($accent),
            '--pf-bg' => $colors['background'],
            '--pf-surface' => $colors['surface'],
            '--pf-text' => $colors['text'],
            '--pf-muted' => $colors['muted'],
            '--pf-border' => $colors['border'],
            '--pf-font-heading' => $this->fonts->stack($typography['heading_font']),
            '--pf-font-body' => $this->fonts->stack($typography['body_font']),
            '--pf-type-scale' => $this->number(ThemeSchema::TYPE_SCALES[$typography['scale']] ?? 1.0),
            '--pf-space-scale' => $this->number(ThemeSchema::SPACING_SCALES[$layout['section_spacing']] ?? 1.0),
            '--pf-content-width' => (ThemeSchema::CONTENT_WIDTHS[$layout['content_width']] ?? 960).'px',
            '--pf-btn-radius' => ThemeSchema::BUTTON_RADII[$buttons['style']] ?? '10px',
        ];
    }

    /**
     * The :root block injected ahead of the template stylesheet.
     *
     * @param  array<string, mixed>  $settings
     */
    public function compile(array $settings): string
    {
        $lines = [];

        foreach ($this->variables($settings) as $name => $value) {
            $lines[] = "  {$name}: {$value};";
        }

        return ":root {\n".implode("\n", $lines)."\n}";
    }

    /**
     * The font families that must be bundled for these settings.
     *
     * @param  array<string, mixed>  $settings
     * @return array<int, string>
     */
    public function fontSlugs(array $settings): array
    {
        $merged = $this->schema->merge($settings);

        return array_values(array_unique([
            $merged['typography']['heading_font'],
            $merged['typography']['body_font'],
        ]));
    }

    /** Trailing-zero-free decimal so PHP and JS agree on "1" vs "1.0". */
    private function number(float $value): string
    {
        $formatted = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
