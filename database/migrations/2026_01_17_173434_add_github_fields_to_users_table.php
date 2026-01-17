<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('github_id')->nullable()->unique()->after('id');
            $table->string('github_username')->nullable()->after('github_id');
            $table->text('github_token')->nullable()->after('github_username');
            $table->string('avatar_url')->nullable()->after('github_token');
            $table->json('preferences')->nullable()->after('avatar_url');
            $table->string('timezone')->default('UTC')->after('preferences');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'github_id',
                'github_username',
                'github_token',
                'avatar_url',
                'preferences',
                'timezone',
            ]);
        });
    }
};
