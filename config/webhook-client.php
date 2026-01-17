<?php

declare(strict_types=1);

return [
    'configs' => [
        [
            /*
             * GitHub webhook configuration for receiving push events,
             * pull requests, and other Git activity.
             */
            'name' => 'github',

            /*
             * GitHub webhook secret for signature verification.
             * Set this in your .env file as GITHUB_WEBHOOK_SECRET
             */
            'signing_secret' => env('GITHUB_WEBHOOK_SECRET'),

            /*
             * GitHub sends the signature in the X-Hub-Signature-256 header.
             */
            'signature_header_name' => 'X-Hub-Signature-256',

            /*
             * Custom signature validator for GitHub's HMAC SHA-256 signatures.
             */
            'signature_validator' => \App\Webhooks\GitHubSignatureValidator::class,

            /*
             * Filter which GitHub events should be processed.
             */
            'webhook_profile' => \App\Webhooks\GitHubWebhookProfile::class,

            /*
             * Default response handler.
             */
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,

            /*
             * Model for storing webhook calls.
             */
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,

            /*
             * Store GitHub-specific headers for debugging and processing.
             */
            'store_headers' => [
                'X-GitHub-Event',
                'X-GitHub-Delivery',
                'X-Hub-Signature-256',
            ],

            /*
             * Job that processes GitHub webhook events.
             */
            'process_webhook_job' => \App\Jobs\ProcessGitHubWebhookJob::class,
        ],
    ],

    /*
     * Delete webhook records after 30 days.
     */
    'delete_after_days' => 30,

    /*
     * Don't add unique token to route name for cleaner URLs.
     */
    'add_unique_token_to_route_name' => false,
];
