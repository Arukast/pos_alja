<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class Decimal
{
    public static function money(mixed $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }

    public static function multiply(string $a, string $b): string
    {
        $product = self::cents($a) * self::cents($b);
        $rounded = intdiv(abs($product) + 50, 100);

        return self::format($product < 0 ? -$rounded : $rounded);
    }

    public static function add(string $a, string $b): string
    {
        return self::format(self::cents($a) + self::cents($b));
    }

    private static function cents(string $value): int
    {
        if (preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', $value, $matches) !== 1) {
            throw new InvalidArgumentException("Invalid decimal value [{$value}].");
        }

        $cents = (int) $matches[2] * 100 + (int) str_pad($matches[3] ?? '', 2, '0');

        return $matches[1] === '-' ? -$cents : $cents;
    }

    private static function format(int $cents): string
    {
        $absolute = abs($cents);

        return ($cents < 0 ? '-' : '')
            .intdiv($absolute, 100)
            .'.'
            .str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
