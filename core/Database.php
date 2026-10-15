<?php
/**
 * Singleton Database Connection Wrapper
 * Uses PDO prepared statements for high security against SQL Injection.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/Logger.php';

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private function __construct()
    {
        try {
            $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", DB_HOST, DB_PORT, DB_NAME);
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            Logger::error("Database Connection Failure: " . $e->getMessage(), $e);
            throw new Exception("Database Connection Failed: " . $e->getMessage());
        }
    }

    public static function hasActiveConnection(): bool
    {
        return self::$instance !== null && self::$instance->pdo !== null;
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): ?PDO
    {
        return $this->pdo;
    }

    /**
     * Helper to run prepared SELECT queries safely
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        try {
            $stmt = self::getInstance()->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Logger::error("SQL fetchAll Failed: " . $e->getMessage(), $e, ['sql' => $sql, 'params' => $params]);
            throw $e;
        }
    }

    /**
     * Helper to run single row prepared SELECT queries
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        try {
            $stmt = self::getInstance()->getConnection()->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            Logger::error("SQL fetchOne Failed: " . $e->getMessage(), $e, ['sql' => $sql, 'params' => $params]);
            throw $e;
        }
    }

    /**
     * Helper to run INSERT/UPDATE/DELETE statement
     */
    public static function execute(string $sql, array $params = []): bool
    {
        try {
            $stmt = self::getInstance()->getConnection()->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            Logger::error("SQL execute Failed: " . $e->getMessage(), $e, ['sql' => $sql, 'params' => $params]);
            throw $e;
        }
    }

    public static function lastInsertId(): string
    {
        return self::getInstance()->getConnection()->lastInsertId();
    }
}
