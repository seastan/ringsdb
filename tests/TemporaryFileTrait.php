<?php

namespace App\Tests;

trait TemporaryFileTrait
{
    /**
     * A new empty file in the system's temporary directory.
     */
    private static function temporaryFile(string $prefix): string
    {
        $file = tempnam(sys_get_temp_dir(), $prefix);
        if (false === $file) {
            throw new \RuntimeException('Cannot create a temporary file.');
        }

        return $file;
    }
}
