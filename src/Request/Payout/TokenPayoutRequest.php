<?php

declare(strict_types=1);

namespace CloudPayments\Request\Payout;

use CloudPayments\Contract\ApiRequest;
use CloudPayments\Enum\Currency;
use CloudPayments\ValueObject\Amount;

/**
 * Payout to a saved card token (server-initiated).
 */
final readonly class TokenPayoutRequest implements ApiRequest
{
    public function __construct(
        public string $token,
        public Amount $amount,
        public ?Currency $currency = Currency::RUB,
        public ?string $accountId = null,
        public ?string $invoiceId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'Token' => $this->token,
            'Amount' => (string) $this->amount,
            'Currency' => $this->currency?->value,
            'AccountId' => $this->accountId,
            'InvoiceId' => $this->invoiceId,
        ];
    }
}
