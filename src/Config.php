<?php

declare(strict_types=1);

namespace CloudPayments;

use CloudPayments\Webhook\SignatureValidator;

/**
 * Immutable client configuration: API credentials and the gateway to talk to.
 *
 * The API secret is private — it is never exposed as a property and is scrubbed
 * from {@see Config::__debugInfo()} so it cannot leak into var_dump output,
 * logger context, or error trackers.
 */
final readonly class Config
{
    public function __construct(
        public string $publicId,
        private string $apiSecret,
        public Gateway $gateway = Gateway::Russia,
        /** Default response message language passed as CultureName. */
        public string $cultureName = 'ru-RU',
    ) {
        if ($publicId === '') {
            throw new \InvalidArgumentException('CloudPayments publicId must not be empty.');
        }

        if ($apiSecret === '') {
            throw new \InvalidArgumentException('CloudPayments apiSecret must not be empty.');
        }
    }

    public function baseUrl(): string
    {
        return $this->gateway->baseUrl();
    }

    public function basicAuthHeader(): string
    {
        return 'Basic ' . base64_encode($this->publicId . ':' . $this->apiSecret);
    }

    /**
     * Build a webhook signature validator bound to this secret, so callers never
     * have to read the raw secret out of the config.
     */
    public function createSignatureValidator(): SignatureValidator
    {
        return new SignatureValidator($this->apiSecret);
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return [
            'publicId' => $this->publicId,
            'apiSecret' => '***redacted***',
            'gateway' => $this->gateway->name,
            'cultureName' => $this->cultureName,
        ];
    }
}
