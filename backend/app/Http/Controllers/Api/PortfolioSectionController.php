<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\SectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderSectionsRequest;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Http\Resources\PortfolioSectionResource;
use App\Models\Portfolio;
use App\Models\PortfolioSection;
use App\Services\Portfolio\SectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PortfolioSectionController extends Controller
{
    public function __construct(private readonly SectionService $sections) {}

    public function index(Request $request, Portfolio $portfolio): AnonymousResourceCollection
    {
        $this->authorize('view', $portfolio);

        return PortfolioSectionResource::collection($portfolio->sections()->get());
    }

    public function store(StoreSectionRequest $request, Portfolio $portfolio): JsonResponse
    {
        $this->authorize('update', $portfolio);

        $section = $this->sections->create(
            $portfolio,
            SectionType::from($request->string('type')->toString()),
            $request->has('position') ? $request->integer('position') : null,
        );

        return PortfolioSectionResource::make($section)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateSectionRequest $request, Portfolio $portfolio, PortfolioSection $section): PortfolioSectionResource
    {
        $this->authorize('update', $portfolio);
        $this->assertBelongs($portfolio, $section);

        return PortfolioSectionResource::make(
            $this->sections->update($section, $request->validated())
        );
    }

    public function destroy(Request $request, Portfolio $portfolio, PortfolioSection $section): JsonResponse
    {
        $this->authorize('update', $portfolio);
        $this->assertBelongs($portfolio, $section);

        $this->sections->delete($section);

        return response()->json(['data' => null]);
    }

    public function reorder(ReorderSectionsRequest $request, Portfolio $portfolio): AnonymousResourceCollection
    {
        $this->authorize('update', $portfolio);

        $sections = $this->sections->reorder($portfolio, $request->validated()['section_ids']);

        return PortfolioSectionResource::collection($sections);
    }

    /**
     * Nested route binding does not imply ownership on its own, so the parent
     * link is checked explicitly.
     */
    private function assertBelongs(Portfolio $portfolio, PortfolioSection $section): void
    {
        abort_unless($section->portfolio_id === $portfolio->id, 404);
    }
}
