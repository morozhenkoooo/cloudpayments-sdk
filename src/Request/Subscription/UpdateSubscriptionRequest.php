<?php

declare(strict_types=1);

namespace CloudPayments\Request\Subscription;

use CloudPayments\Contract\ApiRequest;
use CloudPayments\Enum\Currency;
use CloudPayments\Enum\Interval;
use CloudPayments\ValueObject\Amount;

/**
 * Update an existing subscription, identified by {@see $id}.
 *
 * Every billing field is optional: only the values supplied are sent, leaving
 * the rest unchanged on the CloudPayments side.
 */
final readonly class UpdateSubscriptionRequest implements ApiRequest
{
    public function __construct(
        public string $id,
        public ?Amount $amount = null,
        public ?Currency $currency = null,
        public ?string $description = null,
        public ?\DateTimeInterface $startDate = null,
        public ?Interval $interval = null,
        public ?int $period = null,
        public ?int $maxPeriods = null,
        public ?bool $requireConfirmation = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'Id' => $this->id,
            'Amount' => $this->amount !== null ? (string) $this->amount : null,
            'Currency' => $this->currency?->value,
            'Description' => $this->description,
            'StartDate' => $this->startDate?->format('Y-m-d H:i:s'),
            'Interval' => $this->interval?->value,
            'Period' => $this->period,
            'MaxPeriods' => $this->maxPeriods,
            'RequireConfirmation' => $this->requireConfirmation,
        ];
    }
}
