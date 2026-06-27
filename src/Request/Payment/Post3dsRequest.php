<?php

declare(strict_types=1);

namespace CloudPayments\Request\Payment;

use CloudPayments\Contract\ApiRequest;

/**
 * Complete a payment after the cardholder finished the 3-D Secure challenge.
 * `PaRes` (v1) or the v2 callback data is returned by the ACS to your TermUrl.
 */
final readonly class Post3dsRequest implements ApiRequest
{
    public function __construct(
        public int $transactionId,
        public string $paRes,
        public ?string $threeDsCallbackId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'TransactionId' => $this->transactionId,
            'PaRes' => $this->paRes,
            'ThreeDsCallbackId' => $this->threeDsCallbackId,
        ];
    }
}
