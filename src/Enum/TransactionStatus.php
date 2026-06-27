<?php

declare(strict_types=1);

namespace CloudPayments\Enum;

/**
 * Lifecycle status of a payment transaction. CloudPayments reports both a
 * textual `Status` and a numeric `StatusCode`; this enum bridges them.
 */
enum TransactionStatus: string
{
    case AwaitingAuthentication = 'AwaitingAuthentication';
    case Authorized = 'Authorized';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
    case Declined = 'Declined';

    public function code(): int
    {
        return match ($this) {
            self::AwaitingAuthentication => 1,
            self::Authorized => 2,
            self::Completed => 3,
            self::Cancelled => 4,
            self::Declined => 5,
        };
    }

    public static function fromCode(int $code): ?self
    {
        return match ($code) {
            1 => self::AwaitingAuthentication,
            2 => self::Authorized,
            3 => self::Completed,
            4 => self::Cancelled,
            5 => self::Declined,
            default => null,
        };
    }

    /**
     * Resolve from whatever the API provided: the textual status first, then
     * the numeric code as a fallback.
     */
    public static function resolve(?string $status, ?int $code): ?self
    {
        if ($status !== null && ($resolved = self::tryFrom($status)) !== null) {
            return $resolved;
        }

        return $code !== null ? self::fromCode($code) : null;
    }
}
