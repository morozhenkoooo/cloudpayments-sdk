<?php

declare(strict_types=1);

namespace CloudPayments\Request\Subscription;

use CloudPayments\Contract\ApiRequest;
use CloudPayments\Enum\Currency;
use CloudPayments\Enum\Interval;
use CloudPayments\Request\Receipt\CustomerReceipt;
use CloudPayments\Support\Payload;
use CloudPayments\ValueObject\Amount;

/**
 * Create a recurring subscription billed against a saved card token.
 *
 * The {@see $token} is obtained from a prior payment made with `SaveCard`
 * enabled; {@see $startDate} schedules the first recurring charge.
 */
final readonly class CreateSubscriptionRequest implements ApiRequest
{
    public function __construct(
        public string $token,
        public string $accountId,
        public string $description,
        public string $email,
        public Amount $amount,
        public \DateTimeInterface $startDate,
        public Interval $interval,
        public int $period,
        public ?Currency $currency = Currency::RUB,
        public ?bool $requireConfirmation = null,
        public ?int $maxPeriods = null,
        /** Online-checkout receipt (54-FZ) registered with each recurring charge. */
        public ?CustomerReceipt $customerReceipt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'Token' => $this->token,
            'AccountId' => $this->accountId,
            'Description' => $this->description,
            'Email' => $this->email,
            'Amount' => (string) $this->amount,
            'Currency' => $this->currency?->value,
            'RequireConfirmation' => $this->requireConfirmation,
            'StartDate' => $this->startDate->format('Y-m-d H:i:s'),
            'Interval' => $this->interval->value,
            'Period' => $this->period,
            'MaxPeriods' => $this->maxPeriods,
            'CustomerReceipt' => Payload::json($this->customerReceipt?->toArray()),
        ];
    }
}
