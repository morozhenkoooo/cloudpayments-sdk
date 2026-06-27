<?php

declare(strict_types=1);

namespace CloudPayments\Webhook;

use CloudPayments\Contract\Notification;
use CloudPayments\Enum\NotificationType;
use CloudPayments\Exception\InvalidSignatureException;
use CloudPayments\Webhook\Notification\CancelNotification;
use CloudPayments\Webhook\Notification\CheckNotification;
use CloudPayments\Webhook\Notification\ConfirmNotification;
use CloudPayments\Webhook\Notification\FailNotification;
use CloudPayments\Webhook\Notification\PayNotification;
use CloudPayments\Webhook\Notification\ReceiptNotification;
use CloudPayments\Webhook\Notification\RecurrentNotification;
use CloudPayments\Webhook\Notification\RefundNotification;

/**
 * Verifies and parses inbound CloudPayments webhooks into typed notifications.
 *
 * Always feed it the exact raw request body bytes — the HMAC signature is
 * computed over them, so any re-encoding would break verification.
 */
final readonly class WebhookProcessor
{
    public function __construct(private SignatureValidator $signatureValidator)
    {
    }

    /**
     * Extract the signature from request headers, trying the known header names
     * case-insensitively in priority order.
     *
     * @param array<string, string|list<string>> $headers
     */
    public function signatureFromHeaders(array $headers): ?string
    {
        foreach (SignatureValidator::HEADERS as $name) {
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, $name) !== 0) {
                    continue;
                }

                return \is_array($value) ? ($value[0] ?? null) : $value;
            }
        }

        return null;
    }

    /**
     * @throws InvalidSignatureException if the signature does not match the body
     */
    public function verify(string $rawBody, ?string $signature): void
    {
        if (!$this->signatureValidator->isValid($rawBody, $signature)) {
            throw new InvalidSignatureException('Invalid CloudPayments webhook signature.');
        }

        // Verified.
    }

    /**
     * Verify and decode a webhook into its typed notification DTO.
     *
     * @throws InvalidSignatureException if the signature does not match the body
     */
    public function parse(NotificationType $type, string $rawBody, ?string $signature): Notification
    {
        $this->verify($rawBody, $signature);

        $data = $this->decodeBody($rawBody);

        return match ($type) {
            NotificationType::Check => CheckNotification::fromArray($data),
            NotificationType::Pay => PayNotification::fromArray($data),
            NotificationType::Fail => FailNotification::fromArray($data),
            NotificationType::Confirm => ConfirmNotification::fromArray($data),
            NotificationType::Refund => RefundNotification::fromArray($data),
            NotificationType::Cancel => CancelNotification::fromArray($data),
            NotificationType::Recurrent => RecurrentNotification::fromArray($data),
            NotificationType::Receipt => ReceiptNotification::fromArray($data),
        };
    }

    /**
     * Decode a raw webhook body: JSON first, falling back to form-encoding.
     *
     * @return array<string, mixed>
     */
    private function decodeBody(string $rawBody): array
    {
        $decoded = json_decode($rawBody, true);

        if (!\is_array($decoded)) {
            parse_str($rawBody, $decoded);
        }

        $normalized = [];

        foreach ($decoded as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }
}
