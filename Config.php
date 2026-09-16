<?php

declare(strict_types=1);

namespace TokoKita;

/**
 * Pembaca konfigurasi dari berkas .env.
 * Mendukung akses bertitik: Config::get('store.tax_percent').
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $values = [];

    private static bool $loaded = false;

    public static function load(string $envPath): void
    {
        self::$values = [];
        self::$loaded = false;

        if (is_file($envPath)) {
            self::parseFile($envPath);
        }

        self::$loaded = true;
    }

    /**
     * Menyuntik nilai langsung (dipakai pada pengujian).
     *
     * @param array<string, mixed> $values
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::$values[$key] = $value;
        }
        self::$loaded = true;
    }

    public static function set(string $key, mixed $value): void
    {
        self::$values[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        return $default;
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::get($key, $default);

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    public static function float(string $key, float $default = 0.0): float
    {
        $value = self::get($key, $default);

        return is_numeric($value) ? (float) $value : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }

    /**
     * @return list<string>
     */
    public static function scopes(): array
    {
        $raw = self::string('GOOGLE_OAUTH_SCOPES');

        if ($raw === '') {
            return [
                'https://www.googleapis.com/auth/gmail.send',
                'https://www.googleapis.com/auth/drive.file',
                'https://www.googleapis.com/auth/userinfo.email',
            ];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $raw) ?: [])));
    }

    private static function parseFile(string $path): void
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            self::$values[trim($name)] = self::normalizeValue($value);
        }
    }

    private static function normalizeValue(string $value): string
    {
        $value = trim($value);

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }
}
