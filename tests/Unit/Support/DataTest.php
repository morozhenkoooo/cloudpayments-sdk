<?php

declare(strict_types=1);

namespace CloudPayments\Tests\Unit\Support;

use CloudPayments\Support\Data;
use PHPUnit\Framework\TestCase;

final class DataTest extends TestCase
{
    public function testBoolTreatsEmptyStringAsNull(): void
    {
        // A form-encoded "TestMode=" (no value) must be absent, not false.
        self::assertNull(Data::bool(['TestMode' => ''], 'TestMode'));
        self::assertNull(Data::bool([], 'TestMode'));
    }

    public function testBoolParsesTruthyStringsAndRealBooleans(): void
    {
        self::assertTrue(Data::bool(['x' => 'true'], 'x'));
        self::assertTrue(Data::bool(['x' => '1'], 'x'));
        self::assertTrue(Data::bool(['x' => true], 'x'));
        self::assertFalse(Data::bool(['x' => 'false'], 'x'));
        self::assertFalse(Data::bool(['x' => '0'], 'x'));
    }

    public function testStringAndIntTreatEmptyStringAsNull(): void
    {
        self::assertNull(Data::string(['x' => ''], 'x'));
        self::assertNull(Data::int(['x' => ''], 'x'));
        self::assertSame(42, Data::int(['x' => '42'], 'x'));
        self::assertSame('v', Data::string(['x' => 'v'], 'x'));
    }

    public function testDateTimeParsesIso(): void
    {
        $dt = Data::dateTime(['d' => '2026-02-01T10:00:00Z'], 'd');

        self::assertInstanceOf(\DateTimeImmutable::class, $dt);
        self::assertSame('2026-02-01', $dt->format('Y-m-d'));
    }

    public function testDateTimeReturnsNullOnGarbage(): void
    {
        self::assertNull(Data::dateTime(['d' => 'not-a-date'], 'd'));
        self::assertNull(Data::dateTime([], 'd'));
    }
}
