<?php

declare(strict_types=1);

namespace CloudPayments\ValueObject;

use CloudPayments\Support\Data;

/**
 * Masked card details returned with a transaction. Never contains the full PAN.
 */
final readonly class Card
{
    public function __construct(
        public ?string $firstSix,
        public ?string $lastFour,
        public ?string $type,
        public ?string $expDate,
        public ?string $product,
        public ?string $issuer,
        public ?string $issuerBankCountry,
    ) {
    }

    /**
     * @param array<array-key, mixed> $model
     */
    public static function fromModel(array $model): self
    {
        return new self(
            firstSix: Data::string($model, 'CardFirstSix'),
            lastFour: Data::string($model, 'CardLastFour'),
            type: Data::string($model, 'CardType'),
            expDate: Data::string($model, 'CardExpDate'),
            product: Data::string($model, 'CardProduct'),
            issuer: Data::string($model, 'Issuer'),
            issuerBankCountry: Data::string($model, 'IssuerBankCountry'),
        );
    }

    public function maskedNumber(): ?string
    {
        if ($this->firstSix === null || $this->lastFour === null) {
            return null;
        }

        return $this->firstSix . '******' . $this->lastFour;
    }
}
