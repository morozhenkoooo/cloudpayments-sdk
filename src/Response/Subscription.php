<?php

declare(strict_types=1);

namespace CloudPayments\Response;

use CloudPayments\Enum\Currency;
use CloudPayments\Enum\Interval;
use CloudPayments\Enum\SubscriptionStatus;
use CloudPayments\Support\Data;

/**
 * A recurring subscription as returned by create/get/find/update.
 *
 * Lifecycle is exposed through {@see $status}; use {@see isActive()} and
 * {@see isCancelled()} for the common checks.
 */
final readonly class Subscription
{
    /**
     * @param array<array-key, mixed> $raw the untouched response Model, as an escape hatch
     */
    public function __construct(
        public string $id,
        public ?string $accountId,
        public ?string $description,
        public ?string $email,
        public ?float $amount,
        public ?int $currencyCode,
        public ?Currency $currency,
        public ?bool $requireConfirmation,
        public ?\DateTimeImmutable $startDate,
        public ?Interval $interval,
        public ?int $intervalCode,
        public ?int $period,
        public ?int $maxPeriods,
        public ?SubscriptionStatus $status,
        public ?int $statusCode,
        public ?int $successfulTransactionsNumber,
        public ?int $failedTransactionsNumber,
        public ?int $maxPeriodsFailed,
        public ?\DateTimeImmutable $lastTransactionDate,
        public ?\DateTimeImmutable $nextTransactionDate,
        public array $raw,
    ) {
    }

    /**
     * @param array<array-key, mixed> $model
     */
    public static function fromModel(array $model): self
    {
        return new self(
            id: Data::requireString($model, 'Id'),
            accountId: Data::string($model, 'AccountId'),
            description: Data::string($model, 'Description'),
            email: Data::string($model, 'Email'),
            amount: Data::float($model, 'Amount'),
            currencyCode: Data::int($model, 'CurrencyCode'),
            currency: ($c = Data::string($model, 'Currency')) !== null ? Currency::tryFrom($c) : null,
            requireConfirmation: Data::bool($model, 'RequireConfirmation'),
            startDate: Data::dateTime($model, 'StartDateIso') ?? Data::dateTime($model, 'StartDate'),
            interval: ($i = Data::string($model, 'Interval')) !== null ? Interval::tryFrom($i) : null,
            intervalCode: Data::int($model, 'IntervalCode'),
            period: Data::int($model, 'Period'),
            maxPeriods: Data::int($model, 'MaxPeriods'),
            status: SubscriptionStatus::resolve(Data::string($model, 'Status'), Data::int($model, 'StatusCode')),
            statusCode: Data::int($model, 'StatusCode'),
            successfulTransactionsNumber: Data::int($model, 'SuccessfulTransactionsNumber'),
            failedTransactionsNumber: Data::int($model, 'FailedTransactionsNumber'),
            maxPeriodsFailed: Data::int($model, 'MaxPeriodsFailed'),
            lastTransactionDate: Data::dateTime($model, 'LastTransactionDateIso') ?? Data::dateTime($model, 'LastTransactionDate'),
            nextTransactionDate: Data::dateTime($model, 'NextTransactionDateIso') ?? Data::dateTime($model, 'NextTransactionDate'),
            raw: $model,
        );
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }

    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatus::Cancelled;
    }
}
