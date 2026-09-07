<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Media> */
final class MediaFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'portfolio_id' => null,
            'disk' => 'public',
            'path' => 'portfolios/1/'.Str::ulid().'.webp',
            'mime' => 'image/webp',
            'size' => 24_000,
            'width' => 1200,
            'height' => 800,
            'original_name' => 'photo.jpg',
        ];
    }
}
