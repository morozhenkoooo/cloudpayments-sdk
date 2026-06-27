<?php

declare(strict_types=1);

namespace CloudPayments\Exception;

/**
 * The gateway rejected the API credentials (HTTP 401). Check the Public ID and
 * API Secret and that they match the chosen gateway.
 */
final class AuthenticationException extends ApiException
{
}
