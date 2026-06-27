<?php

declare(strict_types=1);

namespace CloudPayments\Http;

use CloudPayments\Config;
use CloudPayments\Contract\ApiRequest;
use CloudPayments\Exception\ApiException;
use CloudPayments\Exception\AuthenticationException;
use CloudPayments\Exception\TransportException;
use CloudPayments\Exception\UnexpectedResponseException;
use CloudPayments\Support\Payload;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * The single transport used by every API call: builds a POST request with
 * HTTP Basic auth and an `X-Request-ID`, sends it through a PSR-18 client, and
 * decodes the `{ Success, Message, Model }` envelope.
 *
 * It never decides whether a business operation succeeded — it only fails on
 * transport errors, bad credentials, and unparseable responses. Interpreting
 * the envelope is left to the API layer.
 */
final readonly class Transport
{
    /** Reject suspiciously large response bodies to avoid memory exhaustion. */
    private const int MAX_RESPONSE_BYTES = 4 * 1024 * 1024;

    /** Cap JSON nesting depth; CloudPayments responses are shallow. */
    private const int MAX_JSON_DEPTH = 32;

    public function __construct(
        private Config $config,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private RequestIdGenerator $requestIdGenerator,
    ) {
    }

    /**
     * @param ApiRequest|array<string, mixed> $request
     */
    public function send(
        string $path,
        ApiRequest|array $request = [],
        BodyFormat $format = BodyFormat::Form,
        ?string $requestId = null,
    ): Envelope {
        $payload = $request instanceof ApiRequest ? $request->toArray() : $request;
        $payload = Payload::filterNulls($payload);

        if ($format === BodyFormat::Form && !isset($payload['CultureName'])) {
            $payload['CultureName'] = $this->config->cultureName;
        }

        $httpRequest = $this->requestFactory
            ->createRequest('POST', $this->config->baseUrl() . $path)
            ->withHeader('Authorization', $this->config->basicAuthHeader())
            ->withHeader('Content-Type', $format->contentType())
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-Request-ID', $requestId ?? $this->requestIdGenerator->generate())
            ->withBody($this->streamFactory->createStream($this->encode($payload, $format)));

        try {
            $response = $this->httpClient->sendRequest($httpRequest);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException(
                \sprintf('CloudPayments request to "%s" failed: %s', $path, $e->getMessage()),
                previous: $e,
            );
        }

        $status = $response->getStatusCode();

        $size = $response->getBody()->getSize();
        if ($size !== null && $size > self::MAX_RESPONSE_BYTES) {
            throw new UnexpectedResponseException(
                \sprintf('CloudPayments response body is too large (%d bytes).', $size),
            );
        }

        $body = (string) $response->getBody();

        if ($status === 401) {
            throw new AuthenticationException('CloudPayments rejected the API credentials.', $status);
        }

        if ($status >= 500) {
            throw new ApiException(
                \sprintf('CloudPayments returned HTTP %d for "%s".', $status, $path),
                $status,
            );
        }

        return $this->decode($body, $status);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private function encode(array $payload, BodyFormat $format): string
    {
        if ($format === BodyFormat::Json) {
            return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        return http_build_query(self::stringifyBooleans($payload));
    }

    /**
     * CloudPayments' form binder expects `true`/`false` literals, not the
     * `1`/`0` that http_build_query would otherwise produce for booleans.
     *
     * @param array<array-key, mixed> $payload
     *
     * @return array<array-key, mixed>
     */
    private static function stringifyBooleans(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (\is_bool($value)) {
                $payload[$key] = $value ? 'true' : 'false';
            } elseif (\is_array($value)) {
                $payload[$key] = self::stringifyBooleans($value);
            }
        }

        return $payload;
    }

    private function decode(string $body, int $status): Envelope
    {
        if ($body === '') {
            return new Envelope(false, null, [], $status, []);
        }

        try {
            $decoded = json_decode($body, true, self::MAX_JSON_DEPTH, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new UnexpectedResponseException(
                'CloudPayments returned a non-JSON response: ' . $e->getMessage(),
                previous: $e,
            );
        }

        if (!\is_array($decoded)) {
            throw new UnexpectedResponseException('CloudPayments returned an unexpected JSON shape.');
        }

        $model = $decoded['Model'] ?? [];
        $message = $decoded['Message'] ?? null;

        return new Envelope(
            success: (bool) ($decoded['Success'] ?? false),
            message: \is_scalar($message) ? (string) $message : null,
            model: \is_array($model) ? $model : [],
            httpStatusCode: $status,
            raw: $decoded,
        );
    }
}
