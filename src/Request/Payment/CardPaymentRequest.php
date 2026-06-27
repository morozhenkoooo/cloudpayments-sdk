<?php

declare(strict_types=1);

namespace CloudPayments\Request\Payment;

use CloudPayments\Contract\ApiRequest;
use CloudPayments\Enum\Currency;
use CloudPayments\Support\Payload;
use CloudPayments\ValueObject\Amount;

/**
 * Payment by card cryptogram (the packet produced by the CloudPayments widget).
 *
 * The same payload drives both the single-stage charge (`payments()->charge()`)
 * and the two-stage authorization (`payments()->auth()`); Apple Pay / Google
 * Pay flows reuse it by placing the wallet token in {@see $cardCryptogramPacket}.
 *
 * @phpstan-type JsonData array<string, mixed>
 */
final readonly class CardPaymentRequest implements ApiRequest
{
    /**
     * @param array<string, mixed>|null $jsonData arbitrary merchant data; also drives receipt/widget behaviour
     */
    public function __construct(
        public Amount $amount,
        public string $ipAddress,
        public string $cardCryptogramPacket,
        public ?Currency $currency = Currency::RUB,
        public ?string $name = null,
        public ?string $invoiceId = null,
        public ?string $description = null,
        public ?string $accountId = null,
        public ?string $email = null,
        public ?Payer $payer = null,
        public ?array $jsonData = null,
        public ?bool $saveCard = null,
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
            'IpAddress' => $this->ipAddress,
            'CardCryptogramPacket' => $this->cardCryptogramPacket,
            'Name' => $this->name,
            'InvoiceId' => $this->invoiceId,
            'Description' => $this->description,
            'AccountId' => $this->accountId,
            'Email' => $this->email,
            'Payer' => Payload::json($this->payer?->toArray()),
            'JsonData' => Payload::json($this->jsonData),
            'SaveCard' => $this->saveCard,
            'CultureName' => $this->cultureName,
        ];
    }
}
