<?php

declare(strict_types=1);

namespace CloudPayments\Request\Payment;

use CloudPayments\Contract\ApiRequest;
use CloudPayments\Support\Payload;
use CloudPayments\ValueObject\Amount;

/**
 * Capture (confirm) a previously authorized two-stage payment. The amount may
 * be less than the authorized amount for a partial capture.
 */
final readonly class ConfirmRequest implements ApiRequest
{
    /**
     * @param array<string, mixed>|null $jsonData
     */
    public function __construct(
        public int $transactionId,
        public Amount $amount,
        public ?array $jsonData = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'TransactionId' => $this->transactionId,
            'Amount' => (string) $this->amount,
            'JsonData' => Payload::json($this->jsonData),
        ];
    }
}
