# CloudPayments PHP SDK — Documentation

Full usage guide for `morozhenkoooo/cloudpayments-sdk`.

## Contents

1. [Installation](installation.md) — requirements, Composer, picking a PSR-18 client
2. [Configuration](configuration.md) — credentials, gateways, custom HTTP client, idempotency, culture
3. [Payments](payments.md) — charge, two-stage (auth/confirm/void), tokens, 3-D Secure, refunds, lookups
4. [Subscriptions](subscriptions.md) — recurring billing: create, update, find, cancel
5. [Receipts (54-FZ)](receipts.md) — fiscal receipts via CloudKassir
6. [Payouts](payouts.md) — sending money to a card (CardPayout / topup)
7. [Webhooks](webhooks.md) — signature verification, typed notifications, the `Check` flow
8. [Error handling](error-handling.md) — exception hierarchy, declines vs. failures
9. [Enums reference](enums.md) — every enum and its cases

## At a glance

```php
use CloudPayments\Client;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');

$client->payments();      // PaymentsApi
$client->subscriptions(); // SubscriptionsApi
$client->receipts();      // ReceiptsApi
$client->payouts();       // PayoutsApi
$client->webhooks();      // WebhookProcessor
```

Every request is an immutable DTO, every response is a typed object, and every
money value goes through `CloudPayments\ValueObject\Amount`. The HTTP layer is
PSR-18 — bring your own client.

> **Concept that trips people up:** a card **decline is not an exception**. It
> comes back as a `Transaction` whose `status` is `TransactionStatus::Declined`.
> Exceptions are reserved for transport failures, bad credentials, malformed
> requests, and webhook signature mismatches. See [Error handling](error-handling.md).
