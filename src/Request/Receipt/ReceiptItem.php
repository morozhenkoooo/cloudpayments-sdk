<?php

declare(strict_types=1);

namespace CloudPayments\Request\Receipt;

use CloudPayments\Contract\ApiRequest;
use CloudPayments\Enum\PaymentMethod;
use CloudPayments\Enum\PaymentObject;
use CloudPayments\Enum\VatRate;

/**
 * A single line item (товарная позиция) of a 54-FZ customer receipt.
 *
 * Serializes with camelCase keys, as the CloudKassir receipt API expects for
 * the nested `CustomerReceipt.items` array.
 */
final readonly class ReceiptItem implements ApiRequest
{
    public function __construct(
        public string $label,
        public float $price,
        public float $quantity,
        public float $amount,
        public ?VatRate $vat = null,
        public ?PaymentMethod $method = null,
        public ?PaymentObject $object = null,
        public ?string $measurementUnit = null,
        public ?string $ean13 = null,
        public ?string $agentSign = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'vat' => $this->vat?->value,
            'method' => $this->method?->value,
            'object' => $this->object?->value,
            'measurementUnit' => $this->measurementUnit,
            'ean13' => $this->ean13,
            'agentSign' => $this->agentSign,
        ];
    }
}
