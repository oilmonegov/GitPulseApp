<?php

declare(strict_types=1);

namespace App\Integrations\GitHub\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class CreateWebhookRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $owner,
        private readonly string $repo,
        private readonly string $webhookUrl,
        private readonly string $secret,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/repos/{$this->owner}/{$this->repo}/hooks";
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'name' => 'web',
            'active' => true,
            'events' => ['push', 'pull_request'],
            'config' => [
                'url' => $this->webhookUrl,
                'content_type' => 'json',
                'secret' => $this->secret,
                'insecure_ssl' => '0',
            ],
        ];
    }
}
