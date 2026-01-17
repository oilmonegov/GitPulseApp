<?php

declare(strict_types=1);

namespace App\Integrations\GitHub\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetAuthenticatedUserRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/user';
    }
}
