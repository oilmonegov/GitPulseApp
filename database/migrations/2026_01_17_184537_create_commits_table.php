<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sha', 40)->unique();
            $table->text('message');
            $table->string('author_name', 100);
            $table->string('author_email', 255);
            $table->timestamp('committed_at');
            $table->unsignedInteger('additions')->default(0);
            $table->unsignedInteger('deletions')->default(0);
            $table->unsignedInteger('files_changed')->default(0);
            $table->json('files')->nullable();
            $table->string('commit_type', 20)->default('other');
            $table->string('scope', 50)->nullable();
            $table->decimal('impact_score', 5, 2)->default(0);
            $table->json('external_refs')->nullable();
            $table->boolean('is_merge')->default(false);
            $table->string('url', 512)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'committed_at']);
            $table->index(['repository_id', 'committed_at']);
            $table->index('commit_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commits');
    }
};
