<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Base interface for all Queries (read operations).
 *
 * Queries handle read operations and should never modify state.
 * They return data to be passed to Inertia::render().
 */
interface Query
{
    /**
     * Execute the query and return the result.
     *
     * @return mixed The query result (array, collection, model, etc.)
     */
    public function get(): mixed;
}
