<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

/**
 * Proveedor singleton de conexión PDO hacia MySQL / MariaDB.
 */
final class Database
{
    private static ?PDO $conexion = null;

    private function __construct()
    {
    }

    public static function conexion(): PDO
    {
        if (self::$conexion !== null) {
            return self::$conexion;
        }

        cargarEntorno(dirname(__DIR__, 2));

        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $db   = env('DB_NAME', 'restaurante_pedidos');
        $user = env('DB_USER', 'root');
        $pass = env('DB_PASS', '');

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        self::$conexion = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return self::$conexion;
    }
}
