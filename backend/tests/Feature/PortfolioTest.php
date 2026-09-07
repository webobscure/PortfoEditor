<?php

declare(strict_types=1);

use App\Enums\SectionType;
use App\Models\Portfolio;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('creates a portfolio that already has content in it', function () {
    $response = $this->postJson('/api/portfolios', [
        'name' => 'Alex Morgan',
        'preset' => 'designer',
        'profile' => ['name' => 'Alex Morgan', 'title' => 'Product designer'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.template_key', 'minimal')
        ->assertJsonPath('data.status', 'draft');

    $portfolio = Portfolio::firstOrFail();

    // The product promise: a new portfolio looks like a finished site.
    expect($portfolio->sections)->not->toBeEmpty();

    $hero = $portfolio->sections->firstWhere('type', SectionType::Hero);
    expect($hero->data['name'])->toBe('Alex Morgan')
        ->and($hero->data['title'])->toBe('Product designer')
        // Seeded copy is flagged so the editor can label it as a sample.
        ->and($hero->settings['placeholder'])->toBeTrue();
});

it('gives every portfolio a unique slug', function () {
    $this->postJson('/api/portfolios', ['name' => 'Alex Morgan'])->assertCreated();
    $this->postJson('/api/portfolios', ['name' => 'Alex Morgan'])->assertCreated();

    expect(Portfolio::pluck('slug')->unique())->toHaveCount(2);
});

it('lists only the signed-in user portfolios', function () {
    Portfolio::factory()->for($this->user)->create(['name' => 'Mine']);
    Portfolio::factory()->create(['name' => 'Theirs']);

    $this->getJson('/api/portfolios')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Mine');
});

it('refuses to read, change or delete another account portfolio', function () {
    $other = Portfolio::factory()->create();

    $this->getJson("/api/portfolios/{$other->id}")->assertForbidden();
    $this->patchJson("/api/portfolios/{$other->id}", ['name' => 'Hijacked'])->assertForbidden();
    $this->deleteJson("/api/portfolios/{$other->id}")->assertForbidden();
    $this->postJson("/api/portfolios/{$other->id}/export")->assertForbidden();
    $this->getJson("/api/portfolios/{$other->id}/preview")->assertForbidden();

    expect($other->fresh()->name)->not->toBe('Hijacked');
});

it('stores only the settings the user changed', function () {
    $portfolio = Portfolio::factory()->for($this->user)->create(['template_key' => 'minimal']);

    $this->patchJson("/api/portfolios/{$portfolio->id}", [
        'settings' => ['colors' => ['accent' => '#ff0055']],
    ])->assertOk()
        ->assertJsonPath('data.settings.colors.accent', '#ff0055')
        // The rest still comes from the template.
        ->assertJsonPath('data.effective_settings.typography.heading_font', 'manrope');

    expect($portfolio->fresh()->settings)->toBe(['colors' => ['accent' => '#ff0055']]);
});

it('rejects settings outside the schema', function () {
    $portfolio = Portfolio::factory()->for($this->user)->create();

    $this->patchJson("/api/portfolios/{$portfolio->id}", [
        'settings' => ['colors' => ['accent' => 'red']],
    ])->assertStatus(422)->assertJsonValidationErrors('settings.colors.accent');

    $this->patchJson("/api/portfolios/{$portfolio->id}", [
        'settings' => ['typography' => ['heading_font' => 'comic-sans']],
    ])->assertStatus(422);
});

it('keeps every section when the template changes', function () {
    $this->postJson('/api/portfolios', ['name' => 'Nina', 'preset' => 'photographer'])
        ->assertCreated();

    $portfolio = Portfolio::firstOrFail();
    $before = $portfolio->sections()->pluck('type')->sort()->values();

    // developer-dark does not support the services section the photographer
    // preset seeds — the row must survive the switch regardless.
    $this->patchJson("/api/portfolios/{$portfolio->id}", ['template_key' => 'developer-dark'])
        ->assertOk()
        ->assertJsonPath('data.template_key', 'developer-dark');

    $after = $portfolio->fresh()->sections()->pluck('type')->sort()->values();

    expect($after->all())->toBe($before->all())
        ->and($before)->toContain(SectionType::Services);
});

it('deletes a portfolio', function () {
    $portfolio = Portfolio::factory()->for($this->user)->create();

    $this->deleteJson("/api/portfolios/{$portfolio->id}")->assertOk();

    expect(Portfolio::find($portfolio->id))->toBeNull();
});
