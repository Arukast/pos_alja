<?php

declare(strict_types=1);

namespace App\Support;

final class Decimal
{
    public static function money(mixed $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
