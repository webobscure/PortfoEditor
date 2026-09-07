<?php

declare(strict_types=1);

namespace App\Services\Templates;

use App\Enums\SectionType;
use RuntimeException;

/**
 * Discovers templates from resources/templates and hands them out.
 *
 * Discovery is filesystem-driven so adding a fourth template is a directory
 * plus a renderer class — no registration list to forget to update, and no core
 * code to touch.
 */
final class TemplateRegistry
{
    public const DEFAULT_KEY = 'minimal';

    /** @var array<string, Template>|null */
    private ?array $templates = null;

    public function __construct(private readonly string $root) {}

    /** @return array<string, Template> */
    public function all(): array
    {
        if ($this->templates !== null) {
            return $this->templates;
        }

        $templates = [];

        foreach (glob($this->root.'/*/template.json') ?: [] as $manifestPath) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);

            if (! is_array($manifest) || ! isset($manifest['key'])) {
                continue;
            }

            $template = Template::fromManifest($manifest, dirname($manifestPath));
            $templates[$template->key] = $template;
        }

        ksort($templates);

        return $this->templates = $templates;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /** @return array<int, string> */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function find(string $key): ?Template
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Resolve a key, falling back to the default template.
     *
     * A portfolio must never become unopenable because a template was removed,
     * so an unknown key degrades instead of throwing.
     */
    public function resolve(?string $key): Template
    {
        $template = $key !== null ? $this->find($key) : null;

        if ($template !== null) {
            return $template;
        }

        return $this->find(self::DEFAULT_KEY)
            ?? throw new RuntimeException('No templates are installed.');
    }

    /**
     * Sections this template can display, in the order the portfolio holds them.
     *
     * Unsupported sections are hidden, never deleted: switching templates is
     * reversible and lossless by design.
     *
     * @param  array<int, SectionType>  $types
     * @return array<int, SectionType>
     */
    public function filterSupported(Template $template, array $types): array
    {
        return array_values(array_filter($types, fn (SectionType $t) => $template->supports($t)));
    }
}
