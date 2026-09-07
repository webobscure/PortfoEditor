<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PortfolioStatus;
use Database\Factories\PortfolioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property string $template_key
 * @property array<string, mixed> $settings
 * @property array<string, mixed> $meta
 */
class Portfolio extends Model
{
    /** @use HasFactory<PortfolioFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'template_key',
        'status',
        'preset',
        'settings',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'meta' => 'array',
            'status' => PortfolioStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<PortfolioSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(PortfolioSection::class)->orderBy('position');
    }

    /** @return HasMany<Media, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    /** @return HasMany<PortfolioExport, $this> */
    public function exports(): HasMany
    {
        return $this->hasMany(PortfolioExport::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
