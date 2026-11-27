<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    /**
     * Wrap each legacy primary label in a single-element list.
     *
     * shortcut: unknown legacy labels are preserved until an explicit label-migration decision.
     */
    public static function backfillCustomers(): void
    {
        DB::table('customers')
            ->whereNull('institutions')
            ->chunkById(200, static function (Collection $customers): void {
                foreach ($customers as $customer) {
                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update([
                            'institutions' => json_encode([$customer->institution], JSON_THROW_ON_ERROR),
                        ]);
                }
            });
    }

    /**
     * Snapshot the customer's primary label onto each invoice that has none.
     *
     * The plain query builder is used on purpose: soft-deleted customers must still
     * supply a label for their historical invoices.
     */
    public static function backfillInvoices(): void
    {
        DB::table('invoices')
            ->whereNull('institution')
            ->chunkById(200, static function (Collection $invoices): void {
                $customerIds = $invoices->pluck('customer_id')->filter()->unique()->all();
                $labels = DB::table('customers')
                    ->whereIn('id', $customerIds)
                    ->pluck('institution', 'id');

                foreach ($invoices as $invoice) {
                    $label = $labels->get($invoice->customer_id);

                    if ($label !== null) {
                        DB::table('invoices')
                            ->where('id', $invoice->id)
                            ->update(['institution' => $label]);
                    }
                }
            });
    }
}
