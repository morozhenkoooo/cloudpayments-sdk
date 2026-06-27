<?php

declare(strict_types=1);

namespace CloudPayments\ValueObject;

use CloudPayments\Exception\ValidationException;

/**
 * A monetary amount stored as a decimal string to avoid binary-float rounding
 * errors. Serializes to the plain decimal form CloudPayments expects
 * (e.g. `"1000.00"`, `"99.9"`).
 */
final readonly class Amount implements \Stringable
{
    private function __construct(public string $value)
    {
    }

    public static function of(int|float|string $value): self
    {
        if (\is_int($value)) {
            return new self((string) $value);
        }

        if (\is_float($value)) {
            if (!is_finite($value)) {
                throw new ValidationException('Amount must be a finite number.');
            }

            return new self(rtrim(rtrim(\sprintf('%.2f', $value), '0'), '.') ?: '0');
        }

        $normalized = trim($value);

        if (preg_match('/^\d+(\.\d+)?$/', $normalized) !== 1) {
            throw new ValidationException(\sprintf('Invalid amount "%s": expected a non-negative decimal.', $value));
        }

        return new self($normalized);
    }

    public function toFloat(): float
    {
        return (float) $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
