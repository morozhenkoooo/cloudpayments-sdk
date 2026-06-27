# Webhooks

CloudPayments POSTs notifications to URLs you register in the dashboard. Each URL
maps to exactly one kind of event — a `CloudPayments\Enum\NotificationType`. For
every request you must:

1. **Verify the HMAC signature** computed over the **raw, unparsed** request body.
2. **Reply HTTP 200** (the `Check` event additionally needs a JSON body — see below).

A non-200 response, a timeout, or a signature mismatch makes CloudPayments retry
the delivery. Access everything through `$client->webhooks()`, which returns a
`CloudPayments\Webhook\WebhookProcessor`.

## Signature verification

CloudPayments signs every webhook as `base64(HMAC-SHA256(rawBody, apiSecret))`
and sends it in one of two headers, newest first:

| Header | Notes |
|--------|-------|
| `X-Content-HMAC` | Modern header, prefer this |
| `Content-HMAC` | Legacy fallback, still sent by some integrations |

The HMAC is computed over the **exact raw bytes** of the request body. Read the
body before any framework parses or re-encodes it, or verification will fail.

`signatureFromHeaders()` pulls the signature out of a header map, trying both
names case-insensitively in priority order, and returns `null` if neither is
present:

```php
use CloudPayments\Client;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

// $headers is an array<string, string|list<string>> — e.g. from getallheaders()
$signature = $client->webhooks()->signatureFromHeaders($headers);
```

You rarely call verification directly: `parse()` verifies first, then decodes. It
throws `CloudPayments\Exception\InvalidSignatureException` when the signature is
missing, empty, or does not match the body. Comparison is constant-time.

## Parsing

`parse()` verifies the signature and returns the typed notification DTO for the
event:

```php
use CloudPayments\Client;
use CloudPayments\Enum\NotificationType;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

$notification = $client->webhooks()->parse(
    NotificationType::Pay,   // chosen by which route received the request
    $rawBody,                // raw request body bytes
    $signature,              // from signatureFromHeaders() or the header directly
);
```

Signature:

```php
public function parse(
    NotificationType $type,
    string $rawBody,
    ?string $signature,
): CloudPayments\Contract\Notification
```

You pick the `NotificationType` yourself, based on **which route the request hit** —
the payload does not reliably carry its own type, so map one endpoint per type
in your router. The body may arrive form-encoded or as JSON; `parse()`
auto-detects (JSON first, form-encoding fallback) and normalizes the keys.

Every returned DTO implements `CloudPayments\Contract\Notification` and exposes
`type(): NotificationType` plus a `public array $raw` escape hatch holding the
untouched decoded payload.

## Notification types

| `NotificationType` | DTO class | Key fields |
|--------------------|-----------|------------|
| `Check` | `Webhook\Notification\CheckNotification` | `transactionId`, `amount`, `currency`, `paymentAmount`, `accountId`, `invoiceId`, `subscriptionId`, `email`, `name`, `ipAddress`/`ipCountry`/`ipCity`, `testMode`, `card` (`Card`), `data` (`?array`), `raw` |
| `Pay` | `Webhook\Notification\PayNotification` | everything in `Check` plus `authCode`, `token`, `status` (`TransactionStatus`), `operationType`, `gatewayName`, `cardProduct`, `card` (`Card`), `data` (`?array`), `raw` |
| `Fail` | `Webhook\Notification\FailNotification` | `transactionId`, `amount`, `currency`, `accountId`, `reason`, `reasonCodeRaw` (`?int`), `reasonCode` (`?ReasonCode`), `status` (`TransactionStatus`), `card` (`Card`), `data` (`?array`), `raw` |
| `Confirm` | `Webhook\Notification\ConfirmNotification` | `transactionId`, `amount`, `paymentAmount`, `dateTime`, `invoiceId`, `accountId`, `subscriptionId`, `status` (`TransactionStatus`), `token`, `raw` |
| `Refund` | `Webhook\Notification\RefundNotification` | `transactionId`, `paymentTransactionId`, `amount`, `dateTime`, `invoiceId`, `accountId`, `raw` |
| `Cancel` | `Webhook\Notification\CancelNotification` | `transactionId`, `amount`, `dateTime`, `invoiceId`, `accountId`, `raw` |
| `Recurrent` | `Webhook\Notification\RecurrentNotification` | `id`, `accountId`, `description`, `email`, `amount`, `currency`, `interval`, `period`, `status` (`SubscriptionStatus`), `successfulTransactionsNumber`, `failedTransactionsNumber`, `maxPeriods`, `startDate`, `lastTransactionDate`, `nextTransactionDate`, `raw` |
| `Receipt` | `Webhook\Notification\ReceiptNotification` | `id`, `documentNumber`, `sessionNumber`, `type`, `sum`, `fn`, `fiscalSign`, `deviceSn`, `ofd`, `url`, `invoiceId`, `accountId`, `amount`, `raw` |

