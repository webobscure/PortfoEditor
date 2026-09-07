<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SectionType;
use App\Models\Portfolio;
use App\Models\PortfolioSection;
use App\Services\Portfolio\SectionSchemaRegistry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PortfolioSection> */
final class PortfolioSectionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'type' => SectionType::Hero,
            'position' => 0,
            'enabled' => true,
            'data' => app(SectionSchemaRegistry::class)->defaults(SectionType::Hero),
            'settings' => [],
        ];
    }

    /** @param array<string, mixed> $data */
    public function ofType(SectionType $type, array $data = []): self
    {
        return $this->state(fn () => [
            'type' => $type,
            'data' => app(SectionSchemaRegistry::class)->normalise($type, $data),
        ]);
    }
}
