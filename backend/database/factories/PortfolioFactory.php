<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PortfolioStatus;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Portfolio> */
final class PortfolioFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'template_key' => 'minimal',
            'status' => PortfolioStatus::Draft,
            'preset' => 'designer',
            'settings' => [],
            'meta' => [],
        ];
    }

    public function template(string $key): self
    {
        return $this->state(fn () => ['template_key' => $key]);
    }
}
