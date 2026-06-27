# Subscriptions

Recurring payments billed against a saved card token. Access the API through
`$client->subscriptions()`.

## Creating a subscription

`create()` schedules a recurring charge. The `token` comes from a prior payment
made with `SaveCard` enabled — see [Payments](payments.md). All eight constructor
arguments below are required; `currency`, `requireConfirmation` and `maxPeriods`
are optional.

```php
use CloudPayments\Client;
use CloudPayments\Enum\Currency;
use CloudPayments\Enum\Interval;
use CloudPayments\Request\Subscription\CreateSubscriptionRequest;
use CloudPayments\ValueObject\Amount;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

$subscription = $client->subscriptions()->create(
    new CreateSubscriptionRequest(
        token: 'a4e67841-abb0-42de-a364-d1d8f9f59e84', // from a saved-card payment
        accountId: 'user@example.com',
        description: 'Premium plan, monthly',
        email: 'user@example.com',
        amount: Amount::of('499.00'),
        startDate: new \DateTimeImmutable('2026-07-01 10:00:00'),
        interval: Interval::Month,
        period: 1,                               // every 1 month
        currency: Currency::RUB,                 // optional, defaults to RUB
        requireConfirmation: false,              // optional
        maxPeriods: 12,                          // optional, stop after 12 charges
    ),
);

$subscription->id;     // "sc_8cf8a9..."
$subscription->status; // SubscriptionStatus::Active
```

`startDate` accepts any `\DateTimeInterface` and is serialized as
`Y-m-d H:i:s`. `interval` × `period` defines the cadence: `Interval::Week` with
`period: 2` bills every two weeks.

### Interval

`CloudPayments\Enum\Interval` is the billing unit:

| Case | Value |
|------|-------|
| `Interval::Day` | `Day` |
| `Interval::Week` | `Week` |
| `Interval::Month` | `Month` |

## Fetching a subscription

```php
use CloudPayments\Client;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

$subscription = $client->subscriptions()->get('sc_8cf8a9...');
```

## Listing by account

`findByAccountId()` returns every subscription for a merchant `AccountId` as a
`list<Subscription>` (empty when none match).

```php
use CloudPayments\Client;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

$subscriptions = $client->subscriptions()->findByAccountId('user@example.com');

foreach ($subscriptions as $subscription) {
    echo $subscription->id, ' ', $subscription->status?->value, PHP_EOL;
}
```

## Updating a subscription

`update()` patches an existing subscription by `id`. Every billing field is
optional — only the non-null values you pass are sent; the rest stay unchanged on
the CloudPayments side.

```php
use CloudPayments\Client;
use CloudPayments\Request\Subscription\UpdateSubscriptionRequest;
use CloudPayments\ValueObject\Amount;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

$subscription = $client->subscriptions()->update(
    new UpdateSubscriptionRequest(
        id: 'sc_8cf8a9...',
        amount: Amount::of('599.00'),     // raise the price
        description: 'Premium plan (new rate)',
        // interval, period, startDate, maxPeriods, currency,
        // requireConfirmation left out → unchanged
    ),
);
```

## Cancelling a subscription

```php
use CloudPayments\Client;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

$client->subscriptions()->cancel('sc_8cf8a9...'); // returns void
```

## The `Subscription` object

`create()`, `get()`, `findByAccountId()` and `update()` all return
`CloudPayments\Response\Subscription`. Useful public properties:

| Property | Type | Notes |
|----------|------|-------|
| `id` | `string` | Subscription id |
| `accountId` | `?string` | Merchant-side account identifier |
| `amount` | `?float` | Charge amount per period |
| `currency` | `?Currency` | Billing currency |
| `interval` | `?Interval` | Billing unit (`Day`/`Week`/`Month`) |
| `period` | `?int` | Number of intervals between charges |
| `maxPeriods` | `?int` | Total charges before it ends (null = open-ended) |
| `status` | `?SubscriptionStatus` | Lifecycle state |
| `startDate` | `?\DateTimeImmutable` | First scheduled charge |
| `lastTransactionDate` | `?\DateTimeImmutable` | Most recent charge |
| `nextTransactionDate` | `?\DateTimeImmutable` | Next scheduled charge |
| `successfulTransactionsNumber` | `?int` | Count of successful charges |
| `failedTransactionsNumber` | `?int` | Count of failed charges |
| `raw` | `array` | Untouched response Model, as an escape hatch |

Helpers:

```php
$subscription->isActive();    // status === SubscriptionStatus::Active
$subscription->isCancelled(); // status === SubscriptionStatus::Cancelled
```

`CloudPayments\Enum\SubscriptionStatus` cases: `Active`, `PastDue`, `Cancelled`,
`Rejected`, `Expired`.

## Status changes via webhook

The CloudPayments **`Recurrent`** notification delivers subscription status
changes (renewals, failures, cancellations) to your endpoint — poll only as a
fallback. See [Webhooks](webhooks.md).

## Idempotency

`create()`, `update()` and `cancel()` accept an optional `?string $requestId` as
their last argument. Pass a stable value so a retry after a timeout is
deduplicated instead of creating a second subscription:

```php
$client->subscriptions()->create($request, 'subscribe-order-42');
```

See [Configuration → Idempotency](configuration.md#idempotency-x-request-id).

## See also

- [Payments](payments.md) — obtaining the saved-card `token`
- [Webhooks](webhooks.md) — `Recurrent` status notifications
- [Error handling](error-handling.md)
- [Configuration](configuration.md)
