<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ExportStatus;
use App\Models\PortfolioExport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PortfolioExport */
final class PortfolioExportResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'portfolio_id' => $this->portfolio_id,
            'status' => $this->status->value,
            'size' => $this->size,
            'error' => $this->status === ExportStatus::Failed ? 'Не удалось собрать архив.' : null,
            'download_url' => $this->isDownloadable() ? route('exports.download', $this->id) : null,
            'created_at' => $this->created_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
        ];
    }
}
