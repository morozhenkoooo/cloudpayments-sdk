<?php

declare(strict_types=1);

namespace CloudPayments\Tests\Unit\Webhook;

use CloudPayments\Enum\CheckResponseCode;
use CloudPayments\Enum\NotificationType;
use CloudPayments\Enum\TransactionStatus;
use CloudPayments\Exception\InvalidSignatureException;
use CloudPayments\Webhook\CheckResponse;
use CloudPayments\Webhook\Notification\CheckNotification;
use CloudPayments\Webhook\Notification\PayNotification;
use CloudPayments\Webhook\SignatureValidator;
use CloudPayments\Webhook\WebhookProcessor;
use PHPUnit\Framework\TestCase;

final class WebhookProcessorTest extends TestCase
{
    private const string SECRET = 'secret_test';

    private WebhookProcessor $processor;
    private SignatureValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SignatureValidator(self::SECRET);
        $this->processor = new WebhookProcessor($this->validator);
    }

    public function testParsesPayNotificationFromFormBody(): void
    {
        $body = http_build_query([
            'TransactionId' => 504,
            'Amount' => 1000.0,
            'Currency' => 'RUB',
            'Status' => 'Completed',
            'StatusCode' => 3,
            'CardLastFour' => '4242',
            'Token' => 'tk_9',
            'Email' => 'buyer@example.com',
        ]);

        $notification = $this->processor->parse(NotificationType::Pay, $body, $this->validator->sign($body));

        self::assertInstanceOf(PayNotification::class, $notification);
        self::assertSame(NotificationType::Pay, $notification->type());
        self::assertSame(504, $notification->transactionId);
        self::assertSame('tk_9', $notification->token);
        self::assertSame(TransactionStatus::Completed, $notification->status);
        self::assertSame('4242', $notification->card->lastFour);
    }

    public function testParsesCheckNotificationFromJsonBody(): void
    {
        $body = (string) json_encode([
            'TransactionId' => 1,
            'Amount' => 10.0,
            'AccountId' => 'user-7',
            'Data' => '{"orderId":"X1"}',
        ]);

        $notification = $this->processor->parse(NotificationType::Check, $body, $this->validator->sign($body));

        self::assertInstanceOf(CheckNotification::class, $notification);
        self::assertSame('user-7', $notification->accountId);
        self::assertSame(['orderId' => 'X1'], $notification->data);
    }

    public function testInvalidSignatureThrows(): void
    {
        $body = 'TransactionId=1';

        $this->expectException(InvalidSignatureException::class);
        $this->processor->parse(NotificationType::Pay, $body, 'wrong-signature');
    }

    public function testMissingSignatureThrows(): void
    {
        $this->expectException(InvalidSignatureException::class);
        $this->processor->parse(NotificationType::Pay, 'TransactionId=1', null);
    }

    public function testSignatureFromHeadersPrefersModernHeader(): void
    {
        $headers = [
            'Content-HMAC' => 'old',
            'X-Content-HMAC' => 'new',
        ];

        self::assertSame('new', $this->processor->signatureFromHeaders($headers));
    }

    public function testSignatureFromHeadersIsCaseInsensitiveAndHandlesArrays(): void
    {
        self::assertSame('sig', $this->processor->signatureFromHeaders(['content-hmac' => ['sig']]));
        self::assertNull($this->processor->signatureFromHeaders(['X-Other' => 'x']));
    }

    public function testCheckResponseJson(): void
    {
        self::assertSame('{"code":0}', CheckResponse::ok()->toJson());
        self::assertSame('{"code":13}', CheckResponse::rejected()->toJson());
        self::assertSame(CheckResponseCode::InvalidAccountId, CheckResponse::invalidAccountId()->code);
    }
}
