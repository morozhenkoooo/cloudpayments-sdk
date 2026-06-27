<?php

declare(strict_types=1);

namespace CloudPayments\Response;

use CloudPayments\Support\Data;

/**
 * A 3-D Secure authentication challenge returned instead of a completed
 * transaction. Redirect the cardholder's browser to {@see $acsUrl}, POSTing
 * the challenge fields, then finish the payment via `payments()->post3ds()`.
 *
 * Supports both 3DS v1 (`PaReq`/`MD`) and v2 (`creq`/`threeDSSessionData`).
 */
final readonly class Secure3DS
{
    /**
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public int $transactionId,
        public string $acsUrl,
        public ?string $paReq,
        public ?string $md,
        public ?string $creq,
        public ?string $threeDsSessionData,
        public ?string $threeDsCallbackId,
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
            acsUrl: Data::requireString($model, 'AcsUrl'),
            paReq: Data::string($model, 'PaReq'),
            md: Data::string($model, 'MD') ?? Data::string($model, 'TransactionId'),
            creq: Data::string($model, 'creq') ?? Data::string($model, 'CReq'),
            threeDsSessionData: Data::string($model, 'threeDSSessionData') ?? Data::string($model, 'ThreeDsSessionData'),
            threeDsCallbackId: Data::string($model, 'ThreeDsCallbackId') ?? Data::string($model, 'threeDsCallbackId'),
            raw: $model,
        );
    }

    /**
     * True when this is a 3DS v2 challenge (uses `creq` rather than `PaReq`).
     */
    public function isVersion2(): bool
    {
        return $this->creq !== null;
    }

    /**
     * The hidden form fields to POST to {@see $acsUrl}, keyed by field name.
     * `TermUrl` (your return URL) must be added by the caller.
     *
     * @return array<string, string>
     */
    public function formFields(): array
    {
        if ($this->isVersion2()) {
            return array_filter([
                'creq' => $this->creq,
                'threeDSSessionData' => $this->threeDsSessionData,
            ], static fn (?string $v): bool => $v !== null);
        }

        return array_filter([
            'PaReq' => $this->paReq,
            'MD' => $this->md,
        ], static fn (?string $v): bool => $v !== null);
    }
}
