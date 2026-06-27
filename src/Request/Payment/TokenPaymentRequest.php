<?php

declare(strict_types=1);

namespace CloudPayments\Request\Payment;

use CloudPayments\Contract\ApiRequest;
use CloudPayments\Enum\Currency;
use CloudPayments\Support\Payload;
use CloudPayments\ValueObject\Amount;

/**
 * Server-initiated payment by a previously saved card token (no cryptogram,
 * no cardholder present). Drives both `payments()->chargeToken()` and
 * `payments()->authToken()`.
 */
final readonly class TokenPaymentRequest implements ApiRequest
{
    /**
     * @param array<string, mixed>|null $jsonData
     */
    public function __construct(
        public Amount $amount,
        public string $accountId,
        public string $token,
        public ?Currency $currency = Currency::RUB,
        public ?string $invoiceId = null,
        public ?string $description = null,
        public ?string $email = null,
        public ?string $ipAddress = null,
        public ?array $jsonData = null,
        public ?string $cultureName = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'Amount' => (string) $this->amount,
            'Currency' => $this->currency?->value,
            'AccountId' => $this->accountId,
            'Token' => $this->token,
            'InvoiceId' => $this->invoiceId,
            'Description' => $this->description,
            'Email' => $this->email,
            'IpAddress' => $this->ipAddress,
            'JsonData' => Payload::json($this->jsonData),
            'CultureName' => $this->cultureName,
        ];
    }
}
