<?php

declare(strict_types=1);

namespace CloudPayments\Enum;

/**
 * Lifecycle status of a recurring subscription.
 */
enum SubscriptionStatus: string
{
    case Active = 'Active';
    case PastDue = 'PastDue';
    case Cancelled = 'Cancelled';
    case Rejected = 'Rejected';
    case Expired = 'Expired';

    public function code(): int
    {
        return match ($this) {
            self::Active => 0,
            self::PastDue => 1,
            self::Cancelled => 2,
            self::Rejected => 3,
            self::Expired => 4,
        };
    }

    public static function fromCode(int $code): ?self
    {
        return match ($code) {
            0 => self::Active,
            1 => self::PastDue,
            2 => self::Cancelled,
            3 => self::Rejected,
            4 => self::Expired,
            default => null,
        };
    }

    public static function resolve(?string $status, ?int $code): ?self
    {
        if ($status !== null && ($resolved = self::tryFrom($status)) !== null) {
            return $resolved;
        }

        return $code !== null ? self::fromCode($code) : null;
    }
}
