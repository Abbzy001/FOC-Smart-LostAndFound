<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = self::requiredEnvironmentValue('DB_HOST');
        $port = self::requiredEnvironmentValue('DB_PORT');
        $database = self::requiredEnvironmentValue('DB_DATABASE');
        $username = self::requiredEnvironmentValue('DB_USERNAME');
        $password = getenv('DB_PASSWORD');
        if ($password === false) {
            $password = '';
        }

        self::$connection = new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return self::$connection;
    }

    private static function requiredEnvironmentValue(string $name): string
    {
        $value = getenv($name);

        if ($value === false || $value === '') {
            throw new RuntimeException("Required environment variable {$name} is not set.");
        }

        return $value;
    }
}
