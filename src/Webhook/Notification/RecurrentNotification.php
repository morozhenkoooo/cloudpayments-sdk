<?php

declare(strict_types=1);

namespace CloudPayments\Webhook\Notification;

use CloudPayments\Enum\Currency;
use CloudPayments\Enum\NotificationType;
use CloudPayments\Enum\SubscriptionStatus;
use CloudPayments\Support\Data;

/**
 * The `Recurrent` webhook: the status of a recurring subscription has changed.
 */
final readonly class RecurrentNotification implements \CloudPayments\Contract\Notification
{
    /**
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public ?string $id,
        public ?string $accountId,
        public ?string $description,
        public ?string $email,
        public ?float $amount,
        public ?Currency $currency,
        public ?bool $requireConfirmation,
        public ?\DateTimeImmutable $startDate,
        public ?string $interval,
        public ?int $period,
        public ?SubscriptionStatus $status,
        public ?int $successfulTransactionsNumber,
        public ?int $failedTransactionsNumber,
        public ?int $maxPeriods,
        public ?\DateTimeImmutable $lastTransactionDate,
        public ?\DateTimeImmutable $nextTransactionDate,
        public array $raw,
    ) {
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Data::string($data, 'Id'),
            accountId: Data::string($data, 'AccountId'),
            description: Data::string($data, 'Description'),
            email: Data::string($data, 'Email'),
            amount: Data::float($data, 'Amount'),
            currency: ($c = Data::string($data, 'Currency')) !== null ? Currency::tryFrom($c) : null,
            requireConfirmation: Data::bool($data, 'RequireConfirmation'),
            startDate: Data::dateTime($data, 'StartDate'),
            interval: Data::string($data, 'Interval'),
            period: Data::int($data, 'Period'),
            status: SubscriptionStatus::resolve(Data::string($data, 'Status'), Data::int($data, 'StatusCode')),
            successfulTransactionsNumber: Data::int($data, 'SuccessfulTransactionsNumber'),
            failedTransactionsNumber: Data::int($data, 'FailedTransactionsNumber'),
            maxPeriods: Data::int($data, 'MaxPeriods'),
            lastTransactionDate: Data::dateTime($data, 'LastTransactionDate'),
            nextTransactionDate: Data::dateTime($data, 'NextTransactionDate'),
            raw: $data,
        );
    }

    public function type(): \CloudPayments\Enum\NotificationType
    {
        return NotificationType::Recurrent;
    }
}
