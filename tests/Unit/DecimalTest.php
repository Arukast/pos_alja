<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Decimal;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DecimalTest extends TestCase
{
    public function test_multiply_rounds_cents_half_up(): void
    {
        $this->assertSame('833.33', Decimal::multiply('2.50', '333.33'));
        $this->assertSame('0.02', Decimal::multiply('0.10', '0.20'));
        $this->assertSame('1.02', Decimal::multiply('3', '0.34'));
        $this->assertSame('1.00', Decimal::multiply('1', '1'));
        $this->assertSame('-5.00', Decimal::multiply('-2.50', '2'));
    }

    public function test_add_sums_cents(): void
    {
        $this->assertSame('1666.66', Decimal::add('833.33', '833.33'));
        $this->assertSame('-3.50', Decimal::add('-5.50', '2.00'));
    }

    public function test_more_than_two_decimals_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Decimal::multiply('1.234', '1');
    }

    public function test_malformed_input_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Decimal::add('abc', '1.00');
    }
}
