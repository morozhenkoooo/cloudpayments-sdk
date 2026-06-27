<?php

declare(strict_types=1);

namespace CloudPayments;

/**
 * Immutable client configuration: API credentials and the gateway to talk to.
 */
final readonly class Config
{
    public function __construct(
        public string $publicId,
        public string $apiSecret,
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
}
