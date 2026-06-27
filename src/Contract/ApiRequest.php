<?php

declare(strict_types=1);

namespace CloudPayments\Contract;

/**
 * A request payload that can be serialized to the associative array
 * CloudPayments expects in the request body.
 */
interface ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
