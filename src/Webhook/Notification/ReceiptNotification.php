<?php

declare(strict_types=1);

namespace CloudPayments\Webhook\Notification;

use CloudPayments\Enum\NotificationType;
use CloudPayments\Support\Data;

/**
 * The `Receipt` webhook: a 54-FZ fiscal receipt was registered with the OFD.
 */
final readonly class ReceiptNotification implements \CloudPayments\Contract\Notification
{
    /**
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public ?string $id,
        public ?int $documentNumber,
        public ?int $sessionNumber,
        public ?string $type,
        public ?float $sum,
        public ?string $fn,
        public ?string $fiscalSign,
        public ?string $deviceSn,
        public ?string $ofd,
        public ?string $url,
        public ?string $invoiceId,
        public ?string $accountId,
        public ?float $amount,
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
            documentNumber: Data::int($data, 'DocumentNumber'),
            sessionNumber: Data::int($data, 'SessionNumber'),
            type: Data::string($data, 'Type'),
            sum: Data::float($data, 'Sum'),
            fn: Data::string($data, 'Fn'),
            fiscalSign: Data::string($data, 'FiscalSign'),
            deviceSn: Data::string($data, 'DeviceSn'),
            ofd: Data::string($data, 'Ofd'),
            url: Data::string($data, 'Url'),
            invoiceId: Data::string($data, 'InvoiceId'),
            accountId: Data::string($data, 'AccountId'),
            amount: Data::float($data, 'Amount'),
            raw: $data,
        );
    }

    public function type(): \CloudPayments\Enum\NotificationType
    {
        return NotificationType::Receipt;
    }
}
