<?php
// Simple concurrent load tester using curl_multi
// Usage: php load_test.php http://localhost:8000/api/v1/compras 100 10
$url = $argv[1] ?? null;
$total = isset($argv[2]) ? (int)$argv[2] : 100;
$concurrency = isset($argv[3]) ? (int)$argv[3] : 10;
if (!$url) { echo "Usage: php load_test.php <url> [total=100] [concurrency=10]\n"; exit(1); }

$start = microtime(true);
$completed = 0;
$success = 0;
$fails = 0;

function make_handles($n, $url) {
    $chs = [];
    for ($i=0;$i<$n;$i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => 'x'.$i]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $chs[] = $ch;
    }
    return $chs;
}

$remaining = $total;
while ($remaining > 0) {
    $batch = min($concurrency, $remaining);
    $mh = curl_multi_init();
    $chs = make_handles($batch, $url);
    foreach ($chs as $ch) curl_multi_add_handle($mh, $ch);

    $running = null;
    do { curl_multi_exec($mh, $running); curl_multi_select($mh, 0.5); } while ($running > 0);

    foreach ($chs as $ch) {
        $resp = curl_multi_getcontent($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code >= 200 && $code < 300) $success++; else $fails++;
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        $completed++;
    }

    curl_multi_close($mh);
    $remaining -= $batch;
}

$time = microtime(true) - $start;
echo "Completed: $completed, Success: $success, Fails: $fails, Time: ${time}s\n";
