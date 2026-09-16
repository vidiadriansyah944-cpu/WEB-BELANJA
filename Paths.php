<?php

declare(strict_types=1);

namespace TokoKita\Support;

/**
 * Resolusi path absolut di dalam proyek.
 */
final class Paths
{
    private static string $root = '';

    public static function setRoot(string $root): void
    {
        self::$root = rtrim(str_replace('\\', '/', $root), '/');
    }

    public static function root(string $relative = ''): string
    {
        return $relative === '' ? self::$root : self::$root . '/' . ltrim($relative, '/');
    }

    public static function storage(string $relative = ''): string
    {
        return self::root('storage' . ($relative === '' ? '' : '/' . ltrim($relative, '/')));
    }

    public static function invoices(string $relative = ''): string
    {
        return self::storage('invoices' . ($relative === '' ? '' : '/' . ltrim($relative, '/')));
    }

    public static function receipts(string $relative = ''): string
    {
        return self::storage('receipts' . ($relative === '' ? '' : '/' . ltrim($relative, '/')));
    }

    public static function oauth(string $relative = ''): string
    {
        return self::storage('oauth' . ($relative === '' ? '' : '/' . ltrim($relative, '/')));
    }

    public static function logs(string $relative = ''): string
    {
        return self::storage('logs' . ($relative === '' ? '' : '/' . ltrim($relative, '/')));
    }

    public static function database(): string
    {
        $configured = \TokoKita\Config::string('DB_PATH', 'storage/toko.sqlite');

        return str_starts_with($configured, '/') || preg_match('/^[A-Za-z]:/', $configured) === 1
            ? $configured
            : self::root($configured);
    }

    public static function ensureDirectory(string $directory): string
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        return $directory;
    }

    public static function toRelative(string $absolute): string
    {
        $normalized = str_replace('\\', '/', $absolute);
        $root = self::$root . '/';

        if (str_starts_with($normalized, $root)) {
            return substr($normalized, strlen($root));
        }

        return $normalized;
    }
}
