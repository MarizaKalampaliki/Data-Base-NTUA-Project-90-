<?php
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
        ]);

        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("SET collation_connection = 'utf8mb4_unicode_ci'");
    }

    return $pdo;
}

function run_select(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    foreach ($params as $name => $value) {
        $stmt->bindValue(':' . $name, $value);
    }
    $stmt->execute();
    return $stmt->fetchAll();
}

function fetch_one_value(string $sql, array $params = [], $fallback = null)
{
    try {
        $rows = run_select($sql, $params);
        if (!$rows) {
            return $fallback;
        }
        $first = $rows[0];
        return reset($first);
    } catch (Throwable $e) {
        return $fallback;
    }
}

function table_count(string $table): int
{
    try {
        return (int) fetch_one_value("SELECT COUNT(*) FROM `$table`", [], 0);
    } catch (Throwable $e) {
        return 0;
    }
}
