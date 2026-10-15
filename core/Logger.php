<?php
/**
 * Centralized Error & Event Logger for Curtiss POS
 * Satisfies Mandatory System Requirement: Always Add Error Logging to find issues.
 */

require_once __DIR__ . '/../config/constants.php';

class Logger
{
    private static ?string $logFile = LOG_PATH;
    private static bool $isLogging = false;

    /**
     * Log an error message to both app log file and database table (error_logs)
     */
    public static function error(string $message, ?Throwable $exception = null, array $context = []): void
    {
        self::log('ERROR', $message, $exception, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, null, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, null, $context);
    }

    public static function exception(Throwable $exception, array $context = []): void
    {
        self::log('CRITICAL', $exception->getMessage(), $exception, $context);
    }

    private static function log(string $level, string $message, ?Throwable $exception = null, array $context = []): void
    {
        if (self::$isLogging) {
            // Guard against recursive loops if an error occurs during error handling or DB reconnection
            return;
        }

        self::$isLogging = true;

        try {
            $timestamp = date('Y-m-d H:i:s');
            $file = $exception ? $exception->getFile() : ($context['file'] ?? 'N/A');
            $line = $exception ? $exception->getLine() : ($context['line'] ?? 0);
            $trace = $exception ? $exception->getTraceAsString() : json_encode($context, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_PRETTY_PRINT);
            
            $userId = $_SESSION['user_id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';

            // 1. Write to log file
            $logEntry = sprintf(
                "[%s] [%s] UserID: %s | IP: %s | %s | File: %s(%d)\nTrace: %s\n----------------------------------------\n",
                $timestamp,
                $level,
                $userId ?? 'Guest/CLI',
                $ip,
                $message,
                $file,
                $line,
                $trace
            );

            $dir = dirname(self::$logFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            @file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);

            // 2. Try DB logging ONLY if Database is already connected. Never trigger reconnect here.
            if (class_exists('Database') && method_exists('Database', 'hasActiveConnection') && Database::hasActiveConnection()) {
                $db = Database::getInstance()->getConnection();
                if ($db) {
                    $stmt = $db->prepare("
                        INSERT INTO error_logs (user_id, error_level, message, file, line, trace, ip_address)
                        VALUES (:user_id, :error_level, :message, :file, :line, :trace, :ip_address)
                    ");
                    $stmt->execute([
                        ':user_id'     => $userId,
                        ':error_level' => $level,
                        ':message'     => substr($message, 0, 2000),
                        ':file'        => $file,
                        ':line'        => $line,
                        ':trace'       => substr($trace ?? '', 0, 10000),
                        ':ip_address'  => $ip
                    ]);
                }
            }
        } catch (Throwable $e) {
            // Fallback: If DB insert fails, log the DB failure to file
            @file_put_contents(self::$logFile, "[" . date('Y-m-d H:i:s') . "] [LOGGER_FAIL] DB Error Log Failed: " . $e->getMessage() . "\n", FILE_APPEND);
        } finally {
            self::$isLogging = false;
        }
    }
}
