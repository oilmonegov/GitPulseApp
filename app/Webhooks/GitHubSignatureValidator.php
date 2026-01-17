<?php

declare(strict_types=1);

namespace App\Webhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

/**
 * Validates GitHub webhook signatures using HMAC SHA-256.
 *
 * GitHub sends a signature in the X-Hub-Signature-256 header in the format:
 * sha256=<hash>
 */
final class GitHubSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = $request->header($config->signatureHeaderName);

        if (! $signature) {
            return false;
        }

        $secret = $config->signingSecret;

        if (empty($secret)) {
            return false;
        }

        $computedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($computedSignature, $signature);
    }
}
