<?php

declare(strict_types=1);

use App\Models\Portfolio;
use App\Models\User;

it('publishes the template catalogue without a session', function () {
    $response = $this->getJson('/api/templates');

    $response->assertOk();

    $keys = collect($response->json('data'))->pluck('key');

    expect($keys)->toContain('minimal', 'developer-dark', 'editorial');

    foreach ($response->json('data') as $template) {
        expect($template['supported_sections'])->not->toBeEmpty()
            ->and($template['color_schemes'])->not->toBeEmpty();
    }
});

it('serves a stylesheet that starts with the shared base', function () {
    $response = $this->get('/api/templates/minimal/styles.css');

    $response->assertOk()->assertHeader('content-type', 'text/css; charset=UTF-8');

    expect($response->getContent())->toContain('.pf-shell')
        ->and($response->getContent())->toContain('.pf-t-minimal');
});

it('renders a live demo page for the gallery', function () {
    $response = $this->get('/api/templates/editorial/demo');

    $response->assertOk();

    expect($response->getContent())->toContain('pf-t-editorial')
        ->and($response->getContent())->toContain('<h1');
});

it('404s an unknown template', function () {
    $this->getJson('/api/templates/does-not-exist')->assertNotFound();
    $this->get('/api/templates/does-not-exist/styles.css')->assertNotFound();
});

it('publishes theme options the editor builds its controls from', function () {
    $data = $this->getJson('/api/theme/options')->assertOk()->json('data');

    expect($data['fonts'])->toHaveCount(12)
        ->and($data['section_types'])->toHaveCount(10)
        ->and($data['button_styles'])->toBe(['rounded', 'square', 'pill']);
});

it('serves only fonts listed in the manifest', function () {
    $face = $this->getJson('/api/fonts')->assertOk()->json('data.faces.0.file');

    $this->get('/api/fonts/'.$face)->assertOk()->assertHeader('content-type', 'font/woff2');

    $this->get('/api/fonts/not-a-real-font.woff2')->assertNotFound();
});

it('renders a preview of the caller own portfolio only', function () {
    $user = User::factory()->create();
    $portfolio = Portfolio::factory()->for($user)->create();

    $this->getJson("/api/portfolios/{$portfolio->id}/preview")->assertUnauthorized();

    $this->actingAs($user)
        ->get("/api/portfolios/{$portfolio->id}/preview?template=editorial")
        ->assertOk()
        ->assertSee('pf-t-editorial', false);
});
