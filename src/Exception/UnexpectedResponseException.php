<?php

declare(strict_types=1);

namespace CloudPayments\Exception;

/**
 * The gateway responded with a body that could not be parsed as the expected
 * JSON envelope.
 */
final class UnexpectedResponseException extends \RuntimeException implements CloudPaymentsException
{
}
