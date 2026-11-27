<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Customer;
use InvalidArgumentException;

final class CustomerInstitution
{
    /**
     * Deduplicate labels and order them by the canonical constant order.
     *
     * @param  list<string>  $values
     * @return list<string>
     */
    public static function normalize(array $values): array
    {
        foreach ($values as $value) {
            if (! in_array($value, Customer::INSTITUTIONS, true)) {
                throw new InvalidArgumentException(sprintf('Unknown customer institution label: %s', $value));
            }
        }

        $unique = array_values(array_unique($values, SORT_STRING));
        $positions = array_flip(Customer::INSTITUTIONS);

        usort($unique, static function (string $a, string $b) use ($positions): int {
            return $positions[$a] <=> $positions[$b];
        });

        return $unique;
    }
}
