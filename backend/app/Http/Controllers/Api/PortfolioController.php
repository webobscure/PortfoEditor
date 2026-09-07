<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePortfolioRequest;
use App\Http\Requests\UpdatePortfolioRequest;
use App\Http\Resources\PortfolioResource;
use App\Models\Portfolio;
use App\Services\Portfolio\PortfolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PortfolioController extends Controller
{
    public function __construct(private readonly PortfolioService $portfolios) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $portfolios = $request->user()
            ->portfolios()
            ->latest('updated_at')
            ->get();

        return PortfolioResource::collection($portfolios);
    }

    public function store(StorePortfolioRequest $request): JsonResponse
    {
        $portfolio = $this->portfolios->create($request->user(), $request->validated());

        return PortfolioResource::make($portfolio)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Portfolio $portfolio): PortfolioResource
    {
        $this->authorize('view', $portfolio);

        return PortfolioResource::make($portfolio->load('sections'));
    }

    public function update(UpdatePortfolioRequest $request, Portfolio $portfolio): PortfolioResource
    {
        $this->authorize('update', $portfolio);

        $validated = $request->validated();

        if (isset($validated['template_key'])) {
            $this->portfolios->applyTemplate($portfolio, $validated['template_key']);
        }

        if (isset($validated['settings'])) {
            $this->portfolios->updateSettings($portfolio, $validated['settings']);
        }

        $attributes = array_intersect_key($validated, array_flip(['name', 'status']));

        if (isset($validated['meta'])) {
            $attributes['meta'] = array_replace($portfolio->meta ?? [], $validated['meta']);
        }

        if ($attributes !== []) {
            $portfolio->update($attributes);
        }

        return PortfolioResource::make($portfolio->refresh()->load('sections'));
    }

    public function destroy(Request $request, Portfolio $portfolio): JsonResponse
    {
        $this->authorize('delete', $portfolio);

        $portfolio->delete();

        return response()->json(['data' => null], 200);
    }
}
