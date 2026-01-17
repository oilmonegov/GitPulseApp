<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Base interface for all Actions (commands/mutations).
 *
 * Actions handle write operations: create, update, delete.
 * They should be single-purpose and focused on one mutation.
 */
interface Action
{
    /**
     * Execute the action.
     *
     * @return mixed The result of the action (can be a model, boolean, or void)
     */
    public function execute(): mixed;
}
