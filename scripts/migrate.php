<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Config\Database;

$database = Database::connection();
$database->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(190) NOT NULL PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$migrationDirectory = __DIR__ . '/../database/migrations';
$files = glob($migrationDirectory . '/*.sql');

if ($files === false) {
    throw new RuntimeException('Unable to list database migrations.');
}

sort($files, SORT_STRING);
$appliedQuery = $database->query('SELECT migration FROM schema_migrations');
$applied = array_fill_keys($appliedQuery->fetchAll(PDO::FETCH_COLUMN), true);

foreach ($files as $file) {
    $name = basename($file);
    if (isset($applied[$name])) {
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("Unable to read database migration {$name}.");
    }

    $database->exec($sql);
    $statement = $database->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
    $statement->execute(['migration' => $name]);
    fwrite(STDOUT, "Applied {$name}\n");
}

fwrite(STDOUT, "Database migrations are up to date.\n");
