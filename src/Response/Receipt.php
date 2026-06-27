<?php

declare(strict_types=1);

namespace CloudPayments\Response;

use CloudPayments\Support\Data;

/**
 * A registered 54-FZ fiscal receipt as returned by create/status/get.
 *
 * Fields mirror the CloudKassir receipt Model; fiscal identifiers (Fn,
 * FiscalSign, DeviceSn, Ofd, Url) populate once the receipt is fiscalized.
 */
final readonly class Receipt
{
    /**
     * @param array<array-key, mixed> $raw the untouched response Model, as an escape hatch
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
        public ?int $transactionId,
        public array $raw,
    ) {
    }

    /**
     * @param array<array-key, mixed> $model
     */
    public static function fromModel(array $model): self
    {
        return new self(
            id: Data::string($model, 'Id'),
            documentNumber: Data::int($model, 'DocumentNumber'),
            sessionNumber: Data::int($model, 'SessionNumber'),
            type: Data::string($model, 'Type'),
            sum: Data::float($model, 'Sum'),
            fn: Data::string($model, 'Fn'),
            fiscalSign: Data::string($model, 'FiscalSign'),
            deviceSn: Data::string($model, 'DeviceSn'),
            ofd: Data::string($model, 'Ofd'),
            url: Data::string($model, 'Url'),
            invoiceId: Data::string($model, 'InvoiceId'),
            accountId: Data::string($model, 'AccountId'),
            amount: Data::float($model, 'Amount'),
            transactionId: Data::int($model, 'TransactionId'),
            raw: $model,
        );
    }
}
