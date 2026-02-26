<?php
namespace App\Logger;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\RotatingFileHandler;

class Logger
{
    private static ?MonoLogger $logger = null;

    public static function get(string $name = 'app'): MonoLogger
    {
        if (self::$logger === null) {
            $log = new MonoLogger($name);
            $dir = __DIR__ . '/../../storage/logs';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $path = $dir . '/app.log';
            $handler = new RotatingFileHandler($path, 7, MonoLogger::DEBUG);
            $log->pushHandler($handler);
            self::$logger = $log;
        }
        return self::$logger;
    }
}
