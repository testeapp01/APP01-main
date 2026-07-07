<?php
namespace App\Helpers;

use PDO;

class Schema
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Return cached columns for a table. Safe to call in high-frequency code.
     * @return string[]
     */
    public static function tableColumns(PDO $pdo, string $table): array
    {
        static $cache = [];
        $key = spl_object_id($pdo) . ':' . $table;
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        try {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $pdo->query("PRAGMA table_info({$table})");
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                $cols = array_values(array_filter(array_map(static fn(array $row) => $row['name'] ?? null, $rows)));
            } else {
                $stmt = $pdo->query("SHOW COLUMNS FROM {$table}");
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                $cols = array_values(array_filter(array_map(static fn(array $row) => $row['Field'] ?? null, $rows)));
            }
        } catch (\Throwable $e) {
            $cols = [];
        }

        $cache[$key] = $cols;
        return $cols;
    }

    public static function hasColumn(PDO $pdo, string $table, string $column): bool
    {
        $cols = self::tableColumns($pdo, $table);
        return in_array($column, $cols, true);
    }

    public static function hasTable(PDO $pdo, string $table): bool
    {
        static $tableCache = [];
        $key = spl_object_id($pdo) . ':' . $table;
        if (isset($tableCache[$key])) {
            return $tableCache[$key];
        }

        try {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:table LIMIT 1");
                $stmt->execute(['table' => $table]);
                $exists = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
                $exists = (bool)$stmt && (bool)$stmt->fetch(PDO::FETCH_NUM);
            }
        } catch (\Throwable $e) {
            $exists = false;
        }

        $tableCache[$key] = $exists;
        return $exists;
    }

    // Instance convenience
    public function tableColumnsInst(string $table): array
    {
        return self::tableColumns($this->pdo, $table);
    }

    public function hasColumnInst(string $table, string $column): bool
    {
        return self::hasColumn($this->pdo, $table, $column);
    }

    public function hasTableInst(string $table): bool
    {
        return self::hasTable($this->pdo, $table);
    }
}
