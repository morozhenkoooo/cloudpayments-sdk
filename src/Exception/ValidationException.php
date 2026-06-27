<?php

declare(strict_types=1);

namespace CloudPayments\Exception;

/**
 * A request DTO or value object was constructed with invalid input, caught
 * locally before any HTTP call is made.
 */
final class ValidationException extends \InvalidArgumentException implements CloudPaymentsException
{
}
