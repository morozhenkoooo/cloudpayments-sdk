<?php

declare(strict_types=1);

namespace CloudPayments\Tests\Unit;

use CloudPayments\Client;
use CloudPayments\Config;
use CloudPayments\Gateway;
use CloudPayments\Http\RequestIdGenerator;
use CloudPayments\Request\Payment\CardPaymentRequest;
use CloudPayments\ValueObject\Amount;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class ClientTest extends TestCase
{
    private function client(MockClient $mock, Gateway $gateway = Gateway::Russia): Client
    {
        $factory = new Psr17Factory();

        return new Client(
            new Config('pk_test', 'secret_test', $gateway),
            $mock,
            $factory,
            $factory,
            new class () implements RequestIdGenerator {
                public function generate(): string
                {
                    return 'id';
                }
            },
        );
    }

    public function testAccessorsAreMemoized(): void
    {
        $client = $this->client(new MockClient());

        self::assertSame($client->payments(), $client->payments());
        self::assertSame($client->subscriptions(), $client->subscriptions());
        self::assertSame($client->receipts(), $client->receipts());
        self::assertSame($client->payouts(), $client->payouts());
        self::assertSame($client->webhooks(), $client->webhooks());
    }

    public function testKazakhstanGatewayRoutesToKzHost(): void
    {
        $mock = new MockClient();
        $factory = new Psr17Factory();
        $mock->addResponse(
            $factory->createResponse(200)->withBody(
                $factory->createStream((string) json_encode([
                    'Success' => true,
                    'Model' => ['TransactionId' => 1, 'Status' => 'Completed', 'StatusCode' => 3],
                ])),
            ),
        );

        $this->client($mock, Gateway::Kazakhstan)->payments()->charge(new CardPaymentRequest(
            amount: Amount::of('100.00'),
            ipAddress: '127.0.0.1',
            cardCryptogramPacket: 'crypto',
        ));

        $request = $mock->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('api.cloudpayments.kz', $request->getUri()->getHost());
    }
}
