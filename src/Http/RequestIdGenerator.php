<?php

declare(strict_types=1);

namespace CloudPayments\Http;

/**
 * Generates the value sent in the `X-Request-ID` idempotency header.
 *
 * CloudPayments deduplicates mutating requests that carry the same id for one
 * hour, so retries do not double-charge.
 */
interface RequestIdGenerator
{
    public function generate(): string;
}
