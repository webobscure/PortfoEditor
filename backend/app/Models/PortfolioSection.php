<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SectionType;
use Database\Factories\PortfolioSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $portfolio_id
 * @property SectionType $type
 * @property int $position
 * @property bool $enabled
 * @property array<string, mixed> $data
 * @property array<string, mixed> $settings
 */
class PortfolioSection extends Model
{
    /** @use HasFactory<PortfolioSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'position',
        'enabled',
        'data',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
            'enabled' => 'boolean',
            'position' => 'integer',
            'data' => 'array',
            'settings' => 'array',
        ];
    }

    /** @return BelongsTo<Portfolio, $this> */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
