<?php

declare(strict_types=1);

namespace CloudPayments\Exception;

/**
 * An inbound webhook failed HMAC signature verification and must be rejected.
 */
final class InvalidSignatureException extends \RuntimeException implements CloudPaymentsException
{
}
