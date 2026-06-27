<?php

declare(strict_types=1);

namespace CloudPayments\Support;

/**
 * Null-safe, type-coercing reader for CloudPayments response Model arrays.
 *
 * CloudPayments is loosely typed over the wire (numbers as strings, missing
 * keys, etc.), so response DTOs hydrate through these helpers instead of
 * touching the raw array directly.
 */
final class Data
{
    /**
     * @param array<array-key, mixed> $data
     */
    public static function string(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return \is_scalar($value) ? (string) $value : null;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function requireString(array $data, string $key): string
    {
        return self::string($data, $key) ?? '';
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function int(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function float(array $data, string $key): ?float
    {
        $value = $data[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function bool(array $data, string $key): ?bool
    {
        $value = $data[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (\is_bool($value)) {
            return $value;
        }

        if (\is_string($value)) {
            return \in_array(strtolower($value), ['true', '1', 'yes'], true);
        }

        return (bool) $value;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    public static function array(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return \is_array($value) ? $value : [];
    }

    /**
     * Parse a CloudPayments date string (ISO 8601 or `/Date(…)/` legacy form).
     *
     * @param array<array-key, mixed> $data
     */
    public static function dateTime(array $data, string $key): ?\DateTimeImmutable
    {
        $value = self::string($data, $key);

        if ($value === null) {
            return null;
        }

        if (preg_match('#/Date\((\d+)#', $value, $m) === 1) {
            return (new \DateTimeImmutable())->setTimestamp((int) ((int) $m[1] / 1000));
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
