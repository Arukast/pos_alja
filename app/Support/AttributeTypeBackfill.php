<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AttributeTypeBackfill
{
    /**
     * @var array<string, array{string, int}>
     */
    private const DEFAULTS = [
        'finishing' => ['Finishing', 1],
        'cut' => ['Potong', 2],
        'display' => ['Display', 3],
    ];

    public static function run(): void
    {
        foreach (self::DEFAULTS as $slug => [$name, $sortOrder]) {
            self::insertIfMissing($slug, $name, $sortOrder);
        }

        $legacyTypes = DB::table('attributes')
            ->select('type')
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        $sortOrder = (int) (DB::table('attribute_types')->max('sort_order') ?? 0);

        foreach ($legacyTypes as $type) {
            $type = (string) $type;

            if (DB::table('attribute_types')->where('slug', $type)->exists()) {
                continue;
            }

            $name = Str::headline($type) ?: $type;
            $base = $name;
            $suffix = 2;

            while (DB::table('attribute_types')->where('name', $name)->exists()) {
                $name = $base.'-'.$suffix;
                $suffix++;
            }

            $sortOrder++;
            self::insertIfMissing($type, $name, $sortOrder);
        }
    }

    private static function insertIfMissing(string $slug, string $name, int $sortOrder): void
    {
        if (DB::table('attribute_types')->where('slug', $slug)->exists()) {
            return;
        }

        DB::table('attribute_types')->insert([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $sortOrder,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
