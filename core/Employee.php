<?php
/**
 * Curtiss POS - Employee Management Core Model
 * Handles Employee Directory, POS Sales Reps, Profiling, Soft-Deletes & Audit Trails.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Auth.php';

class Employee
{
    /**
     * Generate continuous formatted employee code (EMP-0001, EMP-0002...)
     */
    public static function generateEmployeeCode(): string
    {
        try {
            $row = Database::fetchOne("
                SELECT MAX(CAST(SUBSTRING(employee_code, 5) AS UNSIGNED)) AS max_code 
                FROM employees 
                WHERE employee_code LIKE 'EMP-%'
            ");
            $next = ((int)($row['max_code'] ?? 0)) + 1;
            return sprintf('EMP-%04d', $next);
        } catch (Throwable $e) {
            Logger::error("Failed to generate employee code: " . $e->getMessage(), $e);
            return 'EMP-' . rand(1000, 9999);
        }
    }

    /**
     * List employees with filtering, search, pagination, and branch isolation
     */
    public static function all(array $filters = [], int $page = 1, int $limit = 25): array
    {
        try {
            $where = [];
            $params = [];

            // Trash bin vs active records
            if (!empty($filters['view']) && $filters['view'] === 'deleted') {
                $where[] = "e.deleted_at IS NOT NULL";
            } else {
                $where[] = "e.deleted_at IS NULL";
            }

            // Branch filter
            if (!empty($filters['branch_id'])) {
                $where[] = "e.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            // Status filter
            if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'], true)) {
                $where[] = "e.status = :status";
                $params[':status'] = $filters['status'];
            }

            // Role filter
            if (!empty($filters['job_role'])) {
                $where[] = "e.job_role = :job_role";
                $params[':job_role'] = trim($filters['job_role']);
            }

            // Search query (code, name, phone, nic)
            if (!empty($filters['search'])) {
                $term = trim($filters['search']);
                $where[] = "(
                    e.employee_code LIKE :s1 OR 
                    e.full_name LIKE :s2 OR 
                    e.phone LIKE :s3 OR 
                    e.nic LIKE :s4 OR 
                    e.job_role LIKE :s5
                )";
                $params[':s1'] = "%{$term}%";
                $params[':s2'] = "%{$term}%";
                $params[':s3'] = "%{$term}%";
                $params[':s4'] = "%{$term}%";
                $params[':s5'] = "%{$term}%";
            }

            $whereSql = !empty($where) ? implode(' AND ', $where) : '1=1';

            // Count total
            $countRow = Database::fetchOne("
                SELECT COUNT(e.id) AS total 
                FROM employees e 
                JOIN branches b ON e.branch_id = b.id 
                WHERE {$whereSql}
            ", $params);
            $total = (int)($countRow['total'] ?? 0);

            // Fetch paginated items
            $offset = max(0, ($page - 1) * $limit);
            $items = Database::fetchAll("
                SELECT 
                    e.id, 
                    e.employee_code, 
                    e.full_name, 
                    e.phone, 
                    e.whatsapp, 
                    e.nic, 
                    e.job_role, 
                    e.is_sales_rep, 
                    e.branch_id, 
                    b.name AS branch_name, 
                    e.joining_date, 
                    e.basic_salary, 
                    e.status, 
                    e.bank_name, 
                    e.bank_account_name, 
                    e.bank_account_no, 
                    e.notes, 
                    e.created_at, 
                    e.deleted_at
                FROM employees e
                JOIN branches b ON e.branch_id = b.id
                WHERE {$whereSql}
                ORDER BY e.id DESC
                LIMIT {$limit} OFFSET {$offset}
            ", $params);

            return [
                'items'       => $items,
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => max(1, (int)ceil($total / $limit))
            ];
        } catch (Throwable $e) {
            Logger::error("Employee::all query failed: " . $e->getMessage(), $e);
            return ['items' => [], 'total' => 0, 'page' => $page, 'limit' => $limit, 'total_pages' => 1];
        }
    }

    /**
     * Find single employee by ID
     */
    public static function find(int $id, bool $includeDeleted = false): ?array
    {
        return self::getById($id, $includeDeleted);
    }

    /**
     * Get single employee by ID
     */
    public static function getById(int $id, bool $includeDeleted = false): ?array
    {
        try {
            $delSql = $includeDeleted ? '' : 'AND e.deleted_at IS NULL';
            return Database::fetchOne("
                SELECT 
                    e.id, 
                    e.employee_code, 
                    e.full_name, 
                    e.phone, 
                    e.whatsapp, 
                    e.address, 
                    e.nic, 
                    e.job_role, 
                    e.is_sales_rep, 
                    e.branch_id, 
                    b.name AS branch_name, 
                    e.joining_date, 
                    e.basic_salary, 
                    e.status, 
                    e.bank_name, 
                    e.bank_account_name, 
                    e.bank_account_no, 
                    e.notes, 
                    e.created_at, 
                    e.updated_at, 
                    e.deleted_at
                FROM employees e
                JOIN branches b ON e.branch_id = b.id
                WHERE e.id = :id {$delSql}
            ", [':id' => $id]);
        } catch (Throwable $e) {
            Logger::error("Employee::find failed for ID {$id}: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Create new employee record
     */
    public static function create(array $data): array
    {
        try {
            $fullName = trim($data['full_name'] ?? '');
            if (empty($fullName)) {
                return ['success' => false, 'message' => 'Full Name is required.'];
            }

            $branchId = (int)($data['branch_id'] ?? Auth::getCurrentBranchId() ?? 1);
            if ($branchId <= 0) {
                return ['success' => false, 'message' => 'Valid Branch assignment is required.'];
            }

            // Verify branch exists
            $branch = Database::fetchOne("SELECT id, name FROM branches WHERE id = :id", [':id' => $branchId]);
            if (!$branch) {
                return ['success' => false, 'message' => 'Selected branch does not exist.'];
            }

            $employeeCode = trim($data['employee_code'] ?? '');
            if (empty($employeeCode)) {
                $employeeCode = self::generateEmployeeCode();
            } else {
                $dupCode = Database::fetchOne("SELECT id FROM employees WHERE employee_code = :c", [':c' => $employeeCode]);
                if ($dupCode) {
                    return ['success' => false, 'message' => "Employee ID '{$employeeCode}' already exists."];
                }
            }

            $nic = trim($data['nic'] ?? '');
            if (!empty($nic)) {
                $dupNic = Database::fetchOne("SELECT id FROM employees WHERE nic = :nic AND deleted_at IS NULL", [':nic' => $nic]);
                if ($dupNic) {
                    return ['success' => false, 'message' => "An employee with NIC '{$nic}' already exists."];
                }
            }

            $phone = trim($data['phone'] ?? '');
            $whatsapp = trim($data['whatsapp'] ?? '');
            $address = trim($data['address'] ?? '');
            $jobRole = trim($data['job_role'] ?? 'Sales Rep');
            $isSalesRep = !empty($data['is_sales_rep']) ? 1 : ($jobRole === 'Sales Rep' ? 1 : 0);
            $joiningDate = !empty($data['joining_date']) ? date('Y-m-d', strtotime($data['joining_date'])) : date('Y-m-d');
            $basicSalary = round(max(0.00, (float)($data['basic_salary'] ?? 0)), 2);
            $bankName = trim($data['bank_name'] ?? '');
            $bankAccountName = trim($data['bank_account_name'] ?? '');
            $bankAccountNo = trim($data['bank_account_no'] ?? '');
            $notes = trim($data['notes'] ?? '');

            Database::execute("
                INSERT INTO employees (
                    employee_code, full_name, phone, whatsapp, address, nic, 
                    job_role, is_sales_rep, branch_id, joining_date, basic_salary, 
                    status, bank_name, bank_account_name, bank_account_no, notes, created_at
                ) VALUES (
                    :employee_code, :full_name, :phone, :whatsapp, :address, :nic, 
                    :job_role, :is_sales_rep, :branch_id, :joining_date, :basic_salary, 
                    'active', :bank_name, :bank_account_name, :bank_account_no, :notes, NOW()
                )
            ", [
                ':employee_code'     => $employeeCode,
                ':full_name'         => $fullName,
                ':phone'             => $phone ?: null,
                ':whatsapp'          => $whatsapp ?: null,
                ':address'           => $address ?: null,
                ':nic'               => $nic ?: null,
                ':job_role'          => $jobRole,
                ':is_sales_rep'      => $isSalesRep,
                ':branch_id'         => $branchId,
                ':joining_date'      => $joiningDate,
                ':basic_salary'      => $basicSalary,
                ':bank_name'         => $bankName ?: null,
                ':bank_account_name' => $bankAccountName ?: null,
                ':bank_account_no'   => $bankAccountNo ?: null,
                ':notes'             => $notes ?: null
            ]);

            $id = (int)Database::lastInsertId();

            Audit::log('EMPLOYEE_CREATED', 'employees', (string)$id, null, [
                'employee_code' => $employeeCode,
                'full_name'     => $fullName,
                'job_role'      => $jobRole,
                'branch_id'     => $branchId,
                'basic_salary'  => $basicSalary
            ]);

            return [
                'success'       => true,
                'message'       => "Employee '{$fullName}' ({$employeeCode}) created successfully.",
                'employee_id'   => $id,
                'employee_code' => $employeeCode
            ];
        } catch (Throwable $e) {
            Logger::error("Employee::create failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to create employee: ' . $e->getMessage()];
        }
    }

    /**
     * Update existing employee profile
     */
    public static function update(int $id, array $data): array
    {
        try {
            $existing = self::find($id, true);
            if (!$existing) {
                return ['success' => false, 'message' => 'Employee record not found.'];
            }

            $fullName = trim($data['full_name'] ?? $existing['full_name']);
            if (empty($fullName)) {
                return ['success' => false, 'message' => 'Full Name cannot be empty.'];
            }

            $branchId = (int)($data['branch_id'] ?? $existing['branch_id']);
            $nic = trim($data['nic'] ?? $existing['nic'] ?? '');
            if (!empty($nic)) {
                $dupNic = Database::fetchOne("
                    SELECT id FROM employees 
                    WHERE nic = :nic AND id != :id AND deleted_at IS NULL
                ", [':nic' => $nic, ':id' => $id]);
                if ($dupNic) {
                    return ['success' => false, 'message' => "Another employee already has NIC '{$nic}'."];
                }
            }

            $phone = trim($data['phone'] ?? $existing['phone'] ?? '');
            $whatsapp = trim($data['whatsapp'] ?? $existing['whatsapp'] ?? '');
            $address = trim($data['address'] ?? $existing['address'] ?? '');
            $jobRole = trim($data['job_role'] ?? $existing['job_role']);
            $isSalesRep = isset($data['is_sales_rep']) ? (int)$data['is_sales_rep'] : ($jobRole === 'Sales Rep' ? 1 : (int)$existing['is_sales_rep']);
            $joiningDate = !empty($data['joining_date']) ? date('Y-m-d', strtotime($data['joining_date'])) : $existing['joining_date'];
            $basicSalary = isset($data['basic_salary']) ? round(max(0.00, (float)$data['basic_salary']), 2) : (float)$existing['basic_salary'];
            $bankName = trim($data['bank_name'] ?? $existing['bank_name'] ?? '');
            $bankAccountName = trim($data['bank_account_name'] ?? $existing['bank_account_name'] ?? '');
            $bankAccountNo = trim($data['bank_account_no'] ?? $existing['bank_account_no'] ?? '');
            $notes = trim($data['notes'] ?? $existing['notes'] ?? '');

            Database::execute("
                UPDATE employees SET
                    full_name = :full_name,
                    phone = :phone,
                    whatsapp = :whatsapp,
                    address = :address,
                    nic = :nic,
                    job_role = :job_role,
                    is_sales_rep = :is_sales_rep,
                    branch_id = :branch_id,
                    joining_date = :joining_date,
                    basic_salary = :basic_salary,
                    bank_name = :bank_name,
                    bank_account_name = :bank_account_name,
                    bank_account_no = :bank_account_no,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id
            ", [
                ':full_name'         => $fullName,
                ':phone'             => $phone ?: null,
                ':whatsapp'          => $whatsapp ?: null,
                ':address'           => $address ?: null,
                ':nic'               => $nic ?: null,
                ':job_role'          => $jobRole,
                ':is_sales_rep'      => $isSalesRep,
                ':branch_id'         => $branchId,
                ':joining_date'      => $joiningDate,
                ':basic_salary'      => $basicSalary,
                ':bank_name'         => $bankName ?: null,
                ':bank_account_name' => $bankAccountName ?: null,
                ':bank_account_no'   => $bankAccountNo ?: null,
                ':notes'             => $notes ?: null,
                ':id'                => $id
            ]);

            Audit::log('EMPLOYEE_UPDATED', 'employees', (string)$id, $existing, [
                'full_name'    => $fullName,
                'job_role'     => $jobRole,
                'basic_salary' => $basicSalary,
                'branch_id'    => $branchId
            ]);

            return ['success' => true, 'message' => "Employee '{$fullName}' updated successfully."];
        } catch (Throwable $e) {
            Logger::error("Employee::update failed for ID {$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to update employee: ' . $e->getMessage()];
        }
    }

    /**
     * Fast toggle status (active <-> inactive) without opening edit modal
     */
    public static function toggleStatus(int $id): array
    {
        try {
            $employee = self::find($id, true);
            if (!$employee) {
                return ['success' => false, 'message' => 'Employee not found.'];
            }

            $newStatus = ($employee['status'] === 'active') ? 'inactive' : 'active';
            Database::execute("UPDATE employees SET status = :s, updated_at = NOW() WHERE id = :id", [
                ':s'  => $newStatus,
                ':id' => $id
            ]);

            Audit::log('EMPLOYEE_STATUS_TOGGLED', 'employees', (string)$id, ['status' => $employee['status']], ['status' => $newStatus]);

            return [
                'success'    => true,
                'message'    => "Employee status changed to {$newStatus}.",
                'new_status' => $newStatus
            ];
        } catch (Throwable $e) {
            Logger::error("Employee::toggleStatus failed for ID {$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to toggle status: ' . $e->getMessage()];
        }
    }

    /**
     * Soft delete employee record into Trash Bin
     */
    public static function delete(int $id): array
    {
        try {
            $employee = self::find($id, false);
            if (!$employee) {
                return ['success' => false, 'message' => 'Employee not found or already deleted.'];
            }

            Database::execute("UPDATE employees SET deleted_at = NOW(), status = 'inactive' WHERE id = :id", [':id' => $id]);

            Audit::log('EMPLOYEE_SOFT_DELETED', 'employees', (string)$id, $employee, ['deleted_at' => date('Y-m-d H:i:s')]);

            return ['success' => true, 'message' => "Employee '{$employee['full_name']}' moved to Trash Bin."];
        } catch (Throwable $e) {
            Logger::error("Employee::delete failed for ID {$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to delete employee: ' . $e->getMessage()];
        }
    }

    /**
     * Restore soft-deleted employee record from Trash Bin
     */
    public static function restore(int $id): array
    {
        try {
            $employee = self::find($id, true);
            if (!$employee || empty($employee['deleted_at'])) {
                return ['success' => false, 'message' => 'Employee is not in Trash Bin.'];
            }

            Database::execute("UPDATE employees SET deleted_at = NULL, status = 'active', updated_at = NOW() WHERE id = :id", [':id' => $id]);

            Audit::log('EMPLOYEE_RESTORED', 'employees', (string)$id, null, ['restored_at' => date('Y-m-d H:i:s')]);

            return ['success' => true, 'message' => "Employee '{$employee['full_name']}' restored successfully."];
        } catch (Throwable $e) {
            Logger::error("Employee::restore failed for ID {$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to restore employee: ' . $e->getMessage()];
        }
    }

    /**
     * Fetch active sales reps assigned to branch for POS Terminal selection
     */
    public static function getSalesReps(int $branchId): array
    {
        try {
            return Database::fetchAll("
                SELECT id, employee_code, full_name, job_role, phone 
                FROM employees 
                WHERE status = 'active' 
                  AND deleted_at IS NULL 
                  AND (branch_id = :b OR branch_id IS NULL)
                  AND is_sales_rep = 1
                ORDER BY full_name ASC
            ", [':b' => $branchId]);
        } catch (Throwable $e) {
            Logger::error("Employee::getSalesReps failed: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Fetch active employees for branch (for payroll or general selectors)
     */
    public static function getActiveEmployeesByBranch(int $branchId = 0): array
    {
        try {
            $where = ["e.status = 'active' AND e.deleted_at IS NULL"];
            $params = [];
            if ($branchId > 0) {
                $where[] = "e.branch_id = :b";
                $params[':b'] = $branchId;
            }
            $whereSql = implode(' AND ', $where);

            return Database::fetchAll("
                SELECT 
                    e.id, 
                    e.employee_code, 
                    e.full_name, 
                    e.job_role, 
                    e.basic_salary, 
                    e.branch_id, 
                    b.name AS branch_name, 
                    e.phone, 
                    e.bank_name, 
                    e.bank_account_no
                FROM employees e
                JOIN branches b ON e.branch_id = b.id
                WHERE {$whereSql}
                ORDER BY e.full_name ASC
            ", $params);
        } catch (Throwable $e) {
            Logger::error("Employee::getActiveEmployeesByBranch failed: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get employee sales performance metrics
     */
    public static function getSalesPerformance(int $employeeId, ?string $startDate = null, ?string $endDate = null): array
    {
        try {
            $where = ["s.sales_rep_id = :emp_id AND s.status = 'completed'"];
            $params = [':emp_id' => $employeeId];

            if (!empty($startDate)) {
                $where[] = "DATE(s.created_at) >= :start_date";
                $params[':start_date'] = $startDate;
            }
            if (!empty($endDate)) {
                $where[] = "DATE(s.created_at) <= :end_date";
                $params[':end_date'] = $endDate;
            }

            $whereSql = implode(' AND ', $where);

            $summary = Database::fetchOne("
                SELECT 
                    COUNT(s.id) AS total_invoices,
                    COALESCE(SUM(s.total_amount), 0.00) AS total_sales_volume,
                    COALESCE(SUM(s.discount_amount), 0.00) AS total_discounts_given
                FROM sales s
                WHERE {$whereSql}
            ", $params);

            return [
                'total_invoices'        => (int)($summary['total_invoices'] ?? 0),
                'total_sales_volume'    => (float)($summary['total_sales_volume'] ?? 0.00),
                'total_discounts_given' => (float)($summary['total_discounts_given'] ?? 0.00)
            ];
        } catch (Throwable $e) {
            Logger::error("Employee::getSalesPerformance failed for ID {$employeeId}: " . $e->getMessage(), $e);
            return ['total_invoices' => 0, 'total_sales_volume' => 0.00, 'total_discounts_given' => 0.00];
        }
    }
}
