<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('github_id', 20)->index();
            $table->string('name', 100);
            $table->string('full_name', 200);
            $table->string('description', 500)->nullable();
            $table->string('default_branch', 50)->default('main');
            $table->string('language', 50)->nullable();
            $table->string('webhook_id', 20)->nullable();
            $table->string('webhook_secret', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_private')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'github_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
