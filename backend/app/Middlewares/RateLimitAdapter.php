<?php
namespace App\Middlewares;

class RateLimitAdapter
{
    private const PREFIX = 'rate_limit_';

    public static function check(string $key, int $maxRequests, int $windowSeconds): ?bool
    {
        // Prefer Redis if available
        if (extension_loaded('redis')) {
            try {
                $r = new \Redis();
                // connect to default redis server; for internal usage you may configure via env
                $host = getenv('REDIS_HOST') ?: '127.0.0.1';
                $port = (int)(getenv('REDIS_PORT') ?: 6379);
                $r->connect($host, $port, 1);
                $redisKey = self::PREFIX . $key;
                $now = time();
                $r->multi();
                $r->zAdd($redisKey, $now, (string)$now . rand());
                $r->zRemRangeByScore($redisKey, 0, $now - $windowSeconds);
                $count = $r->zCard($redisKey);
                $r->expire($redisKey, $windowSeconds + 2);
                $r->exec();
                return $count <= $maxRequests;
            } catch (\Throwable $e) {
                // fallback
            }
        }

        // Next: APCu
        if (function_exists('apcu_fetch')) {
            $storeKey = self::PREFIX . $key;
            $now = time();
            $data = apcu_fetch($storeKey) ?: [];
            // remove old
            $filtered = array_filter($data, fn($t) => ($now - $t) <= $windowSeconds);
            $filtered[] = $now;
            apcu_store($storeKey, $filtered, $windowSeconds + 2);
            return count($filtered) <= $maxRequests;
        }

        // No Redis or APCu: indicate not available (middleware will fallback)
        return null;
    }
}
