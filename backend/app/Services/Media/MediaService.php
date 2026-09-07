<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\Media;
use App\Models\Portfolio;
use App\Models\User;
use App\Support\MediaReferences;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Upload, addressing and cleanup of user images.
 *
 * Files are never named after anything the user supplied — the stored path is a
 * random ULID plus an extension this application chose — so an upload cannot
 * collide with, overwrite or traverse into anything.
 */
final class MediaService
{
    public function __construct(private readonly ImageProcessor $processor) {}

    public function store(User $user, UploadedFile $file, ?Portfolio $portfolio = null): Media
    {
        $this->assertQuota($user);
        $this->assertMime($file);

        $processed = $this->processor->process($file->getRealPath());

        $disk = (string) config('portfolio.uploads.disk');
        $path = sprintf('portfolios/%d/%s.%s', $user->id, Str::ulid(), $processed['extension']);

        Storage::disk($disk)->put($path, $processed['contents'], [
            'visibility' => 'public',
            'ContentType' => $processed['mime'],
        ]);

        return Media::create([
            'user_id' => $user->id,
            'portfolio_id' => $portfolio?->id,
            'disk' => $disk,
            'path' => $path,
            'mime' => $processed['mime'],
            'size' => strlen($processed['contents']),
            'width' => $processed['width'],
            'height' => $processed['height'],
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
        ]);
    }

    public function delete(Media $media): void
    {
        Storage::disk($media->disk)->delete($media->path);

        $media->delete();
    }

    /**
     * Delete images no section or portfolio still points at.
     *
     * Uploads happen before the section is saved, so anything younger than the
     * grace period is left alone — otherwise the run would delete a file the
     * user is about to reference.
     *
     * @return int number of files removed
     */
    public function pruneUnreferenced(User $user, int $graceHours = 24): int
    {
        $referenced = [];

        $portfolios = $user->portfolios()->with('sections')->get();

        foreach ($portfolios as $portfolio) {
            foreach (MediaReferences::collect($portfolio->sections->pluck('data')->all()) as $id) {
                $referenced[$id] = true;
            }

            foreach (MediaReferences::collect([$portfolio->meta ?? []]) as $id) {
                $referenced[$id] = true;
            }
        }

        $stale = Media::query()
            ->where('user_id', $user->id)
            ->where('created_at', '<', now()->subHours($graceHours))
            ->whereNotIn('id', array_keys($referenced) ?: [0])
            ->get();

        foreach ($stale as $media) {
            $this->delete($media);
        }

        return $stale->count();
    }

    private function assertMime(UploadedFile $file): void
    {
        // getMimeType() sniffs the file contents; the client-supplied header is
        // never consulted.
        $mime = (string) $file->getMimeType();

        if (! in_array($mime, (array) config('portfolio.uploads.mimes'), true)) {
            throw ValidationException::withMessages([
                'file' => 'Upload a JPEG, PNG or WebP image.',
            ]);
        }
    }

    private function assertQuota(User $user): void
    {
        $limit = (int) config('portfolio.uploads.per_user_limit');

        if ($user->media()->count() >= $limit) {
            throw ValidationException::withMessages([
                'file' => 'You have reached the image limit for this account.',
            ]);
        }
    }
}
