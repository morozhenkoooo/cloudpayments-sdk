# Error handling

The SDK throws only for problems it cannot turn into a result: a failed network
call, rejected credentials, malformed input, a bad webhook signature, or a
response it cannot parse. Ordinary business outcomes — a declined card, a 3-D
Secure challenge — are **return values**, not exceptions. See
[Declines are not exceptions](#declines-are-not-exceptions) below.

## Catch everything with one interface

Every exception the SDK throws implements the marker interface
`CloudPayments\Exception\CloudPaymentsException` (which extends `\Throwable`), so
a single `catch` covers all of them:

```php
use CloudPayments\Exception\CloudPaymentsException;

try {
    $tx = $client->payments()->charge($request);
} catch (CloudPaymentsException $e) {
    // any SDK-level failure: transport, auth, validation, signature, parsing
    error_log($e->getMessage());
}
```

Catch the concrete subtypes when you want to react differently to each — they
are matched top to bottom, so list the most specific first.

## Exception reference

| Exception | Extends | Thrown when |
|-----------|---------|-------------|
| `TransportException` | `\RuntimeException` | The HTTP request never produced a usable response (network, DNS, timeout). Wraps the underlying PSR-18 `ClientExceptionInterface` as `previous`. |
| `AuthenticationException` | `ApiException` | The gateway returns HTTP 401 — wrong Public ID / API Secret, or credentials for the wrong gateway. |
| `ApiException` | `\RuntimeException` | The call reached the API but it reported a failure with no usable model: bad parameters, unknown entity, or HTTP >= 500. Exposes `httpStatusCode()` and `response()`. |
| `ValidationException` | `\InvalidArgumentException` | A request DTO or value object was built with invalid input, caught locally before any HTTP call (e.g. a bad `Amount`). |
| `InvalidSignatureException` | `\RuntimeException` | An inbound webhook failed HMAC signature verification (mismatched or missing signature) and must be rejected. |
| `UnexpectedResponseException` | `\RuntimeException` | The response body could not be parsed as the expected JSON envelope (non-JSON, or an unexpected JSON shape). |

All of them implement `CloudPayments\Exception\CloudPaymentsException`.

`AuthenticationException` is an `ApiException`, so a `catch (ApiException)` also
catches 401s — put the `AuthenticationException` arm first if you need to tell
them apart.

### Inspecting an `ApiException`

`ApiException` (and therefore `AuthenticationException`) carries the gateway
context:

```php
use CloudPayments\Exception\ApiException;

try {
    $tx = $client->payments()->get(504);
} catch (ApiException $e) {
    $e->getMessage();       // the API `Message`, or a default
    $e->httpStatusCode();   // int — the HTTP status (0 if unknown)
    $e->response();         // ?array — the decoded response body, or null
}
```

Internally these come from the transport (401, HTTP >= 500) and from the API
layer, which escalates a failed envelope that carries no `Model` (bad params,
unknown entity) to an `ApiException`. A failed envelope that *does* carry a
model — a declined transaction — is returned to you instead.

## Declines are not exceptions

A declined card is a normal business outcome, not an error. `charge()`, `auth()`,
and the token variants return a `CloudPayments\Response\Transaction` whose
`status` is `TransactionStatus::Declined`. Inspect it rather than wrapping the
call in a `try`/`catch`:

```php
use CloudPayments\Enum\TransactionStatus;

if ($tx->status === TransactionStatus::Declined) {   // or $tx->isDeclined()
    $tx->reasonCode;          // ?ReasonCode — mapped decline reason, or null if unmapped
    $tx->reasonCodeRaw;       // ?int — the raw gateway code (use when reasonCode is null)
    $tx->reason;              // ?string — textual reason
    $tx->cardHolderMessage;   // ?string — message safe to show the cardholder
}
```

`reasonCode` is a `CloudPayments\Enum\ReasonCode`. That enum is non-exhaustive,
so it is `null` for codes it does not list — fall back to `reasonCodeRaw` for the
exact integer. See [Enums › ReasonCode](enums.md#reasoncode).

Likewise, a 3-D Secure challenge is **returned, not thrown**: `charge()`/`auth()`
return a `CloudPayments\Response\Secure3DS` instead of a `Transaction` when the
issuer requires authentication. Branch on the return type. See
[Payments › 3-D Secure flow](payments.md#3-d-secure-flow).

Exceptions are reserved for transport, credential, validation, signature, and
parsing problems only.

## Putting it together

A realistic `charge()` handler distinguishes all four outcomes — a thrown SDK
exception, a 3-D Secure challenge, a decline, and a completed payment:

```php
use CloudPayments\Exception\AuthenticationException;
use CloudPayments\Exception\ApiException;
use CloudPayments\Exception\TransportException;
use CloudPayments\Exception\ValidationException;
use CloudPayments\Exception\CloudPaymentsException;
use CloudPayments\Response\Secure3DS;

try {
    $result = $client->payments()->charge($request);
} catch (ValidationException $e) {
    // bad input — fix the request, do not retry as-is
    return badRequest($e->getMessage());
} catch (AuthenticationException $e) {
    // 401 — misconfigured credentials; alert ops, do not retry
    throw $e;
} catch (TransportException $e) {
    // network/timeout — safe to retry with the SAME requestId (idempotent)
    return retryLater($e);
} catch (ApiException $e) {
    // API rejected the call (bad params / 5xx); inspect $e->response()
    return serverError($e->getMessage(), $e->httpStatusCode());
} catch (CloudPaymentsException $e) {
    // any remaining SDK failure (e.g. UnexpectedResponseException)
    return serverError($e->getMessage());
}

// No exception thrown — now interpret the business result.
if ($result instanceof Secure3DS) {
    return redirectToAcs($result);                 // 3-D Secure challenge
}

if ($result->isDeclined()) {
    return declined($result->cardHolderMessage, $result->reasonCode);
}

if ($result->isCompleted()) {
    return paid($result->transactionId);           // captured
}

// e.g. Authorized (two-stage) — capture later with confirm()
return pending($result->transactionId);
```

## See also

- [Payments](payments.md)
- [Enums](enums.md)
- [Configuration](configuration.md)
