<?php

declare(strict_types=1);

namespace CloudPayments\Tests\Unit\Api;

use CloudPayments\Api\PayoutsApi;
use CloudPayments\Enum\TransactionStatus;
use CloudPayments\Request\Payout\CardPayoutRequest;
use CloudPayments\Request\Payout\TokenPayoutRequest;
use CloudPayments\Tests\Support\MockHttp;
use CloudPayments\ValueObject\Amount;
use PHPUnit\Framework\TestCase;

final class PayoutsApiTest extends TestCase
{
    private MockHttp $http;
    private PayoutsApi $api;

    protected function setUp(): void
    {
        $this->http = new MockHttp();
        $this->api = new PayoutsApi($this->http->transport);
    }

    public function testPayoutToCardUsesPluralPath(): void
    {
        $this->http->queueModel([
            'TransactionId' => 42,
            'Amount' => 500.0,
            'Currency' => 'RUB',
            'Status' => 'Completed',
            'StatusCode' => 3,
            'CardLastFour' => '4242',
        ]);

        $payout = $this->api->toCard(new CardPayoutRequest(
            cardCryptogramPacket: 'crypto',
            amount: Amount::of('500.00'),
            accountId: 'user-7',
        ));

        self::assertSame(42, $payout->transactionId);
        self::assertSame(TransactionStatus::Completed, $payout->status);
        self::assertSame('4242', $payout->card->lastFour);
        self::assertSame('https://api.cloudpayments.ru/payments/cards/topup', (string) $this->http->lastRequest()->getUri());
        self::assertSame('crypto', $this->http->lastRequestParams()['CardCryptogramPacket']);
    }

    public function testPayoutToTokenUsesSingularPath(): void
    {
        $this->http->queueModel(['TransactionId' => 43, 'Status' => 'Completed', 'StatusCode' => 3]);

        $this->api->toToken(new TokenPayoutRequest(
            token: 'tk_1',
            amount: Amount::of('250.00'),
            accountId: 'user-7',
        ));

        self::assertSame('https://api.cloudpayments.ru/payments/token/topup', (string) $this->http->lastRequest()->getUri());
        $params = $this->http->lastRequestParams();
        self::assertSame('tk_1', $params['Token']);
        self::assertSame('250.00', $params['Amount']);
    }
}
