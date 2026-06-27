<?php

declare(strict_types=1);

namespace CloudPayments;

use CloudPayments\Api\PaymentsApi;
use CloudPayments\Api\PayoutsApi;
use CloudPayments\Api\ReceiptsApi;
use CloudPayments\Api\SubscriptionsApi;
use CloudPayments\Http\RandomRequestIdGenerator;
use CloudPayments\Http\RequestIdGenerator;
use CloudPayments\Http\Transport;
use CloudPayments\Webhook\SignatureValidator;
use CloudPayments\Webhook\WebhookProcessor;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Entry point to the CloudPayments SDK.
 *
 * The HTTP client and PSR-17 factories are optional — when omitted they are
 * auto-discovered via php-http/discovery, so any PSR-18 client installed in the
 * project is used. Pass them explicitly to control timeouts, middleware, etc.
 *
 * ```php
 * $client = Client::create('pk_xxx', 'api_secret');
 * $result = $client->payments()->charge($request);
 * ```
 */
final class Client
{
    private readonly Transport $transport;

    private ?PaymentsApi $payments = null;
    private ?SubscriptionsApi $subscriptions = null;
    private ?ReceiptsApi $receipts = null;
    private ?PayoutsApi $payouts = null;
    private ?WebhookProcessor $webhooks = null;

    public function __construct(
        private readonly Config $config,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?RequestIdGenerator $requestIdGenerator = null,
    ) {
        $this->transport = new Transport(
            $config,
            $httpClient ?? Psr18ClientDiscovery::find(),
            $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory(),
            $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory(),
            $requestIdGenerator ?? new RandomRequestIdGenerator(),
        );
    }

    public static function create(
        string $publicId,
        string $apiSecret,
        Gateway $gateway = Gateway::Russia,
    ): self {
        return new self(new Config($publicId, $apiSecret, $gateway));
    }

    public function payments(): PaymentsApi
    {
        return $this->payments ??= new PaymentsApi($this->transport);
    }

    public function subscriptions(): SubscriptionsApi
    {
        return $this->subscriptions ??= new SubscriptionsApi($this->transport);
    }

    public function receipts(): ReceiptsApi
    {
        return $this->receipts ??= new ReceiptsApi($this->transport);
    }

    public function payouts(): PayoutsApi
    {
        return $this->payouts ??= new PayoutsApi($this->transport);
    }

    public function webhooks(): WebhookProcessor
    {
        return $this->webhooks ??= new WebhookProcessor(new SignatureValidator($this->config->apiSecret));
    }

    /**
     * Low-level transport, for endpoints not yet wrapped by a typed method.
     */
    public function transport(): Transport
    {
        return $this->transport;
    }
}
