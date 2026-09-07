<?php

declare(strict_types=1);

namespace App\Services\Portfolio;

use App\Enums\PortfolioStatus;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\Templates\TemplateRegistry;
use App\Services\Templates\ThemeSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creating, restyling and cloning portfolios.
 *
 * One decision shapes this whole class: `portfolios.settings` stores only the
 * values the user has explicitly changed, never a full snapshot. Effective
 * settings are schema defaults, then template defaults, then those overrides.
 * That is what makes switching templates feel right — the design changes
 * immediately, but a colour the user picked on purpose survives the switch.
 */
final class PortfolioService
{
    public function __construct(
        private readonly TemplateRegistry $templates,
        private readonly ThemeSchema $schema,
        private readonly PortfolioPresets $presets,
        private readonly SectionService $sections,
    ) {}

    /**
     * @param  array{name:string,preset?:string,template_key?:string,profile?:array<string,string>}  $input
     */
    public function create(User $user, array $input): Portfolio
    {
        $preset = $input['preset'] ?? 'other';
        $preset = $this->presets->exists($preset) ? $preset : 'other';

        $templateKey = $input['template_key'] ?? $this->presets->templateFor($preset);
        $template = $this->templates->resolve($templateKey);

        return DB::transaction(function () use ($user, $input, $preset, $template) {
            $portfolio = $user->portfolios()->create([
                'name' => $input['name'],
                'slug' => $this->uniqueSlug($input['name']),
                'template_key' => $template->key,
                'status' => PortfolioStatus::Draft,
                'preset' => $preset,
                'settings' => [],
                'meta' => [],
            ]);

            $position = 0;

            foreach ($this->presets->sections($preset, $input['profile'] ?? []) as $section) {
                $this->sections->createFromPreset($portfolio, $section['type'], $section['data'], $position++);
            }

            return $portfolio->load('sections');
        });
    }

    /**
     * Switch template without touching a single section row.
     *
     * Sections the new template cannot display stay in the database and simply
     * stop rendering, so the switch is always reversible.
     */
    public function applyTemplate(Portfolio $portfolio, string $templateKey): Portfolio
    {
        $template = $this->templates->resolve($templateKey);

        $portfolio->update(['template_key' => $template->key]);

        return $portfolio->refresh();
    }

    /**
     * Merge sparse overrides into the stored settings.
     *
     * @param  array<string, mixed>  $settings
     */
    public function updateSettings(Portfolio $portfolio, array $settings): Portfolio
    {
        $current = $portfolio->settings ?? [];

        foreach ($settings as $group => $values) {
            if (! is_array($values)) {
                continue;
            }

            $current[$group] = array_replace($current[$group] ?? [], $values);
        }

        $portfolio->update(['settings' => $current]);

        return $portfolio->refresh();
    }

    /**
     * Schema defaults, then template defaults, then the user's overrides.
     *
     * @return array<string, mixed>
     */
    public function effectiveSettings(Portfolio $portfolio): array
    {
        $template = $this->templates->resolve($portfolio->template_key);

        return $this->schema->merge($portfolio->settings ?? [], $template->defaultSettings);
    }

    /**
     * A slug that is unique across every account, because slugs become
     * subdomains once publishing ships.
     */
    public function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'portfolio';
        $base = Str::limit($base, 60, '');

        if (in_array($base, (array) config('portfolio.reserved_slugs'), true)) {
            $base .= '-site';
        }

        $slug = $base;
        $suffix = 1;

        while (Portfolio::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
