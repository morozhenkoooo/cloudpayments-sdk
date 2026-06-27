<?php

declare(strict_types=1);

namespace CloudPayments\Http;

/**
 * Request body encoding. Most endpoints accept form-encoded bodies; the
 * 54-FZ receipt (`/kkt/*`) endpoints require JSON.
 */
enum BodyFormat
{
    case Form;
    case Json;

    public function contentType(): string
    {
        return match ($this) {
            self::Form => 'application/x-www-form-urlencoded',
            self::Json => 'application/json',
        };
    }
}
