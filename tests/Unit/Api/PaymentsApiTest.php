<?php

declare(strict_types=1);

namespace CloudPayments\Tests\Unit\Api;

use CloudPayments\Api\PaymentsApi;
use CloudPayments\Enum\Currency;
use CloudPayments\Enum\ReasonCode;
use CloudPayments\Enum\TransactionStatus;
use CloudPayments\Exception\ApiException;
use CloudPayments\Request\Payment\CardPaymentRequest;
use CloudPayments\Request\Payment\ConfirmRequest;
use CloudPayments\Request\Payment\RefundRequest;
use CloudPayments\Response\Secure3DS;
use CloudPayments\Response\Transaction;
use CloudPayments\Tests\Support\MockHttp;
use CloudPayments\ValueObject\Amount;
use PHPUnit\Framework\TestCase;

final class PaymentsApiTest extends TestCase
{
    private MockHttp $http;
    private PaymentsApi $api;

    protected function setUp(): void
    {
        $this->http = new MockHttp();
        $this->api = new PaymentsApi($this->http->transport);
    }

    private function chargeRequest(): CardPaymentRequest
    {
        return new CardPaymentRequest(
            amount: Amount::of('1000.00'),
            ipAddress: '127.0.0.1',
            cardCryptogramPacket: 'crypto-packet',
            invoiceId: 'INV-1',
        );
    }

    public function testChargeSuccessReturnsCompletedTransaction(): void
    {
        $this->http->queueModel([
            'TransactionId' => 504,
            'Amount' => 1000.0,
            'Currency' => 'RUB',
            'Status' => 'Completed',
            'StatusCode' => 3,
            'Token' => 'tk_123',
        ]);

        $result = $this->api->charge($this->chargeRequest());

        self::assertInstanceOf(Transaction::class, $result);
        self::assertSame(504, $result->transactionId);
        self::assertSame(Currency::RUB, $result->currency);
        self::assertTrue($result->isCompleted());
        self::assertSame('tk_123', $result->token);
    }

    public function testChargeBuildsAuthenticatedPostRequest(): void
    {
        $this->http->queueModel(['TransactionId' => 1, 'Status' => 'Completed', 'StatusCode' => 3]);

        $this->api->charge($this->chargeRequest());

        $request = $this->http->lastRequest();
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.cloudpayments.ru/payments/cards/charge', (string) $request->getUri());
        self::assertSame('Basic ' . base64_encode('pk_test:secret_test'), $request->getHeaderLine('Authorization'));
        self::assertSame('fixed-request-id', $request->getHeaderLine('X-Request-ID'));
        self::assertStringContainsString('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));

        $params = $this->http->lastRequestParams();
        self::assertSame('1000.00', $params['Amount']);
        self::assertSame('RUB', $params['Currency']);
        self::assertSame('crypto-packet', $params['CardCryptogramPacket']);
        self::assertSame('INV-1', $params['InvoiceId']);
        self::assertSame('ru-RU', $params['CultureName']);
    }

    public function testChargeReturnsSecure3dsWhenChallenged(): void
    {
        $this->http->queueModel([
            'TransactionId' => 777,
            'PaReq' => 'pa-req-value',
            'AcsUrl' => 'https://acs.bank/3ds',
        ], success: false);

        $result = $this->api->charge($this->chargeRequest());

        self::assertInstanceOf(Secure3DS::class, $result);
        self::assertSame(777, $result->transactionId);
        self::assertSame('https://acs.bank/3ds', $result->acsUrl);
        self::assertFalse($result->isVersion2());
        self::assertSame(['PaReq' => 'pa-req-value', 'MD' => '777'], $result->formFields());
    }

    public function testDeclinedPaymentIsTransactionNotException(): void
    {
        $this->http->queueModel([
            'TransactionId' => 9,
            'Status' => 'Declined',
            'StatusCode' => 5,
            'Reason' => 'InsufficientFunds',
            'ReasonCode' => 5051,
        ], success: false);

        $result = $this->api->charge($this->chargeRequest());

        self::assertInstanceOf(Transaction::class, $result);
        self::assertTrue($result->isDeclined());
        self::assertSame(ReasonCode::InsufficientFunds, $result->reasonCode);
        self::assertSame(5051, $result->reasonCodeRaw);
        self::assertSame(TransactionStatus::Declined, $result->status);
    }

    public function testApiErrorWithoutModelThrows(): void
    {
        $this->http->queueModel([], success: false, message: 'Amount is required');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Amount is required');

        $this->api->charge($this->chargeRequest());
    }

    public function testConfirmSucceeds(): void
    {
        $this->http->queueModel([], success: true);

        $this->api->confirm(new ConfirmRequest(504, Amount::of('1000.00')));

        $params = $this->http->lastRequestParams();
        self::assertSame('504', $params['TransactionId']);
        self::assertSame('1000.00', $params['Amount']);
    }

    public function testConfirmThrowsOnFailure(): void
    {
        $this->http->queueModel([], success: false, message: 'Transaction not found');

        $this->expectException(ApiException::class);
        $this->api->confirm(new ConfirmRequest(1, Amount::of('1.00')));
    }

    public function testVoidPostsTransactionId(): void
    {
        $this->http->queueModel([], success: true);

        $this->api->void(504);

        self::assertSame('504', $this->http->lastRequestParams()['TransactionId']);
        self::assertSame('https://api.cloudpayments.ru/payments/void', (string) $this->http->lastRequest()->getUri());
    }

    public function testRefundReturnsNewTransactionId(): void
    {
        $this->http->queueModel(['TransactionId' => 888], success: true);

        $refund = $this->api->refund(new RefundRequest(504, Amount::of('500.00')));

        self::assertSame(888, $refund->transactionId);
    }

    public function testGetReturnsTransaction(): void
    {
        $this->http->queueModel(['TransactionId' => 504, 'Status' => 'Authorized', 'StatusCode' => 2]);

        $tx = $this->api->get(504);

        self::assertTrue($tx->isAuthorized());
    }

    public function testFindByInvoiceIdReturnsNullWhenMissing(): void
    {
        $this->http->queueModel([], success: false, message: 'not found');

        self::assertNull($this->api->findByInvoiceId('UNKNOWN'));
    }

    public function testListMapsRows(): void
    {
        $this->http->queueJson((string) json_encode([
            'Success' => true,
            'Message' => null,
            'Model' => [
                ['TransactionId' => 1, 'Status' => 'Completed', 'StatusCode' => 3],
                ['TransactionId' => 2, 'Status' => 'Declined', 'StatusCode' => 5],
            ],
        ]));

        $list = $this->api->list(new \DateTimeImmutable('2026-01-01'));

        self::assertCount(2, $list);
        self::assertSame(1, $list[0]->transactionId);
        self::assertTrue($list[1]->isDeclined());
    }
}
