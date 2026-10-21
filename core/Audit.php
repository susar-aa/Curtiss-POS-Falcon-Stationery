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
        string|int|null $recordId = null,
        array|object|null $oldValues = null,
        array|object|null $newValues = null
    ): bool {
        try {
            $recordIdStr = $recordId !== null ? (string)$recordId : null;
            $userId = $_SESSION['user_id'] ?? null;
            $branchId = $_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? null;
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
                ':record_id'  => $recordIdStr,
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
     * Build WHERE conditions and parameters for audit queries
     */
    protected static function buildWhere(array $filters): array
    {
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
            $rawTerm = trim($filters['search']);
            $searchTerm = '%' . $rawTerm . '%';

            $cleanNum = rtrim(rtrim(str_replace(',', '', $rawTerm), '0'), '.');
            $hasNumAlt = (is_numeric($cleanNum) && $cleanNum !== '' && $cleanNum !== $rawTerm);
            $cleanNumTerm = '%' . $cleanNum . '%';

            $where[] = "(
                CAST(a.id AS CHAR) LIKE :s1 OR
                CONCAT('#', a.id) LIKE :s2 OR
                a.created_at LIKE :s3 OR
                a.action LIKE :s4 OR
                a.module LIKE :s5 OR
                a.record_id LIKE :s6 OR
                a.ip_address LIKE :s7 OR
                u.full_name LIKE :s8 OR
                u.username LIKE :s9 OR
                b.name LIKE :s10 OR
                a.old_values LIKE :s11 OR
                a.new_values LIKE :s12 OR
                a.user_agent LIKE :s13" . ($hasNumAlt ? " OR a.old_values LIKE :s14 OR a.new_values LIKE :s15" : "") . "
            )";
            for ($i = 1; $i <= 13; $i++) {
                $params[":s{$i}"] = $searchTerm;
            }
            if ($hasNumAlt) {
                $params[':s14'] = $cleanNumTerm;
                $params[':s15'] = $cleanNumTerm;
            }
        }

        return [$where, $params];
    }

    /**
     * Count matching audit logs
     */
    public static function count(array $filters = []): int
    {
        try {
            list($where, $params) = self::buildWhere($filters);
            $whereSql = implode(' AND ', $where);

            $sql = "
                SELECT COUNT(*) as total
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN branches b ON a.branch_id = b.id
                WHERE {$whereSql}
            ";

            $row = Database::fetchOne($sql, $params);
            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            Logger::error("Audit Count Error: " . $e->getMessage(), $e);
            return 0;
        }
    }

    /**
     * Fetch audit logs with filtering and explicit user/branch JOINs
     */
    public static function all(array $filters = []): array
    {
        try {
            list($where, $params) = self::buildWhere($filters);
            $whereSql = implode(' AND ', $where);
            $limit = isset($filters['limit']) ? max(1, (int)$filters['limit']) : 1000;
            $offset = isset($filters['offset']) ? max(0, (int)$filters['offset']) : 0;

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
                LIMIT {$limit} OFFSET {$offset}
            ";

            return Database::fetchAll($sql, $params);
        } catch (Throwable $e) {
            Logger::error("Audit Fetch Error: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Server-side paginated audit logs
     */
    public static function paginate(array $filters = [], int $page = 1, int $limit = 25): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $total = self::count($filters);
        $pages = max(1, (int)ceil($total / $limit));
        if ($page > $pages) $page = $pages;
        $offset = ($page - 1) * $limit;

        $filters['limit'] = $limit;
        $filters['offset'] = $offset;
        $data = self::all($filters);

        return [
            'data'        => $data,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => $pages
        ];
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
