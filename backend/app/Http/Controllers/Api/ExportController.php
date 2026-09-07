<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioExportResource;
use App\Jobs\ExportPortfolioJob;
use App\Models\Portfolio;
use App\Models\PortfolioExport;
use App\Services\Export\PortfolioExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export is request/poll/download, even though the build currently runs inline.
 *
 * Shaping the API that way now means switching to the queue is a config flag:
 * the client already polls for a status it might not get immediately.
 */
final class ExportController extends Controller
{
    public function __construct(private readonly PortfolioExportService $exports) {}

    public function store(Request $request, Portfolio $portfolio): JsonResponse
    {
        $this->authorize('export', $portfolio);

        $export = $this->exports->start($portfolio);

        if (config('portfolio.exports.queue')) {
            ExportPortfolioJob::dispatch($export->id);
        } else {
            $export = $this->exports->run($export);
        }

        return PortfolioExportResource::make($export)
            ->response()
            ->setStatusCode(202);
    }

    public function show(Request $request, PortfolioExport $export): PortfolioExportResource
    {
        $this->authorize('view', $export);

        return PortfolioExportResource::make($export);
    }

    public function download(Request $request, PortfolioExport $export): StreamedResponse
    {
        $this->authorize('view', $export);

        abort_unless($export->isDownloadable(), 404);

        $portfolio = $export->portfolio;

        return Storage::disk((string) $export->disk)->download(
            (string) $export->path,
            $this->exports->downloadFilename($portfolio),
        );
    }
}
