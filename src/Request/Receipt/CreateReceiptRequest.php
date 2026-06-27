<?php

declare(strict_types=1);

namespace CloudPayments\Request\Receipt;

use CloudPayments\Contract\ApiRequest;
use CloudPayments\Enum\ReceiptType;

/**
 * Request to register a 54-FZ fiscal receipt (`/kkt/receipt`).
 *
 * Top-level keys are PascalCase; the embedded {@see CustomerReceipt} keeps its
 * own camelCase shape. The whole body is JSON-encoded once by the transport.
 */
final readonly class CreateReceiptRequest implements ApiRequest
{
    public function __construct(
        public ReceiptType $type,
        public CustomerReceipt $customerReceipt,
        public ?string $inn = null,
        public ?string $invoiceId = null,
        public ?string $accountId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'Inn' => $this->inn,
            'Type' => $this->type->value,
            'InvoiceId' => $this->invoiceId,
            'AccountId' => $this->accountId,
            'CustomerReceipt' => $this->customerReceipt->toArray(),
        ];
    }
}
