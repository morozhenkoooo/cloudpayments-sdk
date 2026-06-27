<?php

declare(strict_types=1);

namespace CloudPayments\Exception;

/**
 * CloudPayments returned a transport-level success but the API reported a
 * failure that is not a normal business outcome (e.g. malformed parameters,
 * unknown transaction). Card declines are NOT represented as exceptions — they
 * come back as a transaction with a `Declined` status and a reason code.
 */
class ApiException extends \RuntimeException implements CloudPaymentsException
{
    /**
     * @param array<array-key, mixed>|null $response the decoded response body, when available
     */
    public function __construct(
        string $message,
        private readonly int $httpStatusCode = 0,
        private readonly ?array $response = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function httpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function response(): ?array
    {
        return $this->response;
    }
}
