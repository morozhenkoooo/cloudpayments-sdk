<?php

declare(strict_types=1);

namespace CloudPayments\Enum;

/**
 * Recurring subscription billing interval unit.
 */
enum Interval: string
{
    case Day = 'Day';
    case Week = 'Week';
    case Month = 'Month';
}
