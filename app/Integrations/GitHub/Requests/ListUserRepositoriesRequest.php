<?php

declare(strict_types=1);

namespace App\Integrations\GitHub\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ListUserRepositoriesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $perPage = 100,
        private readonly int $page = 1,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/user/repos';
    }

    protected function defaultQuery(): array
    {
        return [
            'per_page' => $this->perPage,
            'page' => $this->page,
            'sort' => 'pushed',
            'direction' => 'desc',
        ];
    }
}
