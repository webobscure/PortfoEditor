<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUploadRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Models\Portfolio;
use App\Services\Media\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class UploadController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    /**
     * Every image belonging to a portfolio.
     *
     * The editor needs URLs for media ids already stored in section data — it
     * has the ids but not the addresses — so this is fetched once when the
     * editor opens rather than resolved id by id.
     */
    public function index(Request $request, Portfolio $portfolio): AnonymousResourceCollection
    {
        $this->authorize('view', $portfolio);

        return MediaResource::collection(
            $portfolio->media()->latest()->get()
        );
    }

    public function store(StoreUploadRequest $request): JsonResponse
    {
        $portfolio = null;

        if ($request->filled('portfolio_id')) {
            $portfolio = Portfolio::findOrFail($request->integer('portfolio_id'));
            // Attaching an upload to someone else's portfolio is an ownership
            // decision, so it goes through the policy like any other write.
            $this->authorize('update', $portfolio);
        }

        $media = $this->media->store($request->user(), $request->file('file'), $portfolio);

        return MediaResource::make($media)->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        $this->authorize('delete', $media);

        $this->media->delete($media);

        return response()->json(['data' => null]);
    }
}
