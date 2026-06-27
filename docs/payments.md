# Payments

All payment operations live on `$client->payments()` (a `CloudPayments\Api\PaymentsApi`).
Amounts are passed as `CloudPayments\ValueObject\Amount` to avoid float rounding.

> **A decline is not an exception.** `charge()`/`auth()` return a `Transaction`
> with `TransactionStatus::Declined` for soft failures (insufficient funds, fraud,
> bad CVV). Only transport/auth/validation problems throw. See
> [Error handling](error-handling.md).

## Single-stage charge (cryptogram)

Authorize and capture in one call. The `cardCryptogramPacket` is produced
client-side by the CloudPayments JS widget and posted to your server.

```php
use CloudPayments\Enum\Currency;
use CloudPayments\Request\Payment\CardPaymentRequest;
use CloudPayments\Response\Secure3DS;
use CloudPayments\ValueObject\Amount;

$result = $client->payments()->charge(new CardPaymentRequest(
    amount: Amount::of('1000.00'),
    ipAddress: $_SERVER['REMOTE_ADDR'],
    cardCryptogramPacket: $cryptogram,   // from the CloudPayments widget
    currency: Currency::RUB,
    invoiceId: 'order-42',
    description: 'Order #42',
    accountId: 'user-7',                 // required if you want to save the card
    email: 'buyer@example.com',
));
```

Inspect the result. A `Secure3DS` means the card needs a 3-D Secure challenge
(see below); otherwise it is a `Transaction`:

```php
if ($result instanceof Secure3DS) {
    // redirect cardholder to the ACS — see "3-D Secure flow"
} elseif ($result->isCompleted()) {
    // paid; persist $result->transactionId
} elseif ($result->isDeclined()) {
    // show $result->cardHolderMessage; inspect $result->reasonCode
    error_log((string) $result->reasonCode?->label());
}
```

## Two-stage (auth + confirm / void)

`auth()` holds the funds without capturing. The request payload is identical to
`charge()`.

```php
use CloudPayments\Request\Payment\CardPaymentRequest;
use CloudPayments\Response\Transaction;
use CloudPayments\ValueObject\Amount;

$auth = $client->payments()->auth(new CardPaymentRequest(
    amount: Amount::of('1000.00'),
    ipAddress: $_SERVER['REMOTE_ADDR'],
    cardCryptogramPacket: $cryptogram,
));

// $auth may be Secure3DS (handle 3DS first) or a Transaction with isAuthorized() === true
```

Capture later with `confirm()`. The captured amount may be **less than or equal
to** the authorized amount (partial capture); the remainder is released.

```php
use CloudPayments\Request\Payment\ConfirmRequest;
use CloudPayments\ValueObject\Amount;

assert($auth instanceof Transaction);

$client->payments()->confirm(new ConfirmRequest(
    transactionId: $auth->transactionId,
    amount: Amount::of('750.00'),   // <= authorized amount
));
```

To release the hold entirely instead of capturing, `void()` it:

```php
$client->payments()->void($auth->transactionId);
```

`confirm()` and `void()` return `void` and throw on failure.

## 3-D Secure flow

When the issuer requires authentication, `charge()`/`auth()` (and the token
variants) return a `CloudPayments\Response\Secure3DS` instead of a `Transaction`.

1. Render an auto-submitting HTML form that POSTs to `$secure->acsUrl`.
2. Include `$secure->formFields()` **plus your own `TermUrl`** (the URL the ACS
   will return the cardholder to). `formFields()` carries `PaReq`/`MD` for v1 or
   `creq`/`threeDSSessionData` for v2 — branch on `isVersion2()` only if you need
   to read individual fields.
3. The ACS POSTs the result back to your `TermUrl`.
4. Complete the payment with `post3ds()`, which returns a real `Transaction`.

```php
use CloudPayments\Response\Secure3DS;

assert($result instanceof Secure3DS);

$fields = $result->formFields();
$fields['TermUrl'] = 'https://shop.example.com/3ds-return';

// echo an HTML <form method="post" action="$result->acsUrl"> with $fields as
// hidden inputs, then document.forms[0].submit() on load.
$version = $result->isVersion2() ? 'v2' : 'v1';

// Stash $result->transactionId (and $result->threeDsCallbackId for v2) in the session
// so you can match the ACS callback to this payment.
```

After the ACS returns to your `TermUrl`, finish the payment. Pass the `PaRes`
(v1) or `cres` (v2) value the ACS sent back as `paRes`; supply
`threeDsCallbackId` from the original `Secure3DS` for v2.

```php
use CloudPayments\Request\Payment\Post3dsRequest;

$tx = $client->payments()->post3ds(new Post3dsRequest(
    transactionId: $transactionId,         // saved before the redirect
    paRes: $_POST['PaRes'] ?? $_POST['cres'],
    threeDsCallbackId: $threeDsCallbackId,  // null for v1
));

if ($tx->isCompleted()) {
    // authenticated and paid
}
```

## Saving cards & charging by token

Pass `saveCard: true` (with an `accountId`) on the first payment. On success the
returned `Transaction` carries a reusable `token`:

