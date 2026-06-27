<?php

declare(strict_types=1);

namespace CloudPayments\Webhook\Notification;

use CloudPayments\Enum\NotificationType;
use CloudPayments\Support\Data;

/**
 * The `Refund` webhook: a (partial or full) refund was issued against a payment.
 */
final readonly class RefundNotification implements \CloudPayments\Contract\Notification
{
    /**
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public ?int $transactionId,
        public ?int $paymentTransactionId,
        public ?float $amount,
        public ?\DateTimeImmutable $dateTime,
        public ?string $invoiceId,
        public ?string $accountId,
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
            paymentTransactionId: Data::int($data, 'PaymentTransactionId'),
            amount: Data::float($data, 'Amount'),
            dateTime: Data::dateTime($data, 'DateTime'),
            invoiceId: Data::string($data, 'InvoiceId'),
            accountId: Data::string($data, 'AccountId'),
            raw: $data,
        );
    }

    public function type(): \CloudPayments\Enum\NotificationType
    {
        return NotificationType::Refund;
    }
}
