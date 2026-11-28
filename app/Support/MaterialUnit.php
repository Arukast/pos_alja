<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class MaterialUnit
{
    /** @var list<string> */
    public const VALUES = ['m2', 'pcs', 'lembar', 'roll', 'kg', 'liter', 'batang'];

    /**
     * Ordered keyword sets: the first matching unit wins.
     *
     * @var array<string, list<string>>
     */
    private const KEYWORDS = [
        'm2' => ['banner', 'vinyl', 'flexi', 'backdrop', 'spandex', 'spanduk'],
        'lembar' => ['kertas', 'paper', 'hvs', 'art paper', 'sticker', 'stiker'],
        'liter' => ['tinta', 'ink', 'solvent', 'pelarut'],
    ];

    public static function guess(?string $materialName, ?string $categoryName, ?string $archetype): string
    {
        foreach (self::KEYWORDS as $unit => $keywords) {
            if (self::containsAny($materialName, $keywords)) {
                return $unit;
            }
        }

        foreach (self::KEYWORDS as $unit => $keywords) {
            if (self::containsAny($categoryName, $keywords)) {
                return $unit;
            }
        }

        return $archetype === 'surface_area' ? 'm2' : 'pcs';
    }

    /**
     * Assign a unit to every material row from its current material and category names.
     *
     * shortcut: one pass over the materials master; revisit only if it outgrows memory.
     */
    public static function backfill(): void
    {
        $materials = DB::table('materials')
            ->leftJoin('product_categories', 'materials.product_category_id', '=', 'product_categories.id')
            ->get([
                'materials.id as id',
                'materials.name as name',
                'product_categories.name as category_name',
                'product_categories.calculation_archetype as calculation_archetype',
            ]);

        foreach ($materials as $material) {
            $row = (array) $material;

            DB::table('materials')
                ->where('id', $row['id'])
                ->update([
                    'unit' => self::guess(
                        isset($row['name']) ? (string) $row['name'] : null,
                        isset($row['category_name']) ? (string) $row['category_name'] : null,
                        isset($row['calculation_archetype']) ? (string) $row['calculation_archetype'] : null,
                    ),
                ]);
        }
    }

    /**
     * @param  list<string>  $keywords
     */
    private static function containsAny(?string $value, array $keywords): bool
    {
        if ($value === null) {
            return false;
        }

        $value = mb_strtolower($value);

        foreach ($keywords as $keyword) {
            if (str_contains($value, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
