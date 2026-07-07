<?php
namespace App\Middlewares;

class RateLimitMiddleware
{
    // improved rate limit: try Redis/APCu first, fallback to file per-IP
    public static function check(int $maxRequests = 60, int $windowSeconds = 60): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';

        $ok = \App\Middlewares\RateLimitAdapter::check($ip, $maxRequests, $windowSeconds);
        if ($ok === true) {
            return; // allowed
        }

        if ($ok === false) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Too Many Requests']);
            exit;
        }

        // fallback to file-based
        $dir = __DIR__ . '/../../storage/rate_limit';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file = $dir . '/' . preg_replace('/[^a-z0-9_.-]/i', '_', $ip) . '.json';

        $now = time();
        $data = ['timestamps' => []];
        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            $data = $raw ? json_decode($raw, true) ?: ['timestamps' => []] : ['timestamps' => []];
        }

        // purge old
        $data['timestamps'] = array_filter($data['timestamps'], fn($t) => ($now - $t) <= $windowSeconds);
        if (count($data['timestamps']) >= $maxRequests) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Too Many Requests']);
            exit;
        }

        $data['timestamps'][] = $now;
        @file_put_contents($file, json_encode($data));
    }
}
