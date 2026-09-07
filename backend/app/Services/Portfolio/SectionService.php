<?php

declare(strict_types=1);

namespace App\Services\Portfolio;

use App\Enums\SectionType;
use App\Models\Portfolio;
use App\Models\PortfolioSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Section lifecycle: creation, updates, ordering and deletion.
 *
 * Sections seeded at creation carry `settings.placeholder = true`. The flag is
 * cleared the first time the user edits the section, which is how the editor
 * can honestly label sample copy without a second source of truth about what
 * the user has actually written.
 */
final class SectionService
{
    public function __construct(private readonly SectionSchemaRegistry $schemas) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromPreset(Portfolio $portfolio, SectionType $type, array $data, int $position): PortfolioSection
    {
        return $portfolio->sections()->create([
            'type' => $type,
            'position' => $position,
            'enabled' => true,
            'data' => $this->schemas->normalise($type, $data),
            'settings' => ['placeholder' => true],
        ]);
    }

    /**
     * Add an empty section of the given type.
     *
     * Singleton types are rejected here rather than by a unique index, because
     * the rule is a product decision that differs per type and is likely to be
     * relaxed (two project galleries) before it is tightened.
     */
    public function create(Portfolio $portfolio, SectionType $type, ?int $position = null): PortfolioSection
    {
        if ($type->isSingleton() && $portfolio->sections()->where('type', $type->value)->exists()) {
            throw ValidationException::withMessages([
                'type' => "Секция «{$type->label()}» уже есть в этом портфолио.",
            ]);
        }

        $position ??= (int) $portfolio->sections()->max('position') + 1;

        return DB::transaction(function () use ($portfolio, $type, $position) {
            $portfolio->sections()
                ->where('position', '>=', $position)
                ->increment('position');

            return $portfolio->sections()->create([
                'type' => $type,
                'position' => $position,
                'enabled' => true,
                'data' => $this->schemas->defaults($type),
                'settings' => [],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes  validated: content, enabled, settings
     */
    public function update(PortfolioSection $section, array $attributes): PortfolioSection
    {
        $changes = [];

        if (array_key_exists('content', $attributes)) {
            // Merged, not replaced: the editor sends only what changed, and a
            // partial payload must never blank out the rest of the section.
            $changes['data'] = array_replace(
                $this->schemas->normalise($section->type, $section->data ?? []),
                $attributes['content']
            );

            $settings = $section->settings ?? [];
            unset($settings['placeholder']);
            $changes['settings'] = $settings;
        }

        if (array_key_exists('enabled', $attributes)) {
            $changes['enabled'] = (bool) $attributes['enabled'];
        }

        if (array_key_exists('settings', $attributes)) {
            $changes['settings'] = array_replace(
                $changes['settings'] ?? ($section->settings ?? []),
                $attributes['settings']
            );
        }

        $section->update($changes);

        return $section->refresh();
    }

    /**
     * Apply a new order.
     *
     * Ids that do not belong to the portfolio are ignored rather than trusted,
     * so a tampered payload can only reorder the caller's own sections.
     *
     * @param  array<int, int>  $orderedIds
     * @return Collection<int, PortfolioSection>
     */
    public function reorder(Portfolio $portfolio, array $orderedIds)
    {
        $sections = $portfolio->sections()->get()->keyBy('id');

        DB::transaction(function () use ($orderedIds, $sections) {
            $position = 0;

            foreach ($orderedIds as $id) {
                $section = $sections->get((int) $id);

                if ($section === null) {
                    continue;
                }

                $section->update(['position' => $position++]);
            }

            // Anything the client did not mention keeps its relative order at
            // the end, so a stale payload cannot silently drop a section.
            foreach ($sections->sortBy('position') as $section) {
                if (! in_array($section->id, array_map('intval', $orderedIds), true)) {
                    $section->update(['position' => $position++]);
                }
            }
        });

        return $portfolio->sections()->get();
    }

    public function delete(PortfolioSection $section): void
    {
        $section->delete();
    }
}
