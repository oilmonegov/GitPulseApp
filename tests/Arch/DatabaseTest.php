<?php

declare(strict_types=1);

/**
 * Pest Architectural Testing - Database Layer
 *
 * These tests enforce architectural rules for database-related code.
 */
arch('factories should be classes')
    ->expect('Database\Factories')
    ->toBeClasses();

arch('factories should extend Factory')
    ->expect('Database\Factories')
    ->toExtend(\Illuminate\Database\Eloquent\Factories\Factory::class);

arch('factories should have suffix')
    ->expect('Database\Factories')
    ->toHaveSuffix('Factory');

arch('seeders should be classes')
    ->expect('Database\Seeders')
    ->toBeClasses();

arch('seeders should extend Seeder')
    ->expect('Database\Seeders')
    ->toExtend(\Illuminate\Database\Seeder::class);

arch('seeders should have suffix')
    ->expect('Database\Seeders')
    ->toHaveSuffix('Seeder');
