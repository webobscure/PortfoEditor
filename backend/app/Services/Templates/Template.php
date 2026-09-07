<?php

declare(strict_types=1);

namespace App\Services\Templates;

use App\Enums\SectionType;

/**
 * A template as described by its template.json manifest.
 *
 * Templates are code-defined and discovered from disk. Nothing about them lives
 * in the database, so a template can be redesigned, or its supported sections
 * changed, without a migration — and a portfolio pointing at a template that no
 * longer exists degrades to the default instead of 500ing.
 */
final class Template
{
    /**
     * @param  array<int, string>  $tags
     * @param  array<int, SectionType>  $supportedSections
     * @param  array<string, mixed>  $defaultSettings
     * @param  array<int, array<string, mixed>>  $colorSchemes
     * @param  array<string, mixed>  $capabilities
     */
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $description,
        public readonly array $tags,
        public readonly array $supportedSections,
        public readonly array $defaultSettings,
        public readonly array $colorSchemes,
        public readonly array $capabilities,
        public readonly string $rendererClass,
        public readonly string $directory,
    ) {}

    /**
     * @param  array<string, mixed>  $manifest
     */
    public static function fromManifest(array $manifest, string $directory): self
    {
        $sections = [];

        foreach ((array) ($manifest['supported_sections'] ?? []) as $value) {
            $type = SectionType::tryFrom((string) $value);

            if ($type !== null) {
                $sections[] = $type;
            }
        }

        return new self(
            key: (string) $manifest['key'],
            name: (string) ($manifest['name'] ?? $manifest['key']),
            description: (string) ($manifest['description'] ?? ''),
            tags: array_map('strval', (array) ($manifest['tags'] ?? [])),
            supportedSections: $sections,
            defaultSettings: (array) ($manifest['defaults']['settings'] ?? []),
            colorSchemes: array_map(
                static fn ($scheme) => (array) $scheme,
                (array) ($manifest['color_schemes'] ?? [])
            ),
            capabilities: (array) ($manifest['capabilities'] ?? []),
            rendererClass: __NAMESPACE__.'\\Renderers\\'.(string) ($manifest['renderer'] ?? 'MinimalRenderer'),
            directory: $directory,
        );
    }

    public function supports(SectionType $type): bool
    {
        return in_array($type, $this->supportedSections, true);
    }

    public function hasScript(): bool
    {
        return (bool) ($this->capabilities['script'] ?? false) && is_file($this->scriptPath());
    }

    public function stylePath(): string
    {
        return $this->directory.'/styles.css';
    }

    public function scriptPath(): string
    {
        return $this->directory.'/script.js';
    }

    public function previewPath(): string
    {
        return $this->directory.'/preview.svg';
    }

    public function renderer(): AbstractTemplateRenderer
    {
        /** @var AbstractTemplateRenderer $renderer */
        $renderer = app($this->rendererClass);

        return $renderer;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'tags' => $this->tags,
            'supported_sections' => array_map(fn (SectionType $t) => $t->value, $this->supportedSections),
            'default_settings' => $this->defaultSettings,
            'color_schemes' => $this->colorSchemes,
            'capabilities' => $this->capabilities,
        ];
    }
}
