<?php
namespace App\Logger;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

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
            $handler->setFormatter(new JsonFormatter());
            $log->pushHandler($handler);
            $log->pushProcessor(static function (LogRecord|array $record): LogRecord|array {
                $correlationId = $GLOBALS['CORRELATION_ID'] ?? null;
                $requestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
                $requestUri = $_SERVER['REQUEST_URI'] ?? null;

                if ($record instanceof LogRecord) {
                    return $record->with(extra: array_merge($record->extra, [
                        'correlation_id' => $correlationId,
                        'request_method' => $requestMethod,
                        'request_uri' => $requestUri,
                    ]));
                }

                $record['extra']['correlation_id'] = $correlationId;
                $record['extra']['request_method'] = $requestMethod;
                $record['extra']['request_uri'] = $requestUri;
                return $record;
            });
            self::$logger = $log;
        }
        return self::$logger;
    }
}
