<?php

declare(strict_types=1);

namespace CloudPayments\Webhook;

/**
 * Verifies the HMAC signature CloudPayments attaches to every webhook.
 *
 * The signature is `base64(HMAC-SHA256(rawBody, apiSecret))`, sent in the
 * `Content-HMAC` header (and the newer `X-Content-HMAC`). It MUST be computed
 * over the exact raw request body bytes — never the re-encoded/parsed form.
 */
final readonly class SignatureValidator
{
    /** Header names that may carry the signature, newest first. */
    public const array HEADERS = ['X-Content-HMAC', 'Content-HMAC'];

    public function __construct(private string $apiSecret)
    {
    }

    public function sign(string $rawBody): string
    {
        return base64_encode(hash_hmac('sha256', $rawBody, $this->apiSecret, true));
    }

    /**
     * Constant-time comparison of the expected signature against the provided one.
     */
    public function isValid(string $rawBody, ?string $signature): bool
    {
        if ($signature === null || $signature === '') {
            return false;
        }

        return hash_equals($this->sign($rawBody), $signature);
    }
}
