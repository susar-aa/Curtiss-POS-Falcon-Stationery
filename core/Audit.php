<?php
/**
 * Audit Logging Architecture Component
 * Automatically captures operational events for compliance and security audit trails.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';

class Audit
{
    /**
     * Record an audit event in database
     */
    public static function log(
        string $action,
        string $module,
        ?string $recordId = null,
        array|object|null $oldValues = null,
        array|object|null $newValues = null
    ): bool {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $branchId = $_SESSION['active_branch_id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';

            $oldJson = $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null;
            $newJson = $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null;

            $sql = "
                INSERT INTO audit_logs (user_id, branch_id, action, module, record_id, old_values, new_values, ip_address, user_agent)
                VALUES (:user_id, :branch_id, :action, :module, :record_id, :old_values, :new_values, :ip_address, :user_agent)
            ";

            return Database::execute($sql, [
                ':user_id'    => $userId,
                ':branch_id'  => $branchId,
                ':action'     => $action,
                ':module'     => $module,
                ':record_id'  => $recordId,
                ':old_values' => $oldJson,
                ':new_values' => $newJson,
                ':ip_address' => $ip,
                ':user_agent' => substr($userAgent, 0, 255)
            ]);
        } catch (Throwable $e) {
            Logger::error("Audit Log Failure: " . $e->getMessage(), $e);
            return false;
        }
    }

    /**
     * Fetch audit logs with filtering and explicit user/branch JOINs
     */
    public static function all(array $filters = []): array
    {
        try {
            $where = ["1=1"];
            $params = [];

            if (!empty($filters['module'])) {
                $where[] = "a.module = :module";
                $params[':module'] = $filters['module'];
            }

            if (!empty($filters['action'])) {
                $where[] = "a.action = :action";
                $params[':action'] = $filters['action'];
            }

            if (!empty($filters['user_id'])) {
                $where[] = "a.user_id = :user_id";
                $params[':user_id'] = (int)$filters['user_id'];
            }

            if (!empty($filters['branch_id'])) {
                $where[] = "a.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "a.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
            }

            if (!empty($filters['date_to'])) {
                $where[] = "a.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            if (!empty($filters['search'])) {
                $searchTerm = '%' . trim($filters['search']) . '%';
                $where[] = "(
                    a.action LIKE :s1 OR a.module LIKE :s2 OR a.record_id LIKE :s3 OR
                    a.ip_address LIKE :s4 OR u.full_name LIKE :s5 OR u.username LIKE :s6 OR
                    b.name LIKE :s7 OR a.old_values LIKE :s8 OR a.new_values LIKE :s9
                )";
                for ($i = 1; $i <= 9; $i++) {
                    $params[":s{$i}"] = $searchTerm;
                }
            }

            $whereSql = implode(' AND ', $where);
            $limit = isset($filters['limit']) ? (int)$filters['limit'] : 1000;

            $sql = "
                SELECT 
                    a.id,
                    a.user_id,
                    a.branch_id,
                    a.action,
                    a.module,
                    a.record_id,
                    a.old_values,
                    a.new_values,
                    a.ip_address,
                    a.user_agent,
                    a.created_at,
                    COALESCE(u.full_name, 'System / Guest') AS user_name,
                    u.username,
                    COALESCE(b.name, 'Main Branch') AS branch_name
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN branches b ON a.branch_id = b.id
                WHERE {$whereSql}
                ORDER BY a.id DESC
                LIMIT {$limit}
            ";

            return Database::fetchAll($sql, $params);
        } catch (Throwable $e) {
            Logger::error("Audit Fetch Error: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get distinct modules for filter dropdowns
     */
    public static function getModules(): array
    {
        try {
            $rows = Database::fetchAll("SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL AND module != '' ORDER BY module ASC");
            return array_column($rows, 'module');
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get distinct actions for filter dropdowns
     */
    public static function getActions(): array
    {
        try {
            $rows = Database::fetchAll("SELECT DISTINCT action FROM audit_logs WHERE action IS NOT NULL AND action != '' ORDER BY action ASC");
            return array_column($rows, 'action');
        } catch (Throwable $e) {
            return [];
        }
    }
}
