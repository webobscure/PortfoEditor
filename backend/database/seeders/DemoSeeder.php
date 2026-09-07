<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Services\Portfolio\PortfolioService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A demo account with one fully populated portfolio per template.
 *
 * The point is to be able to open any template and immediately see it carrying
 * real content — three templates showing the same lorem ipsum would tell you
 * nothing about whether they are actually different designs.
 */
final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@portfoedit.test'],
            ['name' => 'Alex Morgan', 'password' => Hash::make('password')],
        );

        $service = app(PortfolioService::class);

        $portfolios = [
            ['name' => 'Alex Morgan — Product Design', 'preset' => 'designer', 'template_key' => 'minimal'],
            ['name' => 'Jordan Reyes — Engineering', 'preset' => 'developer', 'template_key' => 'developer-dark'],
            ['name' => 'Nina Halvorsen — Photography', 'preset' => 'photographer', 'template_key' => 'editorial'],
        ];

        foreach ($portfolios as $definition) {
            if ($user->portfolios()->where('name', $definition['name'])->exists()) {
                continue;
            }

            $service->create($user, $definition);
        }

        $this->command?->info('Demo account: demo@portfoedit.test / password');
    }
}
