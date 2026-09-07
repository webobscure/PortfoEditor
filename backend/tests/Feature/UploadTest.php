<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->portfolio = Portfolio::factory()->for($this->user)->create();
});

it('stores an upload under a generated name and converts it to webp', function () {
    $response = $this->postJson('/api/uploads', [
        'file' => UploadedFile::fake()->image('holiday snap.jpg', 900, 600),
        'portfolio_id' => $this->portfolio->id,
    ]);

    $response->assertCreated()->assertJsonPath('data.mime', 'image/webp');

    $media = Media::firstOrFail();

    Storage::disk('public')->assertExists($media->path);

    expect($media->path)->toEndWith('.webp')
        // The stored path is never derived from what the user called the file.
        ->and($media->path)->not->toContain('holiday')
        ->and($media->original_name)->toBe('holiday snap.jpg')
        ->and($media->user_id)->toBe($this->user->id);
});

it('rejects a file that is not an image', function () {
    $this->postJson('/api/uploads', [
        'file' => UploadedFile::fake()->create('resume.pdf', 40, 'application/pdf'),
    ])->assertStatus(422)->assertJsonValidationErrors('file');

    expect(Media::count())->toBe(0);
});

it('rejects an oversized image', function () {
    $this->postJson('/api/uploads', [
        'file' => UploadedFile::fake()->image('huge.jpg')->size(20_000),
    ])->assertStatus(422)->assertJsonValidationErrors('file');
});

it('refuses to attach an upload to another account portfolio', function () {
    $other = Portfolio::factory()->create();

    $this->postJson('/api/uploads', [
        'file' => UploadedFile::fake()->image('shot.jpg'),
        'portfolio_id' => $other->id,
    ])->assertForbidden();

    expect(Media::count())->toBe(0);
});

it('refuses to delete another account media', function () {
    $media = Media::factory()->create();

    $this->deleteJson("/api/media/{$media->id}")->assertForbidden();

    expect(Media::find($media->id))->not->toBeNull();
});

it('deletes the file along with the record', function () {
    $this->postJson('/api/uploads', [
        'file' => UploadedFile::fake()->image('shot.jpg'),
        'portfolio_id' => $this->portfolio->id,
    ])->assertCreated();

    $media = Media::firstOrFail();

    $this->deleteJson("/api/media/{$media->id}")->assertOk();

    Storage::disk('public')->assertMissing($media->path);
    expect(Media::find($media->id))->toBeNull();
});

it('lists only the media attached to the requested portfolio', function () {
    Media::factory()->create(['user_id' => $this->user->id, 'portfolio_id' => $this->portfolio->id]);
    Media::factory()->create(['user_id' => $this->user->id, 'portfolio_id' => null]);

    $this->getJson("/api/portfolios/{$this->portfolio->id}/media")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
