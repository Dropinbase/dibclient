<?php

declare(strict_types=1);

namespace DropinScript\Mcp;

use RuntimeException;

final class ConnConfigLoader
{
    /**
     * Load database connection configurations from the Conn.php file.
     * Expects that configs/Dib.php has already been required so the
     * real DIB class (with its $DATABASES property) exists.
     *
     * @return array<int|string, array<string, mixed>>
     */
    public function load(string $connFilePath): array
    {
        if (!is_file($connFilePath)) {
            throw new RuntimeException('Connection file not found: ' . $connFilePath);
        }

        require $connFilePath;

        if (!isset(\DIB::$DATABASES) || !is_array(\DIB::$DATABASES)) {
            throw new RuntimeException('No database configuration was loaded from ' . $connFilePath);
        }

        return \DIB::$DATABASES;
    }
}
