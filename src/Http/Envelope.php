<?php

declare(strict_types=1);

namespace CloudPayments\Http;

/**
 * The decoded CloudPayments response envelope: `{ Success, Message, Model }`.
 *
 * `Success` reflects only the API call, not the business outcome — a declined
 * card or a 3DS-required response can both come back with `Success: false`.
 * Interpreting `Model` is the job of the API layer.
 */
final readonly class Envelope
{
    /**
     * @param array<array-key, mixed> $model the decoded `Model` (empty array when null/absent)
     * @param array<array-key, mixed> $raw the full decoded response body
     */
    public function __construct(
        public bool $success,
        public ?string $message,
        public array $model,
        public int $httpStatusCode,
        public array $raw,
    ) {
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->model);
    }
}
