<?php

declare(strict_types=1);

namespace CloudPayments\Tests\Support;

use CloudPayments\Config;
use CloudPayments\Http\RequestIdGenerator;
use CloudPayments\Http\Transport;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;

/**
 * Builds a {@see Transport} backed by an in-memory PSR-18 mock client so tests
 * can queue canned responses and inspect the requests that were sent.
 */
final class MockHttp
{
    public readonly MockClient $client;
    public readonly Transport $transport;
    public readonly Psr17Factory $factory;

    public function __construct(?Config $config = null)
    {
        $this->client = new MockClient();
        $this->factory = new Psr17Factory();

        $this->transport = new Transport(
            $config ?? new Config('pk_test', 'secret_test'),
            $this->client,
            $this->factory,
            $this->factory,
            new class () implements RequestIdGenerator {
                public function generate(): string
                {
                    return 'fixed-request-id';
                }
            },
        );
    }

    /**
     * Queue a JSON response body with the given HTTP status.
     */
    public function queueJson(string $json, int $status = 200): void
    {
        $response = $this->factory->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream($json));

        $this->client->addResponse($response);
    }

    /**
     * @param array<string, mixed> $model
     */
    public function queueModel(array $model, bool $success = true, ?string $message = null): void
    {
        $this->queueJson((string) json_encode([
            'Success' => $success,
            'Message' => $message,
            'Model' => $model,
        ]));
    }

    public function lastRequest(): RequestInterface
    {
        $request = $this->client->getLastRequest();

        if (!$request instanceof RequestInterface) {
            throw new \RuntimeException('No request has been sent through the mock client.');
        }

        return $request;
    }

    public function lastRequestBody(): string
    {
        return (string) $this->lastRequest()->getBody();
    }

    /**
     * @return array<string, string>
     */
    public function lastRequestParams(): array
    {
        parse_str($this->lastRequestBody(), $params);

        $result = [];

        foreach ($params as $key => $value) {
            if (\is_string($value)) {
                $result[(string) $key] = $value;
            }
        }

        return $result;
    }
}
