<?php

declare(strict_types=1);

namespace App\Services\Templates;

/**
 * Serves a template's static assets.
 *
 * The stylesheet a browser receives is always base.css followed by the
 * template's own file, concatenated here. Both the preview endpoint and the
 * export pipeline call this method, so the CSS in the editor and the CSS in the
 * downloaded archive are literally the same bytes.
 */
final class TemplateAssets
{
    public function __construct(private readonly string $baseDirectory) {}

    public function stylesheet(Template $template): string
    {
        $base = $this->read($this->baseDirectory.'/base.css');
        $own = $this->read($template->stylePath());

        return rtrim($base)."\n\n".ltrim($own);
    }

    public function script(Template $template): ?string
    {
        if (! $template->hasScript()) {
            return null;
        }

        return $this->read($template->scriptPath());
    }

    private function read(string $path): string
    {
        return is_file($path) ? (string) file_get_contents($path) : '';
    }
}