All classes live under the `CloudPayments\Webhook\Notification\` namespace. Most
scalar fields are nullable — CloudPayments only sends the keys relevant to the
event.

`CheckNotification` and `PayNotification` additionally expose a parsed
`CloudPayments\ValueObject\Card $card` (masked PAN details: `firstSix`,
`lastFour`, `type`, `expDate`, `product`, `issuer`, `issuerBankCountry`, plus a
`maskedNumber()` helper) and a decoded `?array $data` holding the JSON you passed
in the payment widget's `data` field.

```php
use CloudPayments\Client;
use CloudPayments\Enum\NotificationType;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

$pay = $client->webhooks()->parse(NotificationType::Pay, $rawBody, $signature);

$pay->transactionId;       // int|null
$pay->amount;              // float|null
$pay->card->maskedNumber(); // "424242******4242"
$pay->data['order_id'] ?? null; // your custom widget data
```

## The `Check` flow

The `Check` event is a pre-authorization gate: CloudPayments asks whether the
payment may proceed, and **your JSON answer decides the outcome**. Build it with
`CloudPayments\Webhook\CheckResponse` and emit it via `toJson()`:

```php
use CloudPayments\Client;
use CloudPayments\Enum\NotificationType;
use CloudPayments\Webhook\CheckResponse;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

$check = $client->webhooks()->parse(NotificationType::Check, $rawBody, $signature);

$response = $orderExists($check->invoiceId)
    ? CheckResponse::ok()
    : CheckResponse::invalidAccountId();

echo $response->toJson(); // {"code":0}
```

The named constructors map to `CloudPayments\Enum\CheckResponseCode`:

| Constructor | Code | Meaning |
|-------------|------|---------|
| `CheckResponse::ok()` | `0` | Approve — proceed with the payment |
| `CheckResponse::invalidAccountId()` | `11` | Account / customer does not exist |
| `CheckResponse::cannotProcess()` | `12` | Cannot process right now (retry later) |
| `CheckResponse::rejected()` | `13` | Reject the payment |

`toJson()` serializes `{"code": N}`; `toArray()` gives the same shape as a PHP
array. The default constructor (`new CheckResponse()`) is `code 0`.

**Every other notification type just needs HTTP 200** — no body required. Do your
side effects (mark the order paid on `Pay`, flag the decline on `Fail`, update
the subscription on `Recurrent`, store the fiscal receipt URL on `Receipt`, etc.)
and return an empty 200.

## Framework integration

The pattern is identical everywhere: read the **raw** body and the signature
header, then `parse()` inside a `try`/`catch` for `InvalidSignatureException` and
reject with `401` on failure.

### Plain PHP

```php
use CloudPayments\Client;
use CloudPayments\Enum\NotificationType;
use CloudPayments\Exception\InvalidSignatureException;
use CloudPayments\Webhook\CheckResponse;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

$rawBody   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_CONTENT_HMAC'] ?? $_SERVER['HTTP_CONTENT_HMAC'] ?? null;

try {
    $check = $client->webhooks()->parse(NotificationType::Check, $rawBody, $signature);
} catch (InvalidSignatureException) {
    http_response_code(401);
    exit;
}

