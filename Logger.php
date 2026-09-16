<?php

declare(strict_types=1);

namespace TokoKita\Support;

/**
 * Pencatat log sederhana berbasis berkas harian.
 */
final class Logger
{
    private const CHANNEL = 'app';

    /** @var list<array{level: string, message: string, context: array<string, mixed>, time: string}> */
    private static array $memory = [];

    public static function ensureDirectory(): void
    {
        Paths::ensureDirectory(Paths::logs());
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function debug(string $message, array $context = []): void
    {
        self::write('DEBUG', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    /**
     * @return list<array{level: string, message: string, context: array<string, mixed>, time: string}>
     */
    public static function recent(): array
    {
        return array_reverse(self::$memory);
    }

    public static function clearMemory(): void
    {
        self::$memory = [];
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function write(string $level, string $message, array $context): void
    {
        $time = date('Y-m-d H:i:s');

        self::$memory[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'time' => $time,
        ];

        if (count(self::$memory) > 200) {
            array_shift(self::$memory);
        }

        $line = sprintf('[%s] %s: %s', $time, $level, $message);

        if ($context !== []) {
            $encoded = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $line .= ' ' . ($encoded === false ? '{}' : $encoded);
        }

        self::ensureDirectory();
        $file = Paths::logs(self::CHANNEL . '-' . date('Y-m-d') . '.log');

        $handle = @fopen($file, 'ab');

        if ($handle === false) {
            return;
        }

        flock($handle, LOCK_EX);
        fwrite($handle, $line . PHP_EOL);
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
