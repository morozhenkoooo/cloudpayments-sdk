<?php

declare(strict_types=1);

namespace CloudPayments\Response;

use CloudPayments\Enum\Currency;
use CloudPayments\Enum\TransactionStatus;
use CloudPayments\Support\Data;
use CloudPayments\ValueObject\Card;

/**
 * The transaction returned by a card or token payout (topup).
 */
final readonly class Payout
{
    /**
     * @param array<array-key, mixed> $raw the untouched response Model, as an escape hatch
     */
    public function __construct(
        public int $transactionId,
        public ?float $amount,
        public ?Currency $currency,
        public ?TransactionStatus $status,
        public ?int $statusCode,
        public ?string $token,
        public Card $card,
        public array $raw,
    ) {
    }

    /**
     * @param array<array-key, mixed> $model
     */
    public static function fromModel(array $model): self
    {
        return new self(
            transactionId: Data::int($model, 'TransactionId') ?? 0,
            amount: Data::float($model, 'Amount'),
            currency: ($c = Data::string($model, 'Currency')) !== null ? Currency::tryFrom($c) : null,
            status: TransactionStatus::resolve(Data::string($model, 'Status'), Data::int($model, 'StatusCode')),
            statusCode: Data::int($model, 'StatusCode'),
            token: Data::string($model, 'Token'),
            card: Card::fromModel($model),
            raw: $model,
        );
    }
}
