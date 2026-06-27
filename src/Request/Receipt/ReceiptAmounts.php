<?php

declare(strict_types=1);

namespace CloudPayments\Request\Receipt;

use CloudPayments\Contract\ApiRequest;

/**
 * Breakdown of how a 54-FZ receipt total is settled across payment forms
 * (electronic, advance, credit, provision). All fields are optional.
 */
final readonly class ReceiptAmounts implements ApiRequest
{
    public function __construct(
        public ?float $electronic = null,
        public ?float $advancePayment = null,
        public ?float $credit = null,
        public ?float $provision = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'electronic' => $this->electronic,
            'advancePayment' => $this->advancePayment,
            'credit' => $this->credit,
            'provision' => $this->provision,
        ];
    }
}
