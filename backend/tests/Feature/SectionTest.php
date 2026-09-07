<?php

declare(strict_types=1);

use App\Enums\SectionType;
use App\Models\Portfolio;
use App\Models\PortfolioSection;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->portfolio = Portfolio::factory()->for($this->user)->create();
});

it('adds a section with valid empty defaults', function () {
    $response = $this->postJson("/api/portfolios/{$this->portfolio->id}/sections", [
        'type' => 'projects',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'projects')
        ->assertJsonPath('data.enabled', true)
        ->assertJsonPath('data.content.heading', 'Selected work');
});

it('refuses a second copy of a singleton section', function () {
    $this->postJson("/api/portfolios/{$this->portfolio->id}/sections", ['type' => 'hero'])
        ->assertCreated();

    $this->postJson("/api/portfolios/{$this->portfolio->id}/sections", ['type' => 'hero'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('type');
});

it('rejects an unknown section type', function () {
    $this->postJson("/api/portfolios/{$this->portfolio->id}/sections", ['type' => 'pricing'])
        ->assertStatus(422);
});

it('merges a partial payload instead of replacing the section', function () {
    $section = PortfolioSection::factory()
        ->for($this->portfolio)
        ->ofType(SectionType::Hero, ['name' => 'Alex', 'title' => 'Designer'])
        ->create();

    $this->patchJson("/api/portfolios/{$this->portfolio->id}/sections/{$section->id}", [
        'content' => ['title' => 'Product designer'],
    ])->assertOk()
        ->assertJsonPath('data.content.title', 'Product designer')
        ->assertJsonPath('data.content.name', 'Alex');
});

it('clears the placeholder flag on the first real edit', function () {
    $section = PortfolioSection::factory()
        ->for($this->portfolio)
        ->ofType(SectionType::Hero)
        ->create(['settings' => ['placeholder' => true]]);

    $this->patchJson("/api/portfolios/{$this->portfolio->id}/sections/{$section->id}", [
        'content' => ['name' => 'Alex'],
    ])->assertOk()->assertJsonPath('data.is_placeholder', false);
});

it('drops keys the section schema does not describe', function () {
    $section = PortfolioSection::factory()
        ->for($this->portfolio)
        ->ofType(SectionType::Hero)
        ->create();

    $this->patchJson("/api/portfolios/{$this->portfolio->id}/sections/{$section->id}", [
        'content' => ['name' => 'Alex', 'evil' => '<script>alert(1)</script>'],
    ])->assertOk();

    expect($section->fresh()->data)->not->toHaveKey('evil');
});

it('rejects a javascript: url', function () {
    $section = PortfolioSection::factory()
        ->for($this->portfolio)
        ->ofType(SectionType::Hero)
        ->create();

    $this->patchJson("/api/portfolios/{$this->portfolio->id}/sections/{$section->id}", [
        'content' => ['cta_text' => 'Click', 'cta_url' => 'javascript:alert(1)'],
    ])->assertStatus(422)->assertJsonValidationErrors('content.cta_url');
});

it('reorders sections', function () {
    $types = [SectionType::Hero, SectionType::About, SectionType::Projects];
    $sections = collect($types)->map(fn (SectionType $type, int $index) => PortfolioSection::factory()
        ->for($this->portfolio)
        ->ofType($type)
        ->create(['position' => $index]));

    $reversed = $sections->pluck('id')->reverse()->values()->all();

    $this->postJson("/api/portfolios/{$this->portfolio->id}/sections/reorder", [
        'section_ids' => $reversed,
    ])->assertOk();

    expect($this->portfolio->sections()->pluck('id')->all())->toBe($reversed);
});

it('ignores section ids from another portfolio when reordering', function () {
    $mine = PortfolioSection::factory()->for($this->portfolio)->ofType(SectionType::Hero)->create();
    $theirs = PortfolioSection::factory()->ofType(SectionType::Hero)->create();

    $this->postJson("/api/portfolios/{$this->portfolio->id}/sections/reorder", [
        'section_ids' => [$theirs->id, $mine->id],
    ])->assertOk();

    expect($theirs->fresh()->position)->toBe(0)
        ->and($this->portfolio->sections()->pluck('id')->all())->toBe([$mine->id]);
});

it('refuses to touch a section through the wrong portfolio', function () {
    $other = Portfolio::factory()->for($this->user)->create();
    $section = PortfolioSection::factory()->for($other)->ofType(SectionType::Hero)->create();

    $this->patchJson("/api/portfolios/{$this->portfolio->id}/sections/{$section->id}", [
        'content' => ['name' => 'Nope'],
    ])->assertNotFound();
});

it('refuses section writes on another account portfolio', function () {
    $other = Portfolio::factory()->create();
    $section = PortfolioSection::factory()->for($other)->ofType(SectionType::Hero)->create();

    $this->postJson("/api/portfolios/{$other->id}/sections", ['type' => 'about'])->assertForbidden();
    $this->patchJson("/api/portfolios/{$other->id}/sections/{$section->id}", [
        'content' => ['name' => 'Nope'],
    ])->assertForbidden();
    $this->deleteJson("/api/portfolios/{$other->id}/sections/{$section->id}")->assertForbidden();
});

it('deletes a section', function () {
    $section = PortfolioSection::factory()->for($this->portfolio)->ofType(SectionType::About)->create();

    $this->deleteJson("/api/portfolios/{$this->portfolio->id}/sections/{$section->id}")->assertOk();

    expect(PortfolioSection::find($section->id))->toBeNull();
});
