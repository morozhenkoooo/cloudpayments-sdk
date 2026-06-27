<?php

declare(strict_types=1);

namespace CloudPayments\Webhook\Notification;

use CloudPayments\Enum\Currency;
use CloudPayments\Enum\NotificationType;
use CloudPayments\Enum\TransactionStatus;
use CloudPayments\Support\Data;
use CloudPayments\ValueObject\Card;

/**
 * The `Pay` webhook: a payment has been completed successfully.
 */
final readonly class PayNotification implements \CloudPayments\Contract\Notification
{
    /**
     * @param array<array-key, mixed>|null $data
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public ?int $transactionId,
        public ?float $amount,
        public ?Currency $currency,
        public ?float $paymentAmount,
        public ?\DateTimeImmutable $dateTime,
        public ?string $cardFirstSix,
        public ?string $cardLastFour,
        public ?string $cardType,
        public ?string $cardExpDate,
        public ?bool $testMode,
        public ?string $invoiceId,
        public ?string $accountId,
        public ?string $subscriptionId,
        public ?string $name,
        public ?string $email,
        public ?string $ipAddress,
        public ?string $ipCountry,
        public ?string $ipCity,
        public ?string $authCode,
        public ?string $token,
        public ?TransactionStatus $status,
        public ?string $operationType,
        public ?string $gatewayName,
        public ?string $cardProduct,
        public ?array $data,
        public Card $card,
        public array $raw,
    ) {
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawData = $data['Data'] ?? null;
        $decoded = \is_string($rawData) ? json_decode($rawData, true) : null;

        return new self(
            transactionId: Data::int($data, 'TransactionId'),
            amount: Data::float($data, 'Amount'),
            currency: ($c = Data::string($data, 'Currency')) !== null ? Currency::tryFrom($c) : null,
            paymentAmount: Data::float($data, 'PaymentAmount'),
            dateTime: Data::dateTime($data, 'DateTime'),
            cardFirstSix: Data::string($data, 'CardFirstSix'),
            cardLastFour: Data::string($data, 'CardLastFour'),
            cardType: Data::string($data, 'CardType'),
            cardExpDate: Data::string($data, 'CardExpDate'),
            testMode: Data::bool($data, 'TestMode'),
            invoiceId: Data::string($data, 'InvoiceId'),
            accountId: Data::string($data, 'AccountId'),
            subscriptionId: Data::string($data, 'SubscriptionId'),
            name: Data::string($data, 'Name'),
            email: Data::string($data, 'Email'),
            ipAddress: Data::string($data, 'IpAddress'),
            ipCountry: Data::string($data, 'IpCountry'),
            ipCity: Data::string($data, 'IpCity'),
            authCode: Data::string($data, 'AuthCode'),
            token: Data::string($data, 'Token'),
            status: TransactionStatus::resolve(Data::string($data, 'Status'), Data::int($data, 'StatusCode')),
            operationType: Data::string($data, 'OperationType'),
            gatewayName: Data::string($data, 'GatewayName'),
            cardProduct: Data::string($data, 'CardProduct'),
            data: \is_array($decoded) ? $decoded : null,
            card: Card::fromModel($data),
            raw: $data,
        );
    }

    public function type(): \CloudPayments\Enum\NotificationType
    {
        return NotificationType::Pay;
    }
}
