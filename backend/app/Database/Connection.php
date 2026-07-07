<?php
namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $pdo = null;

    public static function getPdo(): PDO
    {
        if (self::$pdo === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $db   = getenv('DB_DATABASE') ?: (getenv('DB_NAME') ?: 'hortifrutnectar');
            $user = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'root');
            $pass = getenv('DB_PASSWORD');
            if ($pass === false) {
                $pass = getenv('DB_PASS');
            }
            if ($pass === false) {
                $pass = '';
            }

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $sslVerifyAttr = constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT');
                if (is_int($sslVerifyAttr)) {
                    $options[$sslVerifyAttr] = false;
                }
            }

            try {
                self::$pdo = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log("DB ERROR: " . $e->getMessage());
                throw new PDOException('Erro interno de conexão com banco.', (int)$e->getCode(), $e);
            }
        }

        return self::$pdo;
    }
}