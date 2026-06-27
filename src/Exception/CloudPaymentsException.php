<?php

declare(strict_types=1);

namespace CloudPayments\Exception;

/**
 * Marker interface implemented by every exception thrown by this SDK, so
 * callers can catch all of them with a single `catch (CloudPaymentsException)`.
 */
interface CloudPaymentsException extends \Throwable
{
}
