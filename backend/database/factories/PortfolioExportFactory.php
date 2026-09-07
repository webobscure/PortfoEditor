<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExportStatus;
use App\Models\Portfolio;
use App\Models\PortfolioExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PortfolioExport> */
final class PortfolioExportFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'user_id' => User::factory(),
            'status' => ExportStatus::Queued,
        ];
    }
}
