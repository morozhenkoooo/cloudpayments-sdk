# Payouts

A payout credits money **to** a card (a "topup"), the reverse of a charge. Your
account must have payouts enabled by CloudPayments before these calls succeed.
Access the API through `$client->payouts()`.

## Paying out to a card

`toCard()` sends funds to a card identified by a cryptogram packet (produced by
the CloudPayments widget) and posts to `/payments/cards/topup`.

```php
use CloudPayments\Client;
use CloudPayments\Enum\Currency;
use CloudPayments\Request\Payout\CardPayoutRequest;
use CloudPayments\ValueObject\Amount;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

$payout = $client->payouts()->toCard(
    new CardPayoutRequest(
        cardCryptogramPacket: 'eyJ2IjoiM...',   // from the CloudPayments widget
        amount: Amount::of('1500.00'),
        currency: Currency::RUB,                 // optional, defaults to RUB
        name: 'IVAN PETROV',                     // optional, cardholder name
        accountId: 'user@example.com',           // optional
        invoiceId: 'payout-2026-0042',           // optional
        email: 'user@example.com',               // optional
    ),
);

$payout->transactionId; // 504123
$payout->status;        // TransactionStatus::Completed
```

## Paying out to a saved token

`toToken()` reuses a saved card token — the same token you get from a saved-card
payment, see [Payments](payments.md) — and posts to `/payments/token/topup`.

```php
use CloudPayments\Client;
use CloudPayments\Enum\Currency;
use CloudPayments\Request\Payout\TokenPayoutRequest;
use CloudPayments\ValueObject\Amount;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

$payout = $client->payouts()->toToken(
    new TokenPayoutRequest(
        token: 'a4e67841-abb0-42de-a364-d1d8f9f59e84',
        amount: Amount::of('1500.00'),
        currency: Currency::RUB,            // optional, defaults to RUB
        accountId: 'user@example.com',      // optional
        invoiceId: 'payout-2026-0043',      // optional
    ),
);
```

## The `Payout` object

Both methods return `CloudPayments\Response\Payout`:

| Property | Type | Notes |
|----------|------|-------|
| `transactionId` | `int` | Topup transaction id |
| `amount` | `?float` | Amount credited |
| `currency` | `?Currency` | Payout currency |
| `status` | `?TransactionStatus` | Transaction outcome |
| `token` | `?string` | Saved card token returned with the topup |
| `card` | `Card` | Masked card details (never the full PAN) |
| `raw` | `array` | Untouched response Model, as an escape hatch |

```php
$payout->card->maskedNumber(); // "411111******1111"
$payout->card->lastFour;       // "1111"
```

## Idempotency

`toCard()` and `toToken()` accept an optional `?string $requestId` as their last
argument. Pass a stable value so a retry after a timeout returns the original
result instead of paying out twice:

```php
$client->payouts()->toToken($request, 'payout-2026-0043');
```

See [Configuration → Idempotency](configuration.md#idempotency-x-request-id).

## See also

- [Payments](payments.md) — obtaining the saved-card `token`
- [Error handling](error-handling.md)
- [Configuration](configuration.md)