header('Content-Type: application/json');
echo CheckResponse::ok()->toJson();
```

For a non-`Check` route, return an empty 200 instead:

```php
use CloudPayments\Enum\NotificationType;
use CloudPayments\Exception\InvalidSignatureException;

try {
    $pay = $client->webhooks()->parse(NotificationType::Pay, $rawBody, $signature);
} catch (InvalidSignatureException) {
    http_response_code(401);
    exit;
}

markOrderPaid($pay->invoiceId, $pay->transactionId);
http_response_code(200); // empty body is fine
```

### Symfony

```php
use CloudPayments\Client;
use CloudPayments\Enum\NotificationType;
use CloudPayments\Exception\InvalidSignatureException;
use CloudPayments\Webhook\CheckResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CloudPaymentsWebhookController
{
    public function __construct(private readonly Client $client)
    {
    }

    #[Route('/webhooks/cloudpayments/check', methods: ['POST'])]
    public function check(Request $request): Response
    {
        $rawBody   = $request->getContent();
        $signature = $request->headers->get('X-Content-HMAC');

        try {
            $check = $this->client->webhooks()->parse(
                NotificationType::Check,
                $rawBody,
                $signature,
            );
        } catch (InvalidSignatureException) {
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse(CheckResponse::ok()->toArray());
    }

    #[Route('/webhooks/cloudpayments/pay', methods: ['POST'])]
    public function pay(Request $request): Response
    {
        try {
            $pay = $this->client->webhooks()->parse(
                NotificationType::Pay,
                $request->getContent(),
                $request->headers->get('X-Content-HMAC'),
            );
        } catch (InvalidSignatureException) {
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        // ... mark $pay->invoiceId paid ...

        return new Response('', Response::HTTP_OK);
    }
}
```

### Laravel

```php
use CloudPayments\Client;
use CloudPayments\Enum\NotificationType;
use CloudPayments\Exception\InvalidSignatureException;
use CloudPayments\Webhook\CheckResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CloudPaymentsWebhookController
{
    public function __construct(private readonly Client $client)
    {
    }

    public function check(Request $request): Response
    {
        $rawBody   = $request->getContent();
        $signature = $request->header('X-Content-HMAC');

        try {
            $check = $this->client->webhooks()->parse(
                NotificationType::Check,
                $rawBody,
                $signature,
            );
        } catch (InvalidSignatureException) {
            return response('', 401);
        }

        return response()->json(CheckResponse::ok()->toArray());
    }

    public function pay(Request $request): Response
    {
        try {
            $pay = $this->client->webhooks()->parse(
                NotificationType::Pay,
                $request->getContent(),
                $request->header('X-Content-HMAC'),
            );
        } catch (InvalidSignatureException) {
            return response('', 401);
        }

        // ... mark $pay->invoiceId paid ...

        return response('', 200);
    }
}
```

> On Laravel, exclude these routes from CSRF protection and avoid middleware that
> mutates the request body — the signature is over the raw bytes. `getContent()`
> returns those bytes untouched.

## Security notes

- **Replay protection.** A valid signature proves authenticity and integrity, but
  not freshness — the same signed body replayed later still verifies. Make your
  handlers idempotent: key side effects on `transactionId` (or `invoiceId`) and
  ignore an event you have already processed.
- **Don't log the raw payload.** Every notification exposes `$notification->raw`
  (and `Card`, `email`, IP, token fields) with the full wire data. Avoid dumping
  the whole object into persistent logs. `Config` already redacts the API secret
  from `var_dump`/debug output via `__debugInfo()`.
- **Verify before you act.** Always call `parse()` (which verifies) — never read
  the body first and verify "later". The signature must be checked over the exact
  raw bytes before any business logic runs.

## See also

- [Payments](payments.md) — `Pay`/`Fail`/`Confirm` correspond to charge outcomes
- [Subscriptions](subscriptions.md) — the `Recurrent` notification
- [Configuration](configuration.md) — credentials and the API secret used to sign
- [Error handling](error-handling.md) — `InvalidSignatureException` in the exception hierarchy
