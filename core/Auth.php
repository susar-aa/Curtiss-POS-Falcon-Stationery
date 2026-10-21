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
     * Authenticate user credentials (Username & Password)
     */
    public static function attempt(string $username, string $password): array
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';

            // Brute force protection check (exclude CLI)
            if ($ip !== 'CLI' && $ip !== '127.0.0.1' && $ip !== '::1') {
                $failedAttempts = (int)Database::fetchValue("
                    SELECT COUNT(*) FROM audit_logs
                    WHERE action IN ('LOGIN_FAILED_UNKNOWN_USER', 'LOGIN_FAILED_BAD_PASSWORD', 'LOGIN_FAILED_BAD_PIN')
                      AND ip_address = :ip
                      AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ", [':ip' => $ip]);

                if ($failedAttempts >= 10) {
                    Audit::log('LOGIN_THROTTLED', 'auth', null, null, ['ip' => $ip, 'username' => $username, 'method' => 'password']);
                    return ['success' => false, 'message' => 'Too many failed login attempts. Please wait 5 minutes before trying again.'];
                }
            }

            $user = Database::fetchOne("
                SELECT u.*, r.name AS role_name, r.code AS role_code
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE (LOWER(u.username) = LOWER(:username) OR LOWER(u.email) = LOWER(:email))
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

            return self::initializeSession($user, 'password');

        } catch (Throwable $e) {
            Logger::error("Authentication Attempt Failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Authentication service error. Please try again.'];
        }
    }

    /**
     * Authenticate user with 5-digit PIN
     */
    public static function attemptPin(string $pin): array
    {
        try {
            $pin = trim($pin);
            if (!preg_match('/^\d{5}$/', $pin)) {
                return ['success' => false, 'message' => 'Please enter a valid 5-digit numeric PIN.'];
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';

            // Brute force protection check (exclude CLI)
            if ($ip !== 'CLI' && $ip !== '127.0.0.1' && $ip !== '::1') {
                $failedAttempts = (int)Database::fetchValue("
                    SELECT COUNT(*) FROM audit_logs
                    WHERE action IN ('LOGIN_FAILED_UNKNOWN_USER', 'LOGIN_FAILED_BAD_PASSWORD', 'LOGIN_FAILED_BAD_PIN')
                      AND ip_address = :ip
                      AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ", [':ip' => $ip]);

                if ($failedAttempts >= 10) {
                    Audit::log('LOGIN_THROTTLED', 'auth', null, null, ['ip' => $ip, 'method' => 'pin']);
                    return ['success' => false, 'message' => 'Too many failed login attempts. Please wait 5 minutes before trying again.'];
                }
            }

            // Fetch active users with configured PINs
            $users = Database::fetchAll("
                SELECT u.*, r.name AS role_name, r.code AS role_code
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.status = 'active'
                  AND u.deleted_at IS NULL
                  AND u.pin_hash IS NOT NULL
                  AND u.pin_hash != ''
            ");

            $matchedUser = null;
            foreach ($users as $u) {
                if (password_verify($pin, $u['pin_hash'])) {
                    $matchedUser = $u;
                    break;
                }
            }

            if (!$matchedUser) {
                Audit::log('LOGIN_FAILED_BAD_PIN', 'auth', null, null, ['ip' => $ip]);
                return ['success' => false, 'message' => 'Invalid PIN or inactive account.'];
            }

            return self::initializeSession($matchedUser, 'pin');

        } catch (Throwable $e) {
            Logger::error("PIN Authentication Attempt Failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Authentication service error. Please try again.'];
        }
    }

    /**
     * Initialize user session and branch routing after successful authentication
     */
    private static function initializeSession(array $user, string $loginMethod = 'password'): array
    {
        // Update last_login_at
        Database::execute("UPDATE users SET last_login_at = NOW() WHERE id = :id", [':id' => $user['id']]);

        // Regenerate Session ID
        session_regenerate_id(true);
        $_SESSION['user_id']       = (int)$user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['full_name']     = $user['full_name'];
        $_SESSION['email']         = $user['email'];
        $_SESSION['role_id']       = (int)$user['role_id'];
        $_SESSION['role_name']     = $user['role_name'];
        $_SESSION['role_code']     = $user['role_code'];
        $_SESSION['last_activity'] = time();
        $_SESSION['login_method']  = $loginMethod;

        // Load all assigned roles (multi-role support)
        try {
            $assignedRoles = Database::fetchAll("
                SELECT r.id, r.code, r.name
                FROM user_roles ur
                JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = :uid
                ORDER BY r.id ASC
            ", [':uid' => (int)$user['id']]);
        } catch (Throwable $e) {
            $assignedRoles = [];
        }

        if (empty($assignedRoles)) {
            $assignedRoles = [[
                'id'   => (int)$user['role_id'],
                'code' => $user['role_code'],
                'name' => $user['role_name']
            ]];
        }
        $_SESSION['roles']      = $assignedRoles;
        $_SESSION['role_codes'] = array_column($assignedRoles, 'code');

        // Load User Branches
        $branches = self::getUserBranches((int)$user['id'], $user['role_code']);
        $_SESSION['user_branches'] = $branches;

        if (empty($branches)) {
            self::logout();
            return ['success' => false, 'message' => 'No active branch assigned to this account. Contact Administrator.'];
        }

        $isAdminOrSuperAdmin = in_array(ROLE_SUPER_ADMIN, $_SESSION['role_codes'], true) || in_array(ROLE_ADMIN, $_SESSION['role_codes'], true);
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

        $auditAction = ($loginMethod === 'pin') ? 'LOGIN_PIN_SUCCESS' : 'LOGIN_SUCCESS';
        Audit::log($auditAction, 'auth', (string)$user['id'], null, [
            'username'      => $user['username'],
            'login_method'  => $loginMethod,
            'active_branch' => $_SESSION['active_branch_name'] ?? 'None'
        ]);

        return [
            'success'                => true,
            'multiple_branches'      => $hasMultipleBranches,
            'is_admin_or_superadmin' => $isAdminOrSuperAdmin,
            'needs_branch_selection' => ($isAdminOrSuperAdmin && $hasMultipleBranches),
            'user'                   => $user
        ];
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
     * Role Check Helper (Supports multi-role assignments)
     */
    public static function hasRole(string ...$roles): bool
    {
        if (!self::isLoggedIn()) return false;
        
        $assignedCodes = $_SESSION['role_codes'] ?? [$_SESSION['role_code'] ?? ''];
        foreach ($roles as $r) {
            if (in_array($r, $assignedCodes, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if active logged-in user has Special User role
     */
    public static function isSpecialUser(): bool
    {
        if (!self::isLoggedIn()) return false;
        $assignedCodes = $_SESSION['role_codes'] ?? [$_SESSION['role_code'] ?? ''];
        return in_array(ROLE_SPECIAL_USER, $assignedCodes, true);
    }

    /**
     * Check if active logged-in user has Super Admin role
     */
    public static function isSuperAdmin(): bool
    {
        if (!self::isLoggedIn()) return false;
        $assignedCodes = $_SESSION['role_codes'] ?? [$_SESSION['role_code'] ?? ''];
        return in_array(ROLE_SUPER_ADMIN, $assignedCodes, true) 
            || in_array('superadmin', $assignedCodes, true) 
            || in_array('super_admin', $assignedCodes, true);
    }

    /**
     * Permission Check Helper (Checks permissions across all assigned user roles)
     */
    public static function hasPermission(string $permissionCode): bool
    {
        if (!self::isLoggedIn()) return false;

        // Super Admin bypasses all individual permission restrictions
        if (self::isSuperAdmin()) {
            return true;
        }

        // Collect all role IDs assigned to the user
        $roleIds = [];
        if (!empty($_SESSION['roles']) && is_array($_SESSION['roles'])) {
            $roleIds = array_filter(array_map('intval', array_column($_SESSION['roles'], 'id')));
        }
        if (empty($roleIds)) {
            $singleRoleId = (int)($_SESSION['role_id'] ?? 0);
            if ($singleRoleId > 0) $roleIds = [$singleRoleId];
        }

        if (empty($roleIds)) return false;

        try {
            $inList = implode(',', $roleIds);
            $row = Database::fetchOne("
                SELECT rp.permission_id 
                FROM role_permissions rp
                JOIN permissions p ON rp.permission_id = p.id
                WHERE rp.role_id IN ({$inList}) AND p.permission_code = :code
                LIMIT 1
            ", [':code' => $permissionCode]);

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
        $bId = self::getCurrentBranchId();
        $b = Database::fetchOne("SELECT id, name, code FROM branches WHERE id = :id", [':id' => $bId]);
        if ($b) {
            return $b;
        }
        return [
            'id'   => $bId,
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
        return (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);
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
