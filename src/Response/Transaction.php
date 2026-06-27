<?php

declare(strict_types=1);

namespace CloudPayments\Response;

use CloudPayments\Enum\Currency;
use CloudPayments\Enum\ReasonCode;
use CloudPayments\Enum\TransactionStatus;
use CloudPayments\Support\Data;
use CloudPayments\ValueObject\Card;

/**
 * A payment transaction as returned by charge/auth/confirm/get/find and friends.
 *
 * A declined payment is still a {@see Transaction} — inspect {@see isDeclined()}
 * and {@see $reasonCode} rather than expecting an exception.
 */
final readonly class Transaction
{
    /**
     * @param array<array-key, mixed> $raw the untouched response Model, as an escape hatch
     */
    public function __construct(
        public int $transactionId,
        public ?float $amount,
        public ?Currency $currency,
        public ?float $paymentAmount,
        public ?string $invoiceId,
        public ?string $accountId,
        public ?string $subscriptionId,
        public ?string $email,
        public ?string $description,
        public ?string $authCode,
        public ?string $token,
        public ?TransactionStatus $status,
        public ?int $statusCode,
        public ?ReasonCode $reasonCode,
        public ?int $reasonCodeRaw,
        public ?string $reason,
        public ?string $cardHolderMessage,
        public ?string $name,
        public ?string $ipAddress,
        public ?bool $testMode,
        public ?string $gatewayName,
        public Card $card,
        public ?\DateTimeImmutable $createdAt,
        public array $raw,
    ) {
    }

    /**
     * @param array<array-key, mixed> $model
     */
    public static function fromModel(array $model): self
    {
        $reasonCodeRaw = Data::int($model, 'ReasonCode');

        return new self(
            transactionId: Data::int($model, 'TransactionId') ?? 0,
            amount: Data::float($model, 'Amount'),
            currency: ($c = Data::string($model, 'Currency')) !== null ? Currency::tryFrom($c) : null,
            paymentAmount: Data::float($model, 'PaymentAmount'),
            invoiceId: Data::string($model, 'InvoiceId'),
            accountId: Data::string($model, 'AccountId'),
            subscriptionId: Data::string($model, 'SubscriptionId'),
            email: Data::string($model, 'Email'),
            description: Data::string($model, 'Description'),
            authCode: Data::string($model, 'AuthCode'),
            token: Data::string($model, 'Token'),
            status: TransactionStatus::resolve(Data::string($model, 'Status'), Data::int($model, 'StatusCode')),
            statusCode: Data::int($model, 'StatusCode'),
            reasonCode: $reasonCodeRaw !== null ? ReasonCode::tryFrom($reasonCodeRaw) : null,
            reasonCodeRaw: $reasonCodeRaw,
            reason: Data::string($model, 'Reason'),
            cardHolderMessage: Data::string($model, 'CardHolderMessage'),
            name: Data::string($model, 'Name'),
            ipAddress: Data::string($model, 'IpAddress'),
            testMode: Data::bool($model, 'TestMode'),
            gatewayName: Data::string($model, 'GatewayName'),
            card: Card::fromModel($model),
            createdAt: Data::dateTime($model, 'CreatedDateIso') ?? Data::dateTime($model, 'CreatedDate'),
            raw: $model,
        );
    }

    public function isCompleted(): bool
    {
        return $this->status === TransactionStatus::Completed;
    }

    public function isAuthorized(): bool
    {
        return $this->status === TransactionStatus::Authorized;
    }

    public function isDeclined(): bool
    {
        return $this->status === TransactionStatus::Declined;
    }
}
