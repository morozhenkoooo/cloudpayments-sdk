<?php

declare(strict_types=1);

namespace CloudPayments\Response;

use CloudPayments\Support\Data;

/**
 * Result of a refund: the id of the newly created refund transaction.
 */
final readonly class Refund
{
    public function __construct(
        public int $transactionId,
    ) {
    }

    /**
     * @param array<array-key, mixed> $model
     */
    public static function fromModel(array $model): self
    {
        return new self(
            transactionId: Data::int($model, 'TransactionId') ?? 0,
        );
    }
}
