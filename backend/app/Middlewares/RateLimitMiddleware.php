<?php
namespace App\Middlewares;

use App\Helpers\Response;

class RateLimitMiddleware
{
    /**
     * General-purpose rate limiter.
     *
     * @param int         $maxRequests    Max requests allowed in the window.
     * @param int         $windowSeconds  Window size in seconds.
     * @param string|null $key            Optional bucket key. Defaults to the real client IP.
     */
    public static function check(int $maxRequests = 60, int $windowSeconds = 60, ?string $key = null): void
    {
        $bucketKey = $key ?? self::getClientIp();

        $ok = \App\Middlewares\RateLimitAdapter::check($bucketKey, $maxRequests, $windowSeconds);
        if ($ok === true) {
            return;
        }

        if ($ok === false) {
            Response::error('Too Many Requests', 429);
            exit;
        }

        // Fallback: file-based per-bucket (only when Redis/APCu unavailable)
        $dir = __DIR__ . '/../../storage/rate_limit';
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $safeKey = preg_replace('/[^a-z0-9_.-]/i', '_', $bucketKey);
        $file    = $dir . '/' . $safeKey . '.json';

        $now  = time();
        $data = ['timestamps' => []];
        if (file_exists($file)) {
            $raw  = @file_get_contents($file);
            $data = ($raw !== false) ? (json_decode($raw, true) ?: ['timestamps' => []]) : ['timestamps' => []];
        }

        $data['timestamps'] = array_values(
            array_filter($data['timestamps'], static fn($t) => ($now - $t) <= $windowSeconds)
        );

        if (count($data['timestamps']) >= $maxRequests) {
            Response::error('Too Many Requests', 429);
            exit;
        }

        $data['timestamps'][] = $now;
        // Atomic write via tmp file to avoid race condition
        $tmp = $file . '.tmp.' . getmypid();
        @file_put_contents($tmp, json_encode($data));
        @rename($tmp, $file);
    }

    /**
     * Login-specific rate limiter: 5 attempts per email per 5 minutes.
     * Uses a hashed email as bucket key so the raw email is never stored on disk.
     */
    public static function checkLoginAttempts(string $emailIdentifier): void
    {
        $bucketKey = 'login:' . hash('sha256', strtolower(trim($emailIdentifier)));
        self::check(5, 300, $bucketKey);
    }

    /**
     * Resolve the real client IP, respecting a configured trusted proxy CIDR.
     * Set TRUSTED_PROXY_CIDR env var (e.g. "172.16.0.0/12") to enable.
     */
    private static function getClientIp(): string
    {
        $remoteAddr       = $_SERVER['REMOTE_ADDR'] ?? '';
        $trustedProxyCidr = getenv('TRUSTED_PROXY_CIDR') ?: '';

        if ($trustedProxyCidr !== '' && self::ipInCidr($remoteAddr, $trustedProxyCidr)) {
            $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            if ($forwarded !== '') {
                $ips      = array_map('trim', explode(',', $forwarded));
                $clientIp = filter_var($ips[0] ?? '', FILTER_VALIDATE_IP);
                if ($clientIp !== false) {
                    return $clientIp;
                }
            }
        }

        return $remoteAddr ?: 'unknown';
    }

    private static function ipInCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }
        [$subnet, $bits] = explode('/', $cidr, 2);
        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }
        $mask = -1 << (32 - (int) $bits);
        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
