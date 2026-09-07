<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Portfolio\PortfolioPresets;
use App\Services\Portfolio\SectionSchemaRegistry;
use App\Support\RenderFixture;
use Illuminate\Console\Command;

/**
 * Regenerates the render-parity fixtures.
 *
 * Run this after deliberately changing a renderer, then review the diff: the
 * golden files are the contract that keeps the PHP exporter and the TypeScript
 * preview producing the same document.
 */
final class BuildRenderFixtures extends Command
{
    protected $signature = 'render:fixtures';

    protected $description = 'Rebuild the golden HTML fixtures shared by the PHP and TypeScript render tests';

    public function handle(PortfolioPresets $presets, SectionSchemaRegistry $schemas): int
    {
        $sections = [];

        foreach ($presets->sections('designer') as $section) {
            $sections[] = [
                'type' => $section['type']->value,
                'content' => $schemas->normalise($section['type'], $section['data']),
            ];
        }

        $portfolio = [
            'name' => 'Alex Morgan',
            'slug' => 'alex-morgan',
            'meta' => ['title' => '', 'description' => ''],
            'settings' => ['colors' => ['accent' => '#2f5bea']],
            'sections' => $sections,
        ];

        $this->write('portfolio.json', json_encode($portfolio, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
        $this->write('fonts.json', json_encode(RenderFixture::fonts(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

        foreach (RenderFixture::templateKeys() as $key) {
            $this->write('golden/'.$key.'.html', RenderFixture::render($key));
        }

        return self::SUCCESS;
    }

    private function write(string $name, string $contents): void
    {
        $path = RenderFixture::path($name);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $contents);
        $this->line('  wrote '.str_replace(base_path().'/', '', $path));
    }
}
