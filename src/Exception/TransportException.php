<?php

declare(strict_types=1);

namespace CloudPayments\Exception;

/**
 * The HTTP request never produced a usable response (network error, DNS,
 * timeout, etc.). Wraps the underlying PSR-18 client exception.
 */
final class TransportException extends \RuntimeException implements CloudPaymentsException
{
}
