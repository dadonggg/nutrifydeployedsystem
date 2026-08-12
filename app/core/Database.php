<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use Exception;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = Container::get('config');
        $db = $config['db'] ?? [];

        $host = $db['host'] ?? '127.0.0.1';
        $name = $db['name'] ?? '';
        $user = $db['user'] ?? '';
        $pass = $db['pass'] ?? '';
        $charset = $db['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_AUTOCOMMIT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        } catch (PDOException $e) {
            // Local fallback for local development
            if ($host !== '127.0.0.1' && $host !== 'localhost') {
                try {
                    self::$pdo = new PDO("mysql:host=127.0.0.1;dbname=webdev;charset=utf8mb4", 'root', '', [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_AUTOCOMMIT => true,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                    ]);
                    return self::$pdo;
                } catch (PDOException $ex) {
                    // Fall through to error
                }
            }
            self::logError('Database connection failed', $e);
            http_response_code(500);
            echo 'Database connection failed: ' . $e->getMessage();
            exit;
        }

        return self::$pdo;
    }

    /**
     * Begin a database transaction
     */
    public static function beginTransaction(): void
    {
        try {
            self::pdo()->beginTransaction();
        } catch (PDOException $e) {
            self::logError('Failed to begin transaction', $e);
            throw $e;
        }
    }

    /**
     * Commit the current transaction
     */
    public static function commit(): void
    {
        try {
            if (self::inTransaction()) {
                self::pdo()->commit();
            }
        } catch (PDOException $e) {
            self::logError('Failed to commit transaction', $e);
            throw $e;
        }
    }

    /**
     * Rollback the current transaction
     */
    public static function rollback(): void
    {
        try {
            if (self::inTransaction()) {
                self::pdo()->rollBack();
            }
        } catch (PDOException $e) {
            self::logError('Failed to rollback transaction', $e);
            throw $e;
        }
    }

    /**
     * Check if currently in a transaction
     */
    public static function inTransaction(): bool
    {
        return self::pdo()->inTransaction();
    }

    /**
     * Log database errors to file
     */
    private static function logError(string $context, \Throwable $e): void
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/database.log';
        $message = sprintf(
            "[%s] %s: %s in %s:%d\nTrace: %s\n\n",
            date('Y-m-d H:i:s'),
            $context,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        @error_log($message, 3, $logFile);
    }
}
