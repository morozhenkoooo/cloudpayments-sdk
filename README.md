# CloudPayments PHP SDK

Modern, strictly-typed PHP **8.3+** SDK for the [CloudPayments](https://cloudpayments.ru) payment gateway.

- ✅ **Immutable DTOs** for every request and response — no loose arrays
- ✅ **Native PHP enums** for statuses, currencies, reason codes, 54-FZ dictionaries
- ✅ **PSR-18 / PSR-17** HTTP layer — bring your own client (Guzzle, Symfony HttpClient, …) via auto-discovery
- ✅ **Full API coverage**: payments, two-stage (auth/confirm/void), refunds, card **tokens**, **3-D Secure**, **subscriptions**, **54-FZ receipts**, **payouts** (CardPayout), Apple Pay / Google Pay
- ✅ **Webhooks**: HMAC signature verification + typed notification objects
- ✅ **Idempotent** mutating calls via auto-generated `X-Request-ID`
- ✅ PHPStan **level max**, framework-agnostic

> Why another library? Existing PHP packages are either abandoned (PHP 5.x-era thin array wrappers) or hardcode Guzzle, use userland enums, and miss receipts/payouts. This one is built on modern PHP with full coverage.

## Installation

```bash
composer require morozhenkoooo/cloudpayments-sdk
```

You also need any PSR-18 HTTP client and PSR-17 factories. If you don't have one:

```bash
composer require guzzlehttp/guzzle nyholm/psr7
```

## Quick start

```php
use CloudPayments\Client;
use CloudPayments\Enum\Currency;
use CloudPayments\Request\Payment\CardPaymentRequest;
use CloudPayments\Response\Secure3DS;
use CloudPayments\ValueObject\Amount;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

$result = $client->payments()->charge(new CardPaymentRequest(
    amount: Amount::of('1000.00'),
    ipAddress: $request->ip(),
    cardCryptogramPacket: $cryptogram, // from the CloudPayments widget
    currency: Currency::RUB,
    invoiceId: 'ORDER-42',
    accountId: 'user-7',
    email: 'buyer@example.com',
));

if ($result instanceof Secure3DS) {
    // Redirect the cardholder to $result->acsUrl, POSTing $result->formFields() + your TermUrl
} elseif ($result->isCompleted()) {
    // Payment done — persist $result->transactionId, $result->token
} elseif ($result->isDeclined()) {
    // $result->reasonCode / $result->cardHolderMessage
}
```

### Two-stage payment

```php
$auth = $client->payments()->auth($cardPaymentRequest);          // hold funds
$client->payments()->confirm(new ConfirmRequest($auth->transactionId, Amount::of('1000.00'))); // capture
// or release:
$client->payments()->void($auth->transactionId);
```

### 3-D Secure completion

```php
use CloudPayments\Request\Payment\Post3dsRequest;

$tx = $client->payments()->post3ds(new Post3dsRequest(
    transactionId: (int) $_POST['MD'],
    paRes: $_POST['PaRes'],
));
```

### Recurring by saved token

```php
use CloudPayments\Request\Payment\TokenPaymentRequest;

$client->payments()->chargeToken(new TokenPaymentRequest(
    amount: Amount::of('299.00'),
    accountId: 'user-7',
    token: $savedToken,
));
```

### Subscriptions

```php
use CloudPayments\Enum\Interval;
use CloudPayments\Request\Subscription\CreateSubscriptionRequest;

$subscription = $client->subscriptions()->create(new CreateSubscriptionRequest(
    token: $savedToken,
    accountId: 'user-7',
    description: 'Pro plan',
    email: 'buyer@example.com',
    amount: Amount::of('990.00'),
    startDate: new DateTimeImmutable('+1 month'),
    interval: Interval::Month,
    period: 1,
));
```

### 54-FZ receipt

```php
use CloudPayments\Enum\{ReceiptType, TaxationSystem, VatRate};
use CloudPayments\Request\Receipt\{CreateReceiptRequest, CustomerReceipt, ReceiptItem};

$client->receipts()->create(new CreateReceiptRequest(
    type: ReceiptType::Income,
    customerReceipt: new CustomerReceipt(
        items: [new ReceiptItem('Pro plan', price: 990.0, quantity: 1.0, amount: 990.0, vat: VatRate::Vat20)],
        taxationSystem: TaxationSystem::SimplifiedIncome,
        email: 'buyer@example.com',
    ),
    invoiceId: 'ORDER-42',
));
```

### Payout to a card

```php
use CloudPayments\Request\Payout\TokenPayoutRequest;

$client->payouts()->toToken(new TokenPayoutRequest(
    token: $savedToken,
    amount: Amount::of('500.00'),
    accountId: 'user-7',
));
```

## Webhooks

Verify the HMAC signature over the **raw** request body and get a typed notification:

```php
use CloudPayments\Enum\NotificationType;
use CloudPayments\Exception\InvalidSignatureException;
use CloudPayments\Webhook\CheckResponse;
use CloudPayments\Webhook\Notification\PayNotification;

$rawBody   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_CONTENT_HMAC'] ?? $_SERVER['HTTP_X_CONTENT_HMAC'] ?? null;

try {
    $notification = $client->webhooks()->parse(NotificationType::Pay, $rawBody, $signature);
} catch (InvalidSignatureException) {
    http_response_code(401);
    return;
}

// For a Check webhook, reply with a business decision:
header('Content-Type: application/json');
echo CheckResponse::ok()->toJson();        // {"code":0}  — or rejected(), invalidAccountId(), cannotProcess()
```

Each `NotificationType` maps to a typed DTO (`PayNotification`, `FailNotification`, `RefundNotification`, `RecurrentNotification`, `ReceiptNotification`, …).

## Configuration

```php
use CloudPayments\{Client, Config, Gateway};

// Kazakhstan gateway + custom PSR-18 client:
$client = new Client(
    new Config('pk_xxx', 'secret', Gateway::Kazakhstan, cultureName: 'en-US'),
    httpClient: $myPsr18Client,
);
```

## Error handling

All exceptions implement `CloudPayments\Exception\CloudPaymentsException`:

| Exception | When |
|---|---|
| `TransportException` | network/HTTP transport failure |
| `AuthenticationException` | HTTP 401 — bad credentials |
| `ApiException` | API reported a failure (bad params, unknown entity) |
| `ValidationException` | invalid input caught locally |
| `InvalidSignatureException` | webhook HMAC mismatch |
| `UnexpectedResponseException` | unparseable response body |

> Card **declines are not exceptions** — they come back as a `Transaction` with `status === TransactionStatus::Declined` and a `reasonCode`.

## Development

```bash
composer install
composer check   # php-cs-fixer (dry-run) + phpstan + phpunit
```

## License

MIT
