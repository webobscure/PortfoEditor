<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Font catalogue
|--------------------------------------------------------------------------
|
| A deliberately small, curated set. Every family is self-hosted from
| resources/fonts so an exported portfolio never talks to a CDN.
|
| "slug" matches the file prefix in resources/fonts and the manifest.
| "stack" is the CSS fallback chain used before the webfont resolves and
| after it fails; it must always be usable on its own.
|
*/

return [

    'families' => [
        'inter' => [
            'name' => 'Inter',
            'category' => 'sans',
            'stack' => '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            'roles' => ['heading', 'body'],
        ],
        'manrope' => [
            'name' => 'Manrope',
            'category' => 'sans',
            'stack' => '"Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'roles' => ['heading', 'body'],
        ],
        'dm-sans' => [
            'name' => 'DM Sans',
            'category' => 'sans',
            'stack' => '"DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'roles' => ['heading', 'body'],
        ],
        'sora' => [
            'name' => 'Sora',
            'category' => 'sans',
            'stack' => '"Sora", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'roles' => ['heading', 'body'],
        ],
        'space-grotesk' => [
            'name' => 'Space Grotesk',
            'category' => 'sans',
            'stack' => '"Space Grotesk", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'roles' => ['heading', 'body'],
        ],
        'ibm-plex-sans' => [
            'name' => 'IBM Plex Sans',
            'category' => 'sans',
            'stack' => '"IBM Plex Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'roles' => ['heading', 'body'],
        ],
        'jetbrains-mono' => [
            'name' => 'JetBrains Mono',
            'category' => 'mono',
            'stack' => '"JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace',
            'roles' => ['heading', 'body', 'mono'],
        ],
        'fraunces' => [
            'name' => 'Fraunces',
            'category' => 'serif',
            'stack' => '"Fraunces", Georgia, "Times New Roman", serif',
            'roles' => ['heading'],
        ],
        'playfair-display' => [
            'name' => 'Playfair Display',
            'category' => 'serif',
            'stack' => '"Playfair Display", Georgia, "Times New Roman", serif',
            'roles' => ['heading'],
        ],
        'instrument-serif' => [
            'name' => 'Instrument Serif',
            'category' => 'serif',
            'stack' => '"Instrument Serif", Georgia, "Times New Roman", serif',
            'roles' => ['heading'],
        ],
        'newsreader' => [
            'name' => 'Newsreader',
            'category' => 'serif',
            'stack' => '"Newsreader", Georgia, "Times New Roman", serif',
            'roles' => ['heading', 'body'],
        ],
        'libre-baskerville' => [
            'name' => 'Libre Baskerville',
            'category' => 'serif',
            'stack' => '"Libre Baskerville", Georgia, "Times New Roman", serif',
            'roles' => ['heading', 'body'],
        ],
    ],

    /*
     | Where the woff2 files and the manifest generated at build time live.
     | The manifest is a TSV: slug, family, subset, weight, style, file, range
     */
    'path' => resource_path('fonts'),

];
