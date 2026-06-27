<?php

declare(strict_types=1);

namespace CloudPayments\Contract;

use CloudPayments\Enum\NotificationType;

/**
 * A typed inbound webhook notification.
 */
interface Notification
{
    public function type(): NotificationType;
}
