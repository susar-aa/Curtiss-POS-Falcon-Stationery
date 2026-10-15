<?php
/**
 * Authentication & Session Management Core Component
 * Implements password hashing, secure sessions, multi-branch assignment,
 * inactivity timeout, and permission architecture.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Settings.php';

class Auth
{
    /**
     * Authenticate user credentials
     */
    public static function attempt(string $username, string $password): array
    {
        try {
            $user = Database::fetchOne("
                SELECT u.*, r.name AS role_name, r.code AS role_code
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE (u.username = :username OR u.email = :email)
                  AND u.status = 'active'
                  AND u.deleted_at IS NULL
                LIMIT 1
            ", [':username' => $username, ':email' => $username]);

            if (!$user) {
                Audit::log('LOGIN_FAILED_UNKNOWN_USER', 'auth', null, null, ['username' => $username]);
                return ['success' => false, 'message' => 'Invalid credentials or inactive account.'];
            }

            if (!password_verify($password, $user['password_hash'])) {
                Audit::log('LOGIN_FAILED_BAD_PASSWORD', 'auth', (string)$user['id'], null, ['username' => $username]);
                return ['success' => false, 'message' => 'Invalid credentials or inactive account.'];
            }

            // Update last_login_at
            Database::execute("UPDATE users SET last_login_at = NOW() WHERE id = :id", [':id' => $user['id']]);

            // Set Session Data
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role_id'] = (int)$user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['role_code'] = $user['role_code'];
            $_SESSION['last_activity'] = time();

            // Load User Branches
            $branches = self::getUserBranches((int)$user['id'], $user['role_code']);
            $_SESSION['user_branches'] = $branches;

            if (empty($branches)) {
                self::logout();
                return ['success' => false, 'message' => 'No active branch assigned to this account. Contact Administrator.'];
            }

            $isAdminOrSuperAdmin = in_array($user['role_code'], [ROLE_SUPER_ADMIN, ROLE_ADMIN], true);
            $hasMultipleBranches = count($branches) > 1;

            // Set default active branch
            if (count($branches) === 1) {
                $_SESSION['active_branch_id']   = (int)$branches[0]['id'];
                $_SESSION['branch_id']          = (int)$branches[0]['id'];
                $_SESSION['active_branch_name'] = $branches[0]['name'];
                $_SESSION['active_branch_code'] = $branches[0]['code'];
                $_SESSION['branch_selected']    = true;
            } else {
                // Determine initial fallback branch
                $primary = array_values(array_filter($branches, fn($b) => $b['is_primary'] == 1));
                $initial = !empty($primary) ? $primary[0] : $branches[0];

                $_SESSION['active_branch_id']   = (int)$initial['id'];
                $_SESSION['branch_id']          = (int)$initial['id'];
                $_SESSION['active_branch_name'] = $initial['name'];
                $_SESSION['active_branch_code'] = $initial['code'];

                if ($isAdminOrSuperAdmin) {
                    // Admin & Super Admin must explicitly choose an operating branch upon login
                    $_SESSION['branch_selected'] = false;
                } else {
                    $_SESSION['branch_selected'] = true;
                }
            }

            Audit::log('LOGIN_SUCCESS', 'auth', (string)$user['id'], null, [
                'username' => $user['username'],
                'active_branch' => $_SESSION['active_branch_name'] ?? 'None'
            ]);

            return [
                'success' => true,
                'multiple_branches' => $hasMultipleBranches,
                'is_admin_or_superadmin' => $isAdminOrSuperAdmin,
                'needs_branch_selection' => ($isAdminOrSuperAdmin && $hasMultipleBranches),
                'user' => $user
            ];

        } catch (Throwable $e) {
            Logger::error("Authentication Attempt Failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Authentication service error: ' . $e->getMessage()];
        }
    }

    /**
     * Fetch all branches accessible by user based on role & user_branches table
     */
    public static function getUserBranches(int $userId, string $roleCode): array
    {
        try {
            if ($roleCode === ROLE_SUPER_ADMIN || $roleCode === ROLE_ADMIN) {
                // Super Admin and Admin have access to all active branches
                return Database::fetchAll("
                    SELECT id, name, code, address, phone, email, 1 AS is_primary
                    FROM branches
                    WHERE status = 'active' AND deleted_at IS NULL
                    ORDER BY name ASC
                ");
            } else {
                return Database::fetchAll("
                    SELECT b.id, b.name, b.code, b.address, b.phone, b.email, ub.is_primary
                    FROM user_branches ub
                    JOIN branches b ON ub.branch_id = b.id
                    WHERE ub.user_id = :user_id
                      AND b.status = 'active'
                      AND b.deleted_at IS NULL
                    ORDER BY ub.is_primary DESC, b.name ASC
                ", [':user_id' => $userId]);
            }
        } catch (Throwable $e) {
            Logger::error("Failed to load user branches for User #$userId: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Switch Active Branch for multi-branch user
     */
    public static function switchBranch(int $branchId): bool
    {
        if (!self::isLoggedIn()) return false;

        $userId = $_SESSION['user_id'];
        $roleCode = $_SESSION['role_code'];
        $allowedBranches = self::getUserBranches($userId, $roleCode);

        $matched = array_values(array_filter($allowedBranches, fn($b) => (int)$b['id'] === $branchId));
        if (empty($matched)) {
            Logger::warning("Unauthorized branch switch attempt by User #$userId to Branch #$branchId");
            return false;
        }

        $oldBranch = $_SESSION['active_branch_name'] ?? 'None';
        $_SESSION['active_branch_id'] = $matched[0]['id'];
        $_SESSION['branch_id'] = $matched[0]['id'];
        $_SESSION['active_branch_name'] = $matched[0]['name'];
        $_SESSION['active_branch_code'] = $matched[0]['code'];
        $_SESSION['branch_selected'] = true;

        Audit::log('BRANCH_SWITCH', 'auth', (string)$branchId, ['from' => $oldBranch], ['to' => $matched[0]['name']]);
        return true;
    }

    /**
     * Inactivity Timeout Verification
     */
    public static function checkInactivityTimeout(): void
    {
        if (!self::isLoggedIn()) return;

        $timeoutSeconds = (int)Settings::get('session_inactivity_timeout', (string)DEFAULT_INACTIVITY_TIMEOUT);
        $lastActivity = $_SESSION['last_activity'] ?? time();

        if (time() - $lastActivity > $timeoutSeconds) {
            Audit::log('SESSION_EXPIRED', 'auth', (string)$_SESSION['user_id']);
            $returnUrl = $_SERVER['REQUEST_URI'] ?? '';
            self::logout();
            $target = 'login?expired=1';
            if (!empty($returnUrl) && !str_contains($returnUrl, 'login') && !str_contains($returnUrl, 'logout')) {
                $target .= '&return_url=' . urlencode($returnUrl);
            }
            header('Location: ' . $target);
            exit();
        }

        $_SESSION['last_activity'] = time();
    }

    /**
     * Require authentication middleware
     */
    public static function requireAuth(): void
    {
        if (!self::isLoggedIn()) {
            $returnUrl = $_SERVER['REQUEST_URI'] ?? '';
            $target = 'login';
            if (!empty($returnUrl) && !str_contains($returnUrl, 'login') && !str_contains($returnUrl, 'logout')) {
                $target .= '?return_url=' . urlencode($returnUrl);
            }
            header('Location: ' . $target);
            exit();
        }

        self::checkInactivityTimeout();
    }

    /**
     * Require authentication middleware (Alias for requireAuth)
     */
    public static function requireLogin(): void
    {
        self::requireAuth();
    }

    /**
     * Check if session is logged in
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Check if session is logged in (Alias for isLoggedIn)
     */
    public static function check(): bool
    {
        return self::isLoggedIn();
    }

    /**
     * Current authenticated user array
     */
    public static function user(): ?array
    {
        if (!self::isLoggedIn()) return null;
        return [
            'id'                 => $_SESSION['user_id'],
            'username'           => $_SESSION['username'],
            'full_name'          => $_SESSION['full_name'],
            'email'              => $_SESSION['email'],
            'role_id'            => $_SESSION['role_id'],
            'role_name'          => $_SESSION['role_name'],
            'role_code'          => $_SESSION['role_code'],
            'active_branch_id'   => $_SESSION['active_branch_id'] ?? null,
            'active_branch_name' => $_SESSION['active_branch_name'] ?? 'Not Selected',
            'active_branch_code' => $_SESSION['active_branch_code'] ?? 'N/A',
        ];
    }

    /**
     * Role Check Helper
     */
    public static function hasRole(string ...$roles): bool
    {
        if (!self::isLoggedIn()) return false;
        return in_array($_SESSION['role_code'], $roles, true);
    }

    /**
     * Check if active logged-in user has Special User role
     */
    public static function isSpecialUser(): bool
    {
        if (!self::isLoggedIn()) return false;
        return ($_SESSION['role_code'] ?? '') === ROLE_SPECIAL_USER;
    }

    /**
     * Permission Check Helper
     */
    public static function hasPermission(string $permissionCode): bool
    {
        if (!self::isLoggedIn()) return false;

        $roleCode = $_SESSION['role_code'] ?? '';
        if ($roleCode === ROLE_SUPER_ADMIN) {
            return true;
        }

        $roleId = (int)($_SESSION['role_id'] ?? 0);
        if ($roleId <= 0) return false;

        try {
            $row = Database::fetchOne("
                SELECT rp.permission_id 
                FROM role_permissions rp
                JOIN permissions p ON rp.permission_id = p.id
                WHERE rp.role_id = :role_id AND p.permission_code = :code
            ", [':role_id' => $roleId, ':code' => $permissionCode]);

            return $row !== null;
        } catch (Throwable $e) {
            Logger::error("Failed permission check for '$permissionCode': " . $e->getMessage(), $e);
            return false;
        }
    }

    /**
     * Enforce permission middleware
     */
    public static function requirePermission(string $permissionCode): void
    {
        self::requireAuth();
        if (!self::hasPermission($permissionCode)) {
            http_response_code(403);
            echo "<div style='padding:20px; font-family:sans-serif; color:#dc2626; background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; margin:20px;'><strong>403 Forbidden:</strong> You do not have permission to access this resource ('" . htmlspecialchars($permissionCode) . "').</div>";
            exit();
        }
    }

    /**
     * Get current authenticated user array (Alias for user)
     */
    public static function getCurrentUser(): ?array
    {
        return self::user();
    }

    /**
     * Get active branch array for current user
     */
    public static function getCurrentBranch(): ?array
    {
        if (!self::isLoggedIn()) return null;
        return [
            'id'   => $_SESSION['active_branch_id'] ?? null,
            'name' => $_SESSION['active_branch_name'] ?? 'Not Selected',
            'code' => $_SESSION['active_branch_code'] ?? 'N/A'
        ];
    }

    /**
     * Get current user ID
     */
    public static function getUserId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    /**
     * Get current active branch ID
     */
    public static function getCurrentBranchId(): int
    {
        return (int)($_SESSION['active_branch_id'] ?? 1);
    }


    /**
     * Generate CSRF Token
     */
    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF Token
     */
    public static function validateCsrfToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Logout user
     */
    public static function logout(): void
    {
        if (self::isLoggedIn()) {
            Audit::log('LOGOUT', 'auth', (string)$_SESSION['user_id']);
        }
        
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        @session_destroy();
    }
}
