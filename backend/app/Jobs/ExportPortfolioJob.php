<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ExportStatus;
use App\Models\PortfolioExport;
use App\Services\Export\PortfolioExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * The asynchronous path for exports.
 *
 * It exists so the switch is a config flag rather than a refactor: the job does
 * nothing the controller does not already do, it just does it on a worker.
 */
final class ExportPortfolioJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(private readonly int $exportId) {}

    public function handle(PortfolioExportService $exports): void
    {
        $export = PortfolioExport::find($this->exportId);

        if ($export === null || $export->status === ExportStatus::Completed) {
            return;
        }

        $exports->run($export);
    }
}
