<?php

declare(strict_types=1);

namespace CloudPayments\Tests\Unit\ValueObject;

use CloudPayments\Exception\ValidationException;
use CloudPayments\ValueObject\Amount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AmountTest extends TestCase
{
    /**
     * @return iterable<string, array{int|float|string, string}>
     */
    public static function validProvider(): iterable
    {
        yield 'int' => [1000, '1000'];
        yield 'decimal string' => ['1000.00', '1000.00'];
        yield 'float two decimals' => [99.90, '99.9'];
        yield 'float whole' => [100.0, '100'];
        yield 'string single decimal' => ['10.5', '10.5'];
    }

    #[DataProvider('validProvider')]
    public function testNormalizesValidAmounts(int|float|string $input, string $expected): void
    {
        self::assertSame($expected, (string) Amount::of($input));
    }

    public function testToFloat(): void
    {
        self::assertSame(1000.5, Amount::of('1000.50')->toFloat());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'letters' => ['abc'];
        yield 'negative' => ['-10'];
        yield 'comma' => ['10,5'];
        yield 'empty' => [''];
        yield 'too many decimals' => ['10.999'];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalidAmounts(string $input): void
    {
        $this->expectException(ValidationException::class);
        Amount::of($input);
    }
}
