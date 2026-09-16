<?php

declare(strict_types=1);

namespace TokoKita;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use TokoKita\Support\Logger;
use TokoKita\Support\Paths;

/**
 * Koneksi PDO tunggal (SQLite) beserta pembantu query.
 */
final class Database
{
    private static ?PDO $connection = null;

    private static ?string $activePath = null;

    public static function connection(?string $path = null): PDO
    {
        $target = $path ?? Paths::database();

        if (self::$connection instanceof PDO && self::$activePath === $target) {
            return self::$connection;
        }

        Paths::ensureDirectory(dirname($target));

        try {
            $pdo = new PDO('sqlite:' . $target, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            Logger::error('Gagal membuka database', ['path' => $target, 'error' => $e->getMessage()]);
            throw new RuntimeException('Tidak dapat membuka database: ' . $e->getMessage(), 0, $e);
        }

        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');

        self::$connection = $pdo;
        self::$activePath = $target;

        return $pdo;
    }

    public static function reset(): void
    {
        self::$connection = null;
        self::$activePath = null;
    }

    /**
     * @param array<string|int, mixed> $bindings
     */
    public static function run(string $sql, array $bindings = []): PDOStatement
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($bindings);

        return $statement;
    }

    /**
     * @param array<string|int, mixed> $bindings
     * @return array<string, mixed>|null
     */
    public static function first(string $sql, array $bindings = []): ?array
    {
        $row = self::run($sql, $bindings)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string|int, mixed> $bindings
     * @return list<array<string, mixed>>
     */
    public static function all(string $sql, array $bindings = []): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = self::run($sql, $bindings)->fetchAll();

        return $rows;
    }

    /**
     * @param array<string|int, mixed> $bindings
     */
    public static function scalar(string $sql, array $bindings = []): mixed
    {
        $value = self::run($sql, $bindings)->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function insert(string $table, array $values): int
    {
        $columns = array_keys($values);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        self::run($sql, $values);

        return (int) self::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, mixed> $where
     */
    public static function update(string $table, array $values, array $where): int
    {
        $sets = [];
        $bindings = [];

        foreach ($values as $column => $value) {
            $sets[] = $column . ' = :set_' . $column;
            $bindings['set_' . $column] = $value;
        }

        $conditions = [];

        foreach ($where as $column => $value) {
            $conditions[] = $column . ' = :where_' . $column;
            $bindings['where_' . $column] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $sets),
            implode(' AND ', $conditions)
        );

        return self::run($sql, $bindings)->rowCount();
    }

    /**
     * @param array<string, mixed> $where
     */
    public static function delete(string $table, array $where): int
    {
        $conditions = [];
        $bindings = [];

        foreach ($where as $column => $value) {
            $conditions[] = $column . ' = :where_' . $column;
            $bindings['where_' . $column] = $value;
        }

        $sql = sprintf('DELETE FROM %s WHERE %s', $table, implode(' AND ', $conditions));

        return self::run($sql, $bindings)->rowCount();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public static function tableExists(string $table, ?PDO $pdo = null): bool
    {
        $pdo ??= self::connection();
        $statement = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = :name");
        $statement->execute(['name' => $table]);

        return $statement->fetchColumn() !== false;
    }
}
