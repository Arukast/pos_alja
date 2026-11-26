<?php

declare(strict_types=1);

namespace App\Services\Backup;

interface DatabaseDumper
{
    /**
     * Write a compressed database dump to the given absolute path.
     */
    public function dump(string $absolutePath): void;
}
