<?php

declare(strict_types=1);

use App\Enums\SectionType;
use App\Models\Portfolio;
use App\Models\PortfolioExport;
use App\Models\PortfolioSection;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');

    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

/** @return array<int, string> */
function entriesOf(PortfolioExport $export): array
{
    $temp = tempnam(sys_get_temp_dir(), 'pf-test-');
    file_put_contents($temp, Storage::disk($export->disk)->get($export->path));

    $zip = new ZipArchive;
    $zip->open($temp);

    $entries = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entries[] = $zip->getNameIndex($i);
    }

    $zip->close();
    @unlink($temp);

    return $entries;
}

function contentsOf(PortfolioExport $export, string $entry): string
{
    $temp = tempnam(sys_get_temp_dir(), 'pf-test-');
    file_put_contents($temp, Storage::disk($export->disk)->get($export->path));

    $zip = new ZipArchive;
    $zip->open($temp);
    $contents = (string) $zip->getFromName($entry);
    $zip->close();
    @unlink($temp);

    return $contents;
}

it('builds a self-contained archive', function () {
    // The name is supplied through the create flow's profile answers rather
    // than left to the preset, so the assertion below checks that what the
    // user typed reaches the archive — not that the sample copy happens to
    // carry the same name.
    $this->postJson('/api/portfolios', [
        'name' => 'Алиса Морозова',
        'preset' => 'designer',
        'profile' => ['name' => 'Алиса Морозова'],
    ])->assertCreated();

    $portfolio = Portfolio::firstOrFail();

    $response = $this->postJson("/api/portfolios/{$portfolio->id}/export");

    $response->assertStatus(202)->assertJsonPath('data.status', 'completed');

    $export = PortfolioExport::firstOrFail();
    $entries = entriesOf($export);

    expect($entries)->toContain('index.html')
        ->and($entries)->toContain('assets/css/styles.css')
        // The template ships no JavaScript, so none is invented for it.
        ->and($entries)->not->toContain('assets/js/app.js')
        ->and(collect($entries)->filter(fn ($e) => str_starts_with($e, 'assets/fonts/')))
        ->not->toBeEmpty();

    $html = contentsOf($export, 'index.html');

    expect($html)->toStartWith('<!DOCTYPE html>')
        ->and($html)->toContain('Алиса Морозова')
        ->and($html)->toContain('href="assets/css/styles.css"')
        ->and($html)->not->toContain('http://localhost')
        ->and($html)->not->toContain('/api/');

    $css = contentsOf($export, 'assets/css/styles.css');

    // base.css is prepended to the template's own stylesheet, exactly as the
    // preview endpoint serves it.
    expect($css)->toContain('.pf-shell')->and($css)->toContain('.pf-t-minimal');
});

it('bundles uploaded images and addresses them relatively', function () {
    $portfolio = Portfolio::factory()->for($this->user)->create(['template_key' => 'minimal']);

    $upload = $this->postJson('/api/uploads', [
        'file' => UploadedFile::fake()->image('shot.jpg', 800, 600),
        'portfolio_id' => $portfolio->id,
    ])->assertCreated();

    $mediaId = $upload->json('data.id');

    PortfolioSection::factory()->for($portfolio)->ofType(SectionType::Hero, [
        'name' => 'Alex Morgan',
        'title' => 'Designer',
        'photo_media_id' => $mediaId,
    ])->create();

    $this->postJson("/api/portfolios/{$portfolio->id}/export")->assertStatus(202);

    $export = PortfolioExport::firstOrFail();
    $entries = entriesOf($export);
    $images = collect($entries)->filter(fn ($e) => str_starts_with($e, 'assets/images/'));

    expect($images)->toHaveCount(1);

    $html = contentsOf($export, 'index.html');

    expect($html)->toContain('src="assets/images/')
        ->and($html)->not->toContain('/storage/');
});

it('downloads a completed export and refuses another account one', function () {
    $portfolio = Portfolio::factory()->for($this->user)->create();
    PortfolioSection::factory()->for($portfolio)->ofType(SectionType::Hero, ['name' => 'Alex'])->create();

    $this->postJson("/api/portfolios/{$portfolio->id}/export")->assertStatus(202);

    $export = PortfolioExport::firstOrFail();

    $this->get("/api/exports/{$export->id}/download")
        ->assertOk()
        ->assertHeader('content-disposition');

    $stranger = User::factory()->create();
    $this->actingAs($stranger);

    $this->getJson("/api/exports/{$export->id}")->assertForbidden();
    $this->get("/api/exports/{$export->id}/download")->assertForbidden();
});

it('leaves out sections the template cannot display', function () {
    $portfolio = Portfolio::factory()->for($this->user)->create(['template_key' => 'developer-dark']);

    PortfolioSection::factory()->for($portfolio)
        ->ofType(SectionType::Hero, ['name' => 'Jordan Reyes', 'title' => 'Engineer'])
        ->create(['position' => 0]);

    PortfolioSection::factory()->for($portfolio)
        ->ofType(SectionType::Services, ['heading' => 'Consulting rates', 'items' => [
            ['title' => 'Advisory', 'description' => 'Monthly retainer', 'price' => '€2000'],
        ]])
        ->create(['position' => 1]);

    $this->postJson("/api/portfolios/{$portfolio->id}/export")->assertStatus(202);

    $html = contentsOf(PortfolioExport::firstOrFail(), 'index.html');

    expect($html)->toContain('Jordan Reyes')
        ->and($html)->not->toContain('Consulting rates');

    // Hidden, never deleted.
    expect($portfolio->sections()->where('type', 'services')->exists())->toBeTrue();
});

it('skips a disabled section', function () {
    $portfolio = Portfolio::factory()->for($this->user)->create();

    PortfolioSection::factory()->for($portfolio)
        ->ofType(SectionType::Hero, ['name' => 'Alex Morgan'])
        ->create(['position' => 0]);

    PortfolioSection::factory()->for($portfolio)
        ->ofType(SectionType::About, ['heading' => 'About', 'body' => 'Hidden paragraph.'])
        ->create(['position' => 1, 'enabled' => false]);

    $this->postJson("/api/portfolios/{$portfolio->id}/export")->assertStatus(202);

    expect(contentsOf(PortfolioExport::firstOrFail(), 'index.html'))
        ->not->toContain('Hidden paragraph.');
});
