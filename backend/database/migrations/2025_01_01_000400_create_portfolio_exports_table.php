<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Exists from day one even though MVP export runs synchronously. The
        // row is what makes moving to a queue a no-op: the API already returns
        // an export record and the client already polls it.
        Schema::create('portfolio_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('status', 16)->default('queued');
            $table->string('disk', 32)->nullable();
            $table->string('path', 255)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->text('error')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['portfolio_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_exports');
    }
};
