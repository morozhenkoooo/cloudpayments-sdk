<?php

declare(strict_types=1);

namespace CloudPayments\Support;

/**
 * Helpers for building outbound request payloads.
 */
final class Payload
{
    /**
     * Recursively drop null values so optional fields are simply omitted from
     * the request body (CloudPayments treats absent and explicit-null
     * differently for some fields).
     *
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    public static function filterNulls(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (\is_array($value)) {
                $value = self::filterNulls($value);
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Encode an associative array as the JSON string CloudPayments expects for
     * `JsonData`-style fields inside a form-encoded body. Returns null for null.
     *
     * @param array<array-key, mixed>|null $data
     */
    public static function json(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }

        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
