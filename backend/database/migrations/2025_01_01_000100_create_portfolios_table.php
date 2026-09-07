<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);

            // Globally unique, not scoped to the user: the slug is reserved now
            // so that slug.example.com publishing can ship without a migration.
            $table->string('slug', 80)->unique();

            // Plain string, not a foreign key. Templates are code-defined, so a
            // templates table would only let the database drift from what the
            // renderer can actually produce.
            $table->string('template_key', 64);

            $table->string('status', 16)->default('draft');
            $table->string('preset', 32)->nullable();

            // Theme settings (colours, typography, layout, buttons) and SEO meta.
            // json() maps to jsonb on PostgreSQL.
            $table->json('settings');
            $table->json('meta');

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
