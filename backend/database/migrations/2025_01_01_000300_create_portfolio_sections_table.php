<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();

            $table->string('type', 32);
            $table->unsignedInteger('position');
            $table->boolean('enabled')->default(true);

            // Shape is validated per section type by SectionSchemaRegistry
            // before it ever reaches this column.
            $table->json('data');
            $table->json('settings');

            $table->timestamps();

            // No unique (portfolio_id, type): duplicate sections of one type are
            // a plausible v2 feature and singletons are enforced in the service
            // layer, where the rule can differ per type.
            $table->index(['portfolio_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_sections');
    }
};
