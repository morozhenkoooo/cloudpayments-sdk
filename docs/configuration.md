# Configuration

## Credentials

Get your **Public ID** (`pk_…`) and **API Secret** from the CloudPayments
dashboard. The SDK sends them as HTTP Basic auth on every request.

```php
use CloudPayments\Client;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');
```

`Client::create()` is a shortcut. For full control, build the `Config` yourself:

```php
use CloudPayments\Client;
use CloudPayments\Config;
use CloudPayments\Gateway;

$config = new Config(
    publicId: 'pk_xxxxxxxx',
    apiSecret: 'your_api_secret',
    gateway: Gateway::Russia,   // default
    cultureName: 'ru-RU',       // default response message language
);

$client = new Client($config);
```

An empty `publicId` or `apiSecret` throws `InvalidArgumentException` immediately.

## Gateways

Pick the gateway matching the country your account is registered in. Both speak
the identical API; only the host differs.

| Case | Host |
|------|------|
| `Gateway::Russia` (default) | `https://api.cloudpayments.ru` |
| `Gateway::Kazakhstan` | `https://api.cloudpayments.kz` |

```php
$client = Client::create('pk_xxx', 'secret', Gateway::Kazakhstan);
```

## Culture (response language)

`cultureName` controls the language of human-readable `Message` text returned by
the API (e.g. `ru-RU`, `en-US`). It is sent automatically on form-encoded
requests. Per-request DTOs that expose a `cultureName` argument override it.

## Custom HTTP client

When you omit the HTTP client, it is auto-discovered. Pass one explicitly to set
timeouts, retries, proxies, logging middleware, etc. The `Client` constructor
accepts a PSR-18 client and PSR-17 factories:

```php
use CloudPayments\Client;
use CloudPayments\Config;
use GuzzleHttp\Client as GuzzleClient;
use Nyholm\Psr7\Factory\Psr17Factory;

$psr17 = new Psr17Factory();

$client = new Client(
    config: new Config('pk_xxx', 'secret'),
    httpClient: new GuzzleClient(['timeout' => 10]),
    requestFactory: $psr17,
    streamFactory: $psr17,
);
```

Because the transport depends only on `Psr\Http\Client\ClientInterface`, you can
wrap it in any PSR-18 decorator (retry, circuit breaker, logging) without
touching the SDK.

## Idempotency (`X-Request-ID`)

Every mutating call sends a unique `X-Request-ID`. CloudPayments deduplicates
requests carrying the same id for **one hour**, so a retry after a timeout
returns the original result instead of charging twice.

By default a random UUID is generated per call. You can:

**Pin the id for a specific call** — pass it as the last argument so your own
retry sends the same value:

```php
$requestId = 'order-42-attempt'; // stable for this logical operation
$client->payments()->charge($request, $requestId);
// retry with the SAME $requestId → no double charge
```

**Swap the generator globally** — implement `CloudPayments\Http\RequestIdGenerator`:

```php
use CloudPayments\Http\RequestIdGenerator;

final class UlidRequestId implements RequestIdGenerator
{
    public function generate(): string
    {
        return (string) new \Symfony\Component\Uid\Ulid();
    }
}

$client = new Client(
    config: new Config('pk_xxx', 'secret'),
    requestIdGenerator: new UlidRequestId(),
);
```

## Low-level escape hatch

For an endpoint not yet wrapped by a typed method, reach the transport directly:

```php
$envelope = $client->transport()->send('/payments/get', ['TransactionId' => 504]);
$envelope->success;  // bool
$envelope->model;    // array<array-key, mixed>
```

## Next

- [Payments](payments.md)
- [Webhooks](webhooks.md)
- [Error handling](error-handling.md)
