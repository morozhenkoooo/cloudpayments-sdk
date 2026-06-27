<?php

declare(strict_types=1);

namespace CloudPayments\Webhook\Notification;

use CloudPayments\Enum\NotificationType;
use CloudPayments\Enum\TransactionStatus;
use CloudPayments\Support\Data;

/**
 * The `Confirm` webhook: a previously authorized (two-step) payment has been
 * confirmed and the funds captured.
 */
final readonly class ConfirmNotification implements \CloudPayments\Contract\Notification
{
    /**
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public ?int $transactionId,
        public ?float $amount,
        public ?float $paymentAmount,
        public ?\DateTimeImmutable $dateTime,
        public ?string $invoiceId,
        public ?string $accountId,
        public ?string $subscriptionId,
        public ?TransactionStatus $status,
        public ?string $token,
        public array $raw,
    ) {
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: Data::int($data, 'TransactionId'),
            amount: Data::float($data, 'Amount'),
            paymentAmount: Data::float($data, 'PaymentAmount'),
            dateTime: Data::dateTime($data, 'DateTime'),
            invoiceId: Data::string($data, 'InvoiceId'),
            accountId: Data::string($data, 'AccountId'),
            subscriptionId: Data::string($data, 'SubscriptionId'),
            status: TransactionStatus::resolve(Data::string($data, 'Status'), Data::int($data, 'StatusCode')),
            token: Data::string($data, 'Token'),
            raw: $data,
        );
    }

    public function type(): \CloudPayments\Enum\NotificationType
    {
        return NotificationType::Confirm;
    }
}
