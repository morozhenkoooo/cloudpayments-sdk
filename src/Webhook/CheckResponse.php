<?php

declare(strict_types=1);

namespace CloudPayments\Webhook;

use CloudPayments\Enum\CheckResponseCode;

/**
 * The body returned to CloudPayments in answer to a `Check` webhook.
 *
 * CloudPayments expects `{"code": N}` where N decides whether the payment may
 * proceed. Build it with the named constructors and emit it via {@see toJson()}.
 */
final readonly class CheckResponse
{
    public function __construct(public CheckResponseCode $code = CheckResponseCode::Ok)
    {
    }

    public static function ok(): self
    {
        return new self(CheckResponseCode::Ok);
    }

    public static function invalidAccountId(): self
    {
        return new self(CheckResponseCode::InvalidAccountId);
    }

    public static function cannotProcess(): self
    {
        return new self(CheckResponseCode::CannotProcess);
    }

    public static function rejected(): self
    {
        return new self(CheckResponseCode::Rejected);
    }

    /**
     * @return array{code: int}
     */
    public function toArray(): array
    {
        return ['code' => $this->code->value];
    }

    /**
     * @throws \JsonException
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
