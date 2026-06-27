# Installation

## Requirements

- PHP **8.3** or **8.4**
- Any [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client and
  [PSR-17](https://www.php-fig.org/psr/psr-17/) factories

## Install

```bash
composer require morozhenkoooo/cloudpayments-sdk
```

The SDK does not depend on a specific HTTP client. It discovers one at runtime
via [php-http/discovery](https://docs.php-http.org/en/latest/discovery.html). If
your project does not already ship a PSR-18 client + PSR-17 factories, add a
pair — for example:

```bash
# Guzzle (bundles PSR-18 + PSR-17)
composer require guzzlehttp/guzzle

# …or Symfony HttpClient + Nyholm factories
composer require symfony/http-client nyholm/psr7
```

Discovery will pick whichever is installed. To control the client explicitly
(timeouts, proxies, middleware, retries), pass it into the `Client` —
see [Configuration → Custom HTTP client](configuration.md#custom-http-client).

## Verify

```php
require 'vendor/autoload.php';

$client = \CloudPayments\Client::create('pk_test', 'secret');
// $client->payments() is ready to use
```

## Next

- [Configuration](configuration.md)
- [Payments](payments.md)
