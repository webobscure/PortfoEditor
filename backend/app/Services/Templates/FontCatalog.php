<?php

declare(strict_types=1);

namespace App\Services\Templates;

use Illuminate\Support\Facades\Cache;

/**
 * Curated, self-hosted webfonts.
 *
 * Exported portfolios must run with no network at all, so fonts are shipped as
 * woff2 files inside the archive rather than linked from a CDN. Only the two
 * families a portfolio actually uses are bundled, which keeps a typical export
 * under ~150 KB of font data instead of the ~2.4 MB catalogue.
 */
final class FontCatalog
{
    /** @var array<int, array{slug:string,family:string,subset:string,weight:string,style:string,file:string,range:string}>|null */
    private ?array $faces = null;

    /**
     * @return array<string, array{key:string,name:string,category:string,stack:string,roles:array<int,string>}>
     */
    public function all(): array
    {
        $families = [];

        foreach (config('fonts.families', []) as $key => $family) {
            $families[$key] = [
                'key' => $key,
                'name' => $family['name'],
                'category' => $family['category'],
                'stack' => $family['stack'],
                'roles' => $family['roles'],
            ];
        }

        return $families;
    }

    /** @return array<int, string> */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /** CSS font stack for a family, falling back to Inter's stack when unknown. */
    public function stack(string $key): string
    {
        $families = $this->all();

        return $families[$key]['stack']
            ?? $families['inter']['stack']
            ?? 'system-ui, sans-serif';
    }

    /**
     * @param  array<int, string>  $slugs
     * @return array<int, array{slug:string,family:string,subset:string,weight:string,style:string,file:string,range:string}>
     */
    public function facesFor(array $slugs): array
    {
        $wanted = array_values(array_unique(array_filter($slugs)));

        return array_values(array_filter(
            $this->manifest(),
            fn (array $face) => in_array($face['slug'], $wanted, true)
        ));
    }

    /**
     * Build the @font-face block for the given families.
     *
     * $baseUrl is prepended to every file name, which is the one thing that
     * differs between the preview (absolute API URL) and an export (the
     * relative "assets/fonts" folder inside the archive).
     *
     * @param  array<int, string>  $slugs
     */
    public function faceCss(array $slugs, string $baseUrl): string
    {
        $base = rtrim($baseUrl, '/');
        $lines = [];

        foreach ($this->facesFor($slugs) as $face) {
            $range = $face['range'] !== '' ? "\n  unicode-range: {$face['range']};" : '';
            $lines[] = <<<CSS
            @font-face {
              font-family: '{$face['family']}';
              font-style: {$face['style']};
              font-weight: {$face['weight']};
              font-display: swap;
              src: url('{$base}/{$face['file']}') format('woff2');{$range}
            }
            CSS;
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<int, array{slug:string,family:string,subset:string,weight:string,style:string,file:string,range:string}>
     */
    public function manifest(): array
    {
        if ($this->faces !== null) {
            return $this->faces;
        }

        $this->faces = Cache::rememberForever('fonts.manifest', function (): array {
            $path = rtrim((string) config('fonts.path'), '/').'/manifest.tsv';

            if (! is_file($path)) {
                return [];
            }

            $rows = [];

            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $parts = explode("\t", $line);

                if (count($parts) < 6) {
                    continue;
                }

                $rows[] = [
                    'slug' => $parts[0],
                    'family' => $parts[1],
                    'subset' => $parts[2],
                    'weight' => $parts[3],
                    'style' => $parts[4],
                    'file' => $parts[5],
                    'range' => $parts[6] ?? '',
                ];
            }

            return $rows;
        });

        return $this->faces;
    }

    public function filePath(string $file): ?string
    {
        // Never trust the caller with a path: only exact manifest entries resolve.
        foreach ($this->manifest() as $face) {
            if ($face['file'] === $file) {
                return rtrim((string) config('fonts.path'), '/').'/'.$file;
            }
        }

        return null;
    }
}
