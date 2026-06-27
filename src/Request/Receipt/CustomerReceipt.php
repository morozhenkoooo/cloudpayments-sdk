<?php

declare(strict_types=1);

namespace CloudPayments\Request\Receipt;

use CloudPayments\Contract\ApiRequest;
use CloudPayments\Enum\TaxationSystem;

/**
 * The 54-FZ customer receipt body (чек): line items, taxation system, contact
 * details, and the optional payment-form breakdown.
 *
 * Serializes with camelCase keys, as the CloudKassir receipt API expects for
 * the nested `CustomerReceipt` object.
 */
final readonly class CustomerReceipt implements ApiRequest
{
    /**
     * @param list<ReceiptItem> $items
     */
    public function __construct(
        public array $items,
        public TaxationSystem $taxationSystem,
        public ?string $email = null,
        public ?string $phone = null,
        public ?ReceiptAmounts $amounts = null,
        public ?bool $isBso = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'taxationSystem' => $this->taxationSystem->value,
            'email' => $this->email,
            'phone' => $this->phone,
            'isBso' => $this->isBso,
            'amounts' => $this->amounts?->toArray(),
            'items' => array_map(static fn (ReceiptItem $i): array => $i->toArray(), $this->items),
        ];
    }
}
