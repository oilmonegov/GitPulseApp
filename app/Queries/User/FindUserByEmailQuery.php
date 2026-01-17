<?php

declare(strict_types=1);

namespace App\Queries\User;

use App\Contracts\Query;
use App\Models\User;

/**
 * Finds a user by their email address.
 */
final class FindUserByEmailQuery implements Query
{
    public function __construct(
        private readonly string $email,
    ) {}

    public function get(): ?User
    {
        return User::where('email', $this->email)->first();
    }
}
