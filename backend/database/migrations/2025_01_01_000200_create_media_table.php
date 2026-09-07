<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            // Denormalised owner so an authorization check never needs a join.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('portfolio_id')->nullable()->constrained()->cascadeOnDelete();

            // Stored per row so a later move to S3/R2 leaves existing rows
            // serving from their original disk instead of 404ing.
            $table->string('disk', 32);
            $table->string('path', 255);

            $table->string('mime', 64);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            // Display only. Never used to build a filesystem path.
            $table->string('original_name', 255);

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('portfolio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