```php
use CloudPayments\Request\Payment\CardPaymentRequest;
use CloudPayments\Response\Transaction;
use CloudPayments\ValueObject\Amount;

$tx = $client->payments()->charge(new CardPaymentRequest(
    amount: Amount::of('1000.00'),
    ipAddress: $_SERVER['REMOTE_ADDR'],
    cardCryptogramPacket: $cryptogram,
    accountId: 'user-7',
    saveCard: true,
));

if ($tx instanceof Transaction && $tx->isCompleted()) {
    $savedToken = $tx->token;   // store against the customer
}
```

Later, charge that token server-side (no cardholder, no cryptogram) — ideal for
recurring or merchant-initiated payments:

```php
use CloudPayments\Request\Payment\TokenPaymentRequest;
use CloudPayments\ValueObject\Amount;

$tx = $client->payments()->chargeToken(new TokenPaymentRequest(
    amount: Amount::of('1000.00'),
    accountId: 'user-7',     // must match the account the token was saved under
    token: $savedToken,
    invoiceId: 'order-43',
    description: 'Monthly subscription',
));
```

`authToken()` is the two-stage equivalent — authorize now, `confirm()` later.
Both token methods can also return `Secure3DS`, so branch the same way.

## Refunds

`refund()` reverses a completed payment, fully or partially, and returns a
`CloudPayments\Response\Refund` holding the new refund transaction id.

```php
use CloudPayments\Request\Payment\RefundRequest;
use CloudPayments\ValueObject\Amount;

$refund = $client->payments()->refund(new RefundRequest(
    transactionId: $tx->transactionId,
    amount: Amount::of('250.00'),   // omit nothing — pass the original amount for a full refund
));

$refundId = $refund->transactionId;
```

## Lookups

```php
use CloudPayments\Response\Transaction;

// By transaction id (throws if not found):
$tx = $client->payments()->get(504);

// By your InvoiceId — returns the latest match or null:
$tx = $client->payments()->findByInvoiceId('order-42');
if ($tx === null) {
    // no payment recorded for this invoice yet
}

// All transactions for one day (optionally in a named time zone):
$transactions = $client->payments()->list(new \DateTimeImmutable('2026-06-27'), 'MSK');
foreach ($transactions as $tx) {
    echo $tx->transactionId, ' ', $tx->status?->value, PHP_EOL;
}
```

`list()` returns `list<Transaction>`; `findByInvoiceId()` returns
`?Transaction`.

## Apple Pay / Google Pay

Wallet payments use the **same** `charge()`/`auth()` endpoints. Put the wallet's
Base64 payment token into `cardCryptogramPacket` instead of a widget cryptogram —
everything else (3-D Secure, `confirm()`/`void()`, `refund()`) is identical.

```php
use CloudPayments\Request\Payment\CardPaymentRequest;
use CloudPayments\ValueObject\Amount;

$result = $client->payments()->charge(new CardPaymentRequest(
    amount: Amount::of('1000.00'),
    ipAddress: $_SERVER['REMOTE_ADDR'],
    cardCryptogramPacket: $walletTokenBase64,   // Apple Pay / Google Pay token
    invoiceId: 'order-44',
));
```

## Idempotency

Every mutating method (`charge`, `auth`, `chargeToken`, `authToken`, `post3ds`,
`confirm`, `void`, `refund`) takes an optional `?string $requestId` as its last
argument. Pin it to make retries safe — CloudPayments deduplicates identical ids
for an hour:

```php
$client->payments()->charge($request, 'order-42-attempt-1');
// retry with the SAME id after a timeout → no double charge
```

See [Configuration › Idempotency](configuration.md#idempotency-x-request-id).

## Transaction object reference

`CloudPayments\Response\Transaction` (all properties are readonly):

| Property | Type | Notes |
|----------|------|-------|
| `transactionId` | `int` | CloudPayments transaction id |
| `amount` | `?float` | Requested amount |
| `currency` | `?Currency` | `CloudPayments\Enum\Currency` |
| `status` | `?TransactionStatus` | `Authorized` / `Completed` / `Declined` / … |
| `reasonCode` | `?ReasonCode` | Decline reason enum, or null if unmapped |
| `reasonCodeRaw` | `?int` | Raw gateway code (use when `reasonCode` is null) |
| `reason` | `?string` | Textual decline reason |
| `cardHolderMessage` | `?string` | Message safe to show the cardholder |
| `token` | `?string` | Saved-card token (when `saveCard` was set) |
| `subscriptionId` | `?string` | Linked subscription, if any |
| `card` | `Card` | Masked card details (`maskedNumber()`, `lastFour`, …) |
| `createdAt` | `?\DateTimeImmutable` | Creation timestamp |
| `raw` | `array` | Untouched response model (escape hatch) |

Status helpers:

| Method | True when |
|--------|-----------|
| `isCompleted()` | `status === TransactionStatus::Completed` |
| `isAuthorized()` | `status === TransactionStatus::Authorized` |
| `isDeclined()` | `status === TransactionStatus::Declined` |

## See also

- [Configuration](configuration.md)
- [Webhooks](webhooks.md)
- [Error handling](error-handling.md)
