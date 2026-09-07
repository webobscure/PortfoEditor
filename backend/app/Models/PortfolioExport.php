<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExportStatus;
use Database\Factories\PortfolioExportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioExport extends Model
{
    /** @use HasFactory<PortfolioExportFactory> */
    use HasFactory;

    protected $fillable = [
        'portfolio_id',
        'user_id',
        'status',
        'disk',
        'path',
        'size',
        'error',
        'completed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExportStatus::class,
            'size' => 'integer',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Portfolio, $this> */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function isDownloadable(): bool
    {
        return $this->status === ExportStatus::Completed
            && $this->path !== null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
