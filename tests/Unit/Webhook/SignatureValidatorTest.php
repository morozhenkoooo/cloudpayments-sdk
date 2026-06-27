<?php

declare(strict_types=1);

namespace CloudPayments\Tests\Unit\Webhook;

use CloudPayments\Webhook\SignatureValidator;
use PHPUnit\Framework\TestCase;

final class SignatureValidatorTest extends TestCase
{
    private const string SECRET = 'my_api_secret';
    private const string BODY = '{"TransactionId":123,"Amount":10.00}';

    public function testSignMatchesReferenceHmac(): void
    {
        $validator = new SignatureValidator(self::SECRET);
        $expected = base64_encode(hash_hmac('sha256', self::BODY, self::SECRET, true));

        self::assertSame($expected, $validator->sign(self::BODY));
    }

    public function testValidSignaturePasses(): void
    {
        $validator = new SignatureValidator(self::SECRET);

        self::assertTrue($validator->isValid(self::BODY, $validator->sign(self::BODY)));
    }

    public function testTamperedBodyFails(): void
    {
        $validator = new SignatureValidator(self::SECRET);
        $signature = $validator->sign(self::BODY);

        self::assertFalse($validator->isValid(self::BODY . 'x', $signature));
    }

    public function testWrongSecretFails(): void
    {
        $signature = (new SignatureValidator('other'))->sign(self::BODY);

        self::assertFalse((new SignatureValidator(self::SECRET))->isValid(self::BODY, $signature));
    }

    public function testNullOrEmptySignatureFails(): void
    {
        $validator = new SignatureValidator(self::SECRET);

        self::assertFalse($validator->isValid(self::BODY, null));
        self::assertFalse($validator->isValid(self::BODY, ''));
    }
}
