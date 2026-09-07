<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Enums\ExportStatus;
use App\Models\Media;
use App\Models\Portfolio;
use App\Models\PortfolioExport;
use App\Services\Templates\RenderContext;
use App\Services\Templates\TemplateAssets;
use App\Services\Templates\TemplateRegistry;
use App\Services\Templates\ThemeCompiler;
use App\Services\Templates\ThemeSchema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Produces a self-contained static site as a ZIP.
 *
 * Archive layout:
 *
 *   index.html
 *   assets/css/styles.css
 *   assets/js/app.js        (only when the template ships one)
 *   assets/fonts/*.woff2
 *   assets/images/*
 *
 * The result opens from file://, hosts on any static server, and makes zero
 * network requests: fonts are bundled, images are rewritten to relative paths,
 * and nothing points back at this application.
 *
 * The method below runs synchronously today because a portfolio is a handful of
 * kilobytes plus its images. Nothing about it assumes that: ExportPortfolioJob
 * calls the same method, so moving to the queue is a routing decision.
 */
final class PortfolioExportService
{
    private const IMAGE_DIR = 'assets/images';

    private const FONT_DIR = 'assets/fonts';

    public function __construct(
        private readonly PortfolioRenderer $renderer,
        private readonly TemplateRegistry $templates,
        private readonly TemplateAssets $assets,
        private readonly AssetCollector $collector,
        private readonly ThemeSchema $schema,
        private readonly ThemeCompiler $compiler,
    ) {}

    /** Creates the pending record the API hands back immediately. */
    public function start(Portfolio $portfolio): PortfolioExport
    {
        return $portfolio->exports()->create([
            'user_id' => $portfolio->user_id,
            'status' => ExportStatus::Queued,
        ]);
    }

    public function run(PortfolioExport $export): PortfolioExport
    {
        $export->update(['status' => ExportStatus::Processing]);

        $builder = new ZipBuilder;

        try {
            $portfolio = $export->portfolio()->with('sections')->firstOrFail();
            $path = $this->build($portfolio, $builder);

            $disk = (string) config('portfolio.exports.disk');
            $target = sprintf('exports/%d/%s.zip', $portfolio->id, Str::uuid());

            Storage::disk($disk)->put($target, (string) file_get_contents($path));
            $size = (int) filesize($path);
            @unlink($path);

            $export->update([
                'status' => ExportStatus::Completed,
                'disk' => $disk,
                'path' => $target,
                'size' => $size,
                'error' => null,
                'completed_at' => now(),
                'expires_at' => now()->addHours((int) config('portfolio.exports.ttl_hours')),
            ]);
        } catch (Throwable $e) {
            $builder->discard();

            $export->update([
                'status' => ExportStatus::Failed,
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            report($e);
        }

        return $export->refresh();
    }

    /** Renders the site and fills the archive. Returns the temp zip path. */
    private function build(Portfolio $portfolio, ZipBuilder $builder): string
    {
        $template = $this->templates->resolve($portfolio->template_key);
        $images = $this->collector->images($portfolio);

        $context = $this->renderer->context(
            portfolio: $portfolio,
            mode: RenderContext::MODE_EXPORT,
            // Inside the archive an image is a relative path, never a URL that
            // points back at this application.
            imageUrl: fn (Media $media): string => self::IMAGE_DIR.'/'.$media->exportFilename(),
            styleHref: 'assets/css/styles.css',
            fontBaseUrl: self::FONT_DIR,
            scriptSrc: 'assets/js/app.js',
        );

        $builder->addString('index.html', $this->renderer->render($context));
        $builder->addString('assets/css/styles.css', $this->assets->stylesheet($template));

        $script = $this->assets->script($template);

        if ($script !== null) {
            $builder->addString('assets/js/app.js', $script);
        }

        $settings = $this->schema->merge($portfolio->settings ?? [], $template->defaultSettings);

        foreach ($this->collector->fonts($this->compiler->fontSlugs($settings)) as $font) {
            $builder->addFile(self::FONT_DIR.'/'.$font['filename'], $font['path']);
        }

        foreach ($images as $image) {
            $builder->addString(self::IMAGE_DIR.'/'.$image['filename'], $image['contents']);
        }

        $builder->addString('README.txt', $this->readme($portfolio));

        return $builder->finish();
    }

    private function readme(Portfolio $portfolio): string
    {
        return implode("\n", [
            $portfolio->name,
            str_repeat('=', max(3, mb_strlen($portfolio->name))),
            '',
            'A static website. No build step, no dependencies, no server code.',
            '',
            'To publish it, upload the contents of this folder to any static host',
            '(Netlify, GitHub Pages, Cloudflare Pages, S3, nginx). To preview it',
            'locally, open index.html in a browser.',
            '',
            'Files',
            '  index.html            the page',
            '  assets/css/           stylesheet',
            '  assets/fonts/         the two webfonts this design uses',
            '  assets/images/        your uploaded images',
            '',
        ]);
    }

    public function downloadFilename(Portfolio $portfolio): string
    {
        $slug = Str::slug($portfolio->name) ?: 'portfolio';

        return $slug.'.zip';
    }
}
