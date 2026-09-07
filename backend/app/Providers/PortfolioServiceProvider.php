<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Templates\TemplateAssets;
use App\Services\Templates\TemplateRegistry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

final class PortfolioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TemplateRegistry::class, fn () => new TemplateRegistry(
            (string) config('portfolio.templates.path')
        ));

        $this->app->singleton(TemplateAssets::class, fn () => new TemplateAssets(
            (string) config('portfolio.templates.base_path')
        ));
    }

    public function boot(): void
    {
        // Section payloads are JSON columns, so anything the schema does not
        // describe must not survive validation. Without this, an unlisted key
        // in a nested array would pass straight through to the database.
        Validator::excludeUnvalidatedArrayKeys();
    }
}
