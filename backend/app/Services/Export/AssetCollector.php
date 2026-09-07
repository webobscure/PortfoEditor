<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\Media;
use App\Models\Portfolio;
use App\Services\Templates\FontCatalog;
use App\Support\MediaReferences;
use Illuminate\Support\Facades\Storage;

/**
 * Gathers every file an exported portfolio needs.
 *
 * Only what the page actually references is collected — the two fonts in use,
 * not the twelve in the catalogue; the images referenced by enabled sections,
 * not everything the user ever uploaded.
 */
final class AssetCollector
{
    public function __construct(private readonly FontCatalog $fonts) {}

    /**
     * Images referenced by the portfolio, keyed by media id.
     *
     * @return array<int, array{media:Media,filename:string,contents:string}>
     */
    public function images(Portfolio $portfolio): array
    {
        $sections = $portfolio->relationLoaded('sections')
            ? $portfolio->sections
            : $portfolio->sections()->get();

        $ids = MediaReferences::collect(
            $sections->where('enabled', true)->pluck('data')->all()
        );

        foreach (MediaReferences::collect([$portfolio->meta ?? []]) as $id) {
            $ids[] = $id;
        }

        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return [];
        }

        $media = Media::query()
            ->whereIn('id', $ids)
            ->where('user_id', $portfolio->user_id)
            ->get();

        $collected = [];

        foreach ($media as $item) {
            $disk = Storage::disk($item->disk);

            if (! $disk->exists($item->path)) {
                // A missing file must not abort an export; the page simply
                // renders without that image.
                continue;
            }

            $collected[$item->id] = [
                'media' => $item,
                'filename' => $item->exportFilename(),
                'contents' => (string) $disk->get($item->path),
            ];
        }

        return $collected;
    }

    /**
     * Font files for the given families.
     *
     * @param  array<int, string>  $slugs
     * @return array<int, array{filename:string,path:string}>
     */
    public function fonts(array $slugs): array
    {
        $files = [];

        foreach ($this->fonts->facesFor($slugs) as $face) {
            $path = $this->fonts->filePath($face['file']);

            if ($path !== null && is_file($path)) {
                $files[] = ['filename' => $face['file'], 'path' => $path];
            }
        }

        return $files;
    }
}
