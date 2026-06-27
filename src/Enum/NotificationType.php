<?php

declare(strict_types=1);

namespace CloudPayments\Enum;

/**
 * Inbound webhook notification kinds configured in the CloudPayments dashboard.
 */
enum NotificationType: string
{
    case Check = 'check';
    case Pay = 'pay';
    case Fail = 'fail';
    case Confirm = 'confirm';
    case Refund = 'refund';
    case Cancel = 'cancel';
    case Recurrent = 'recurrent';
    case Receipt = 'receipt';
}
