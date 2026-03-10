<?php
namespace App\Observability;

class Metrics
{
    private static float $startedAt;
    private static array $counters = [
        'http_requests_total' => 0,
        'http_errors_total' => 0,
        'http_5xx_total' => 0,
    ];

    public static function boot(): void
    {
        if (!isset(self::$startedAt)) {
            self::$startedAt = microtime(true);
        }
    }

    public static function increment(string $name, int $by = 1): void
    {
        self::boot();
        self::$counters[$name] = (self::$counters[$name] ?? 0) + $by;
    }

    public static function snapshot(): array
    {
        self::boot();
        return [
            'uptime_seconds' => (int)(microtime(true) - self::$startedAt),
            'counters' => self::$counters,
        ];
    }
}
