<?php

declare(strict_types=1);

use App\Support\RenderFixture;

/**
 * Datasets are resolved before the application boots, so the template list is
 * read from the committed golden files rather than from the container.
 *
 * @return array<int, string>
 */
function goldenTemplates(): array
{
    return array_map(
        static fn (string $path): string => basename($path, '.html'),
        glob(__DIR__.'/../Fixtures/golden/*.html') ?: []
    );
}

it('renders each template exactly as the committed golden file', function (string $template) {
    $golden = RenderFixture::path("golden/{$template}.html");

    expect(is_file($golden))->toBeTrue(
        "Missing golden file for [{$template}]. Run: php artisan render:fixtures"
    );

    // The same file is asserted against by the TypeScript renderer's suite, so
    // this is what keeps the preview and the export producing one document.
    expect(RenderFixture::render($template))->toBe(file_get_contents($golden));
})->with(goldenTemplates());

it('escapes user content instead of emitting it as markup', function () {
    $fixture = RenderFixture::portfolio();

    $html = RenderFixture::renderWith('minimal', function (array $sections) {
        foreach ($sections as $index => $section) {
            if ($section['type'] === 'hero') {
                $sections[$index]['content']['name'] = '<script>alert(1)</script>';
                $sections[$index]['content']['intro'] = 'Quote " and \' and <b>bold</b>';
                $sections[$index]['content']['cta_text'] = 'Click';
                $sections[$index]['content']['cta_url'] = 'javascript:alert(2)';
            }
        }

        return $sections;
    });

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->and($html)->not->toContain('javascript:')
        ->and($html)->toContain('&lt;b&gt;bold&lt;/b&gt;')
        ->and($fixture['sections'])->not->toBeEmpty();
});

it('produces an accessible, indexable document', function () {
    $html = RenderFixture::render('minimal');

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('<meta name="viewport"')
        ->and($html)->toContain('<meta property="og:title"')
        ->and($html)->toContain('lang="en"');

    // Every external link opens safely.
    preg_match_all('/<a[^>]*target="_blank"[^>]*>/', $html, $matches);

    expect($matches[0])->not->toBeEmpty();

    foreach ($matches[0] as $anchor) {
        expect($anchor)->toContain('rel="noopener noreferrer"');
    }
});

it('never points an exported page back at the application', function (string $template) {
    $html = RenderFixture::render($template);

    expect($html)->not->toContain('http://localhost')
        ->and($html)->not->toContain('/api/')
        ->and($html)->not->toContain('127.0.0.1');
})->with(goldenTemplates());
