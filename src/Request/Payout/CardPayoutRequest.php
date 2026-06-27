<?php

declare(strict_types=1);

namespace CloudPayments\Request\Payout;

use CloudPayments\Contract\ApiRequest;
use CloudPayments\Enum\Currency;
use CloudPayments\ValueObject\Amount;

/**
 * Payout to a card by cryptogram (the packet produced by the CloudPayments widget).
 */
final readonly class CardPayoutRequest implements ApiRequest
{
    public function __construct(
        public string $cardCryptogramPacket,
        public Amount $amount,
        public ?Currency $currency = Currency::RUB,
        public ?string $name = null,
        public ?string $accountId = null,
        public ?string $invoiceId = null,
        public ?string $email = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'CardCryptogramPacket' => $this->cardCryptogramPacket,
            'Amount' => (string) $this->amount,
            'Currency' => $this->currency?->value,
            'Name' => $this->name,
            'AccountId' => $this->accountId,
            'InvoiceId' => $this->invoiceId,
            'Email' => $this->email,
        ];
    }
}
