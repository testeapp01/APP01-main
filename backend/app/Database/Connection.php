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

            $host = getenv('DB_HOST') ?: 'db';
            $port = getenv('DB_PORT') ?: '3306';
            $db   = getenv('DB_NAME') ?: 'hortifrutnectar';
            $user = getenv('DB_USER') ?: 'admin';
            $pass = getenv('DB_PASS') ?: 'nautico2@';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,

                // importante para Docker + MySQL 8
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ];

            try {
                self::$pdo = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {

                // log interno container
                error_log("DB ERROR: " . $e->getMessage());

                // resposta limpa pro browser
                http_response_code(500);
                die("Erro interno de conexão com banco.");
            }
        }

        return self::$pdo;
    }
}