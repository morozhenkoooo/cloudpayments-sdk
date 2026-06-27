<?php

declare(strict_types=1);

namespace CloudPayments\Tests\Unit\Api;

use CloudPayments\Api\SubscriptionsApi;
use CloudPayments\Enum\Interval;
use CloudPayments\Enum\SubscriptionStatus;
use CloudPayments\Request\Subscription\CreateSubscriptionRequest;
use CloudPayments\Request\Subscription\UpdateSubscriptionRequest;
use CloudPayments\Tests\Support\MockHttp;
use CloudPayments\ValueObject\Amount;
use PHPUnit\Framework\TestCase;

final class SubscriptionsApiTest extends TestCase
{
    private MockHttp $http;
    private SubscriptionsApi $api;

    protected function setUp(): void
    {
        $this->http = new MockHttp();
        $this->api = new SubscriptionsApi($this->http->transport);
    }

    public function testCreateBuildsRequestAndHydrates(): void
    {
        $this->http->queueModel([
            'Id' => 'sc_123',
            'AccountId' => 'user-7',
            'Amount' => 990.0,
            'Currency' => 'RUB',
            'Interval' => 'Month',
            'Period' => 1,
            'Status' => 'Active',
            'StatusCode' => 0,
        ]);

        $subscription = $this->api->create(new CreateSubscriptionRequest(
            token: 'tk_1',
            accountId: 'user-7',
            description: 'Pro plan',
            email: 'buyer@example.com',
            amount: Amount::of('990.00'),
            startDate: new \DateTimeImmutable('2026-02-01 10:00:00'),
            interval: Interval::Month,
            period: 1,
        ));

        self::assertSame('sc_123', $subscription->id);
        self::assertSame(SubscriptionStatus::Active, $subscription->status);
        self::assertTrue($subscription->isActive());

        $params = $this->http->lastRequestParams();
        self::assertSame('https://api.cloudpayments.ru/subscriptions/create', (string) $this->http->lastRequest()->getUri());
        self::assertSame('tk_1', $params['Token']);
        self::assertSame('990.00', $params['Amount']);
        self::assertSame('Month', $params['Interval']);
        self::assertSame('1', $params['Period']);
        self::assertSame('2026-02-01 10:00:00', $params['StartDate']);
    }

    public function testUpdateOmitsNullFields(): void
    {
        $this->http->queueModel(['Id' => 'sc_123', 'Status' => 'Active', 'StatusCode' => 0]);

        $this->api->update(new UpdateSubscriptionRequest(id: 'sc_123', amount: Amount::of('500.00')));

        $params = $this->http->lastRequestParams();
        self::assertSame('sc_123', $params['Id']);
        self::assertSame('500.00', $params['Amount']);
        self::assertArrayNotHasKey('Interval', $params);
        self::assertArrayNotHasKey('Period', $params);
    }

    public function testFindByAccountIdMapsList(): void
    {
        $this->http->queueJson((string) json_encode([
            'Success' => true,
            'Model' => [
                ['Id' => 'sc_1', 'Status' => 'Active', 'StatusCode' => 0],
                ['Id' => 'sc_2', 'Status' => 'Cancelled', 'StatusCode' => 2],
            ],
        ]));

        $list = $this->api->findByAccountId('user-7');

        self::assertCount(2, $list);
        self::assertSame('sc_1', $list[0]->id);
        self::assertTrue($list[1]->isCancelled());
    }

    public function testCancelPostsId(): void
    {
        $this->http->queueModel([], success: true);

        $this->api->cancel('sc_123');

        self::assertSame('sc_123', $this->http->lastRequestParams()['Id']);
        self::assertSame('https://api.cloudpayments.ru/subscriptions/cancel', (string) $this->http->lastRequest()->getUri());
    }
}
