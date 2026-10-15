<?php
/**
 * Curtiss POS - Payroll Management Core Model
 * Handles Monthly Payroll Periods, Statutory Sri Lankan EPF/ETF (8%/12%/3%),
 * Double-Entry Accounting Posting, Payslips, Salary Advances & Payments.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Accounting.php';
require_once __DIR__ . '/Employee.php';
require_once __DIR__ . '/BusinessDay.php';
require_once __DIR__ . '/Banking.php';
require_once __DIR__ . '/PettyCash.php';

class Payroll
{
    /**
     * Generate unique payment number
     */
    public static function generatePaymentNo(): string
    {
        return 'PAY-SAL-' . date('Ym') . '-' . sprintf('%04d', rand(1000, 9999));
    }

    /**
     * Generate unique advance number
     */
    public static function generateAdvanceNo(): string
    {
        return 'ADV-' . date('Ym') . '-' . sprintf('%04d', rand(1000, 9999));
    }

    /**
     * Generate unique remittance number
     */
    public static function generateRemittanceNo(): string
    {
        return 'REMIT-' . date('Ym') . '-' . sprintf('%04d', rand(1000, 9999));
    }

    /**
     * List payroll periods with summary totals
     */
    public static function getPeriods(array $filters = [], int $page = 1, int $limit = 25): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['branch_id'])) {
                $where[] = "(pp.branch_id = :branch_id OR pp.branch_id IS NULL)";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['status'])) {
                $where[] = "pp.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['search'])) {
                $term = trim($filters['search']);
                $where[] = "(pp.period_code LIKE :s1 OR pp.period_name LIKE :s2)";
                $params[':s1'] = "%{$term}%";
                $params[':s2'] = "%{$term}%";
            }

            $whereSql = !empty($where) ? implode(' AND ', $where) : '1=1';

            $countRow = Database::fetchOne("
                SELECT COUNT(pp.id) AS total FROM payroll_periods pp WHERE {$whereSql}
            ", $params);
            $total = (int)($countRow['total'] ?? 0);

            $offset = max(0, ($page - 1) * $limit);
            $items = Database::fetchAll("
                SELECT 
                    pp.*,
                    b.name AS branch_name,
                    u.full_name AS created_by_name,
                    appr.full_name AS approved_by_name,
                    COALESCE(pe_cnt.total_employees, 0) AS total_employees,
                    COALESCE(remit.remitted_epf_etf, 0) AS remitted_epf_etf,
                    COALESCE(remit.remit_count, 0) AS remit_count
                FROM payroll_periods pp
                LEFT JOIN branches b ON pp.branch_id = b.id
                LEFT JOIN users u ON pp.created_by = u.id
                LEFT JOIN users appr ON pp.approved_by = appr.id
                LEFT JOIN (
                    SELECT payroll_period_id, COUNT(*) AS total_employees
                    FROM payroll_entries
                    GROUP BY payroll_period_id
                ) pe_cnt ON pe_cnt.payroll_period_id = pp.id
                LEFT JOIN (
                    SELECT payroll_period_id, SUM(total_amount) AS remitted_epf_etf, COUNT(*) AS remit_count
                    FROM epf_etf_remittances
                    GROUP BY payroll_period_id
                ) remit ON remit.payroll_period_id = pp.id
                WHERE {$whereSql}
                ORDER BY pp.start_date DESC, pp.id DESC
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
            Logger::error("Payroll::getPeriods failed: " . $e->getMessage(), $e);
            return ['items' => [], 'total' => 0, 'page' => $page, 'limit' => $limit, 'total_pages' => 1];
        }
    }

    /**
     * Find single payroll period by ID
     */
    public static function findPeriod(int $id): ?array
    {
        try {
            return Database::fetchOne("
                SELECT 
                    pp.*,
                    b.name AS branch_name,
                    u.full_name AS created_by_name,
                    appr.full_name AS approved_by_name,
                    COALESCE(remit.remitted_epf_etf, 0) AS remitted_epf_etf,
                    COALESCE(remit.remit_count, 0) AS remit_count
                FROM payroll_periods pp
                LEFT JOIN branches b ON pp.branch_id = b.id
                LEFT JOIN users u ON pp.created_by = u.id
                LEFT JOIN users appr ON pp.approved_by = appr.id
                LEFT JOIN (
                    SELECT payroll_period_id, SUM(total_amount) AS remitted_epf_etf, COUNT(*) AS remit_count
                    FROM epf_etf_remittances
                    GROUP BY payroll_period_id
                ) remit ON remit.payroll_period_id = pp.id
                WHERE pp.id = :id
            ", [':id' => $id]);
        } catch (Throwable $e) {
            Logger::error("Payroll::findPeriod failed for ID {$id}: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Create monthly payroll period and populate employee entries
     */
    public static function createPeriod(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $periodCode = trim($data['period_code'] ?? ''); // e.g. 2026-10
            $periodName = trim($data['period_name'] ?? ''); // e.g. October 2026
            $startDate  = !empty($data['start_date']) ? date('Y-m-d', strtotime($data['start_date'])) : '';
            $endDate    = !empty($data['end_date']) ? date('Y-m-d', strtotime($data['end_date'])) : '';
            $branchId   = !empty($data['branch_id']) ? (int)$data['branch_id'] : (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? Auth::getCurrentBranchId() ?? 1);
            $notes      = trim($data['notes'] ?? '');

            if (empty($periodCode) || empty($periodName) || empty($startDate) || empty($endDate)) {
                return ['success' => false, 'message' => 'Period code, name, start date, and end date are required.'];
            }

            // Check duplicate period code for the same branch
            $branchCheckSql = $branchId ? "AND branch_id = :bid" : "AND branch_id IS NULL";
            $branchCheckParams = [':pcode' => $periodCode];
            if ($branchId) {
                $branchCheckParams[':bid'] = $branchId;
            }
            $existing = Database::fetchOne("
                SELECT id FROM payroll_periods WHERE period_code = :pcode {$branchCheckSql} AND status != 'cancelled'
            ", $branchCheckParams);

            if ($existing) {
                return ['success' => false, 'message' => "Payroll period '{$periodCode}' already exists for this branch/scope."];
            }

            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            // 1. Insert Period Header
            $stmtPeriod = $db->prepare("
                INSERT INTO payroll_periods (
                    period_code, period_name, start_date, end_date, branch_id, 
                    status, created_by, notes, created_at
                ) VALUES (
                    :period_code, :period_name, :start_date, :end_date, :branch_id,
                    'draft', :created_by, :notes, NOW()
                )
            ");
            $stmtPeriod->execute([
                ':period_code' => $periodCode,
                ':period_name' => $periodName,
                ':start_date'  => $startDate,
                ':end_date'    => $endDate,
                ':branch_id'   => $branchId,
                ':created_by'  => $userId,
                ':notes'       => $notes
            ]);

            $periodId = (int)$db->lastInsertId();

            // 2. Fetch active employees to include in this period
            $empWhere = ["e.deleted_at IS NULL AND e.status = 'active'"];
            $empParams = [];
            if ($branchId) {
                $empWhere[] = "e.branch_id = :branch_id";
                $empParams[':branch_id'] = $branchId;
            }
            $empWhereSql = implode(' AND ', $empWhere);

            $employees = Database::fetchAll("
                SELECT id, employee_code, full_name, branch_id, basic_salary 
                FROM employees e
                WHERE {$empWhereSql}
                ORDER BY e.employee_code ASC
            ", $empParams);

            if (empty($employees)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'No active employees found to generate payroll for.'];
            }

            $totalBasic       = 0.00;
            $totalAllowances  = 0.00;
            $totalCommissions = 0.00;
            $totalGross       = 0.00;
            $totalEmpEpf      = 0.00;
            $totalEmprEpf     = 0.00;
            $totalEmprEtf     = 0.00;
            $totalDeductions  = 0.00;
            $totalNetSalary   = 0.00;

            $stmtEntry = $db->prepare("
                INSERT INTO payroll_entries (
                    payroll_period_id, employee_id, branch_id, basic_salary, 
                    allowances, bonuses, commissions, overtime_amount, gross_salary, 
                    employee_epf_rate, employee_epf_amount, salary_advances_deducted, 
                    other_deductions, total_deductions, net_salary, 
                    employer_epf_rate, employer_epf_amount, employer_etf_rate, employer_etf_amount, 
                    paid_amount, status, created_at
                ) VALUES (
                    :payroll_period_id, :employee_id, :branch_id, :basic_salary,
                    :allowances, :bonuses, :commissions, :overtime_amount, :gross_salary,
                    :employee_epf_rate, :employee_epf_amount, :salary_advances_deducted,
                    :other_deductions, :total_deductions, :net_salary,
                    :employer_epf_rate, :employer_epf_amount, :employer_etf_rate, :employer_etf_amount,
                    0.00, 'draft', NOW()
                )
            ");

            foreach ($employees as $emp) {
                $empId = (int)$emp['id'];
                $empBranchId = (int)$emp['branch_id'];
                $basic = round((float)$emp['basic_salary'], 2);

                // Auto-suggest salary advance deduction if employee has active advance
                $advRow = Database::fetchOne("
                    SELECT COALESCE(SUM(remaining_amount), 0) AS total_adv 
                    FROM employee_advances 
                    WHERE employee_id = :eid AND status = 'active'
                ", [':eid' => $empId]);
                $remainingAdvance = round((float)($advRow['total_adv'] ?? 0), 2);
                // Deduct up to half basic or total remaining advance
                $suggestedAdvDeduct = min($remainingAdvance, round($basic * 0.5, 2));

                $allowances   = 0.00;
                $bonuses      = 0.00;
                $commissions  = 0.00;
                $overtime     = 0.00;

                // Sri Lankan Statutory EPF & ETF (Employees' Provident Fund Act):
                // Total monthly earnings include basic salary, commissions, allowances & bonuses.
                $epfEligibleBase = round($basic + $commissions + $allowances + $bonuses, 2);
                $empEpf          = round($epfEligibleBase * 0.08, 2);  // Employee EPF 8%
                $emprEpf         = round($epfEligibleBase * 0.12, 2);  // Employer EPF 12%
                $emprEtf         = round($epfEligibleBase * 0.03, 2);  // Employer ETF 3%

                $gross        = round($basic + $allowances + $bonuses + $commissions + $overtime, 2);
                $otherDeduct  = 0.00;
                $deductions   = round($empEpf + $suggestedAdvDeduct + $otherDeduct, 2);
                $net          = max(0.00, round($gross - $deductions, 2));

                $stmtEntry->execute([
                    ':payroll_period_id'        => $periodId,
                    ':employee_id'              => $empId,
                    ':branch_id'                => $empBranchId,
                    ':basic_salary'             => $basic,
                    ':allowances'               => $allowances,
                    ':bonuses'                  => $bonuses,
                    ':commissions'              => $commissions,
                    ':overtime_amount'          => $overtime,
                    ':gross_salary'             => $gross,
                    ':employee_epf_rate'        => 8.00,
                    ':employee_epf_amount'      => $empEpf,
                    ':salary_advances_deducted' => $suggestedAdvDeduct,
                    ':other_deductions'         => $otherDeduct,
                    ':total_deductions'         => $deductions,
                    ':net_salary'               => $net,
                    ':employer_epf_rate'        => 12.00,
                    ':employer_epf_amount'      => $emprEpf,
                    ':employer_etf_rate'        => 3.00,
                    ':employer_etf_amount'      => $emprEtf
                ]);

                $totalBasic       += $basic;
                $totalAllowances  += $allowances;
                $totalCommissions += $commissions;
                $totalGross       += $gross;
                $totalEmpEpf      += $empEpf;
                $totalEmprEpf     += $emprEpf;
                $totalEmprEtf     += $emprEtf;
                $totalDeductions  += $deductions;
                $totalNetSalary   += $net;
            }

            // Update period totals
            $stmtUpdPeriod = $db->prepare("
                UPDATE payroll_periods SET
                    total_basic = :tb,
                    total_allowances = :ta,
                    total_commissions = :tc,
                    total_gross = :tg,
                    total_employee_epf = :te_epf,
                    total_employer_epf = :ter_epf,
                    total_employer_etf = :ter_etf,
                    total_deductions = :td,
                    total_net_salary = :tn,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmtUpdPeriod->execute([
                ':tb'      => $totalBasic,
                ':ta'      => $totalAllowances,
                ':tc'      => $totalCommissions,
                ':tg'      => $totalGross,
                ':te_epf'  => $totalEmpEpf,
                ':ter_epf' => $totalEmprEpf,
                ':ter_etf' => $totalEmprEtf,
                ':td'      => $totalDeductions,
                ':tn'      => $totalNetSalary,
                ':id'      => $periodId
            ]);

            $db->commit();

            Audit::log('PAYROLL_PERIOD_CREATED', 'payroll', (string)$periodId, null, [
                'period_code'     => $periodCode,
                'period_name'     => $periodName,
                'employee_count'  => count($employees),
                'total_gross'     => $totalGross,
                'total_net_salary'=> $totalNetSalary
            ]);

            return [
                'success'   => true,
                'message'   => "Payroll period '{$periodName}' created with " . count($employees) . " employees.",
                'period_id' => $periodId
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Payroll::createPeriod failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error creating payroll period: ' . $e->getMessage()];
        }
    }

    /**
     * Get entries for a payroll period
     * Includes total sales covered by each employee during the period date range.
     */
    public static function getPeriodEntries(int $periodId): array
    {
        try {
            $period = Database::fetchOne("
                SELECT start_date, end_date, branch_id 
                FROM payroll_periods 
                WHERE id = :id
            ", [':id' => $periodId]);

            $entries = Database::fetchAll("
                SELECT 
                    pe.*,
                    e.employee_code,
                    e.full_name AS employee_name,
                    e.job_role,
                    e.is_sales_rep,
                    e.bank_name,
                    e.bank_account_no,
                    b.name AS branch_name
                FROM payroll_entries pe
                JOIN employees e ON pe.employee_id = e.id
                JOIN branches b ON pe.branch_id = b.id
                WHERE pe.payroll_period_id = :pid
                ORDER BY e.employee_code ASC
            ", [':pid' => $periodId]);

            if (!empty($entries) && $period) {
                $startDt = $period['start_date'] . ' 00:00:00';
                $endDt   = $period['end_date'] . ' 23:59:59';

                // Aggregate sales covered by each sales rep for this payroll period in a single fast indexed query
                $salesAgg = Database::fetchAll("
                    SELECT 
                        s.sales_rep_id,
                        COALESCE(SUM(s.total_amount), 0.00) AS period_sales,
                        COUNT(s.id) AS sales_count
                    FROM sales s
                    WHERE s.sales_rep_id IS NOT NULL
                      AND s.status != 'cancelled'
                      AND s.created_at >= :start_dt
                      AND s.created_at <= :end_dt
                    GROUP BY s.sales_rep_id
                ", [
                    ':start_dt' => $startDt,
                    ':end_dt'   => $endDt
                ]);

                $salesByRep = [];
                foreach ($salesAgg as $row) {
                    $salesByRep[(int)$row['sales_rep_id']] = [
                        'period_sales' => round((float)$row['period_sales'], 2),
                        'sales_count'  => (int)$row['sales_count']
                    ];
                }

                foreach ($entries as &$entry) {
                    $empId = (int)$entry['employee_id'];
                    $entry['period_sales'] = $salesByRep[$empId]['period_sales'] ?? 0.00;
                    $entry['sales_count']  = $salesByRep[$empId]['sales_count'] ?? 0;
                }
                unset($entry);
            }

            return $entries;
        } catch (Throwable $e) {
            Logger::error("Payroll::getPeriodEntries failed for period {$periodId}: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Update a single employee payroll entry in draft status
     */
    public static function updateEntry(int $entryId, array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $entry = Database::fetchOne("
                SELECT pe.*, pp.status AS period_status 
                FROM payroll_entries pe
                JOIN payroll_periods pp ON pe.payroll_period_id = pp.id
                WHERE pe.id = :id
            ", [':id' => $entryId]);

            if (!$entry) {
                return ['success' => false, 'message' => 'Payroll entry not found.'];
            }

            if ($entry['period_status'] !== 'draft') {
                return ['success' => false, 'message' => 'Cannot edit an entry in an approved or completed payroll period.'];
            }

            $basic = isset($data['basic_salary']) ? round(max(0.00, (float)$data['basic_salary']), 2) : (float)$entry['basic_salary'];
            $allowances = isset($data['allowances']) ? round(max(0.00, (float)$data['allowances']), 2) : (float)$entry['allowances'];
            $bonuses = isset($data['bonuses']) ? round(max(0.00, (float)$data['bonuses']), 2) : (float)$entry['bonuses'];
            $commissions = isset($data['commissions']) ? round(max(0.00, (float)$data['commissions']), 2) : (float)$entry['commissions'];
            $overtime = isset($data['overtime_amount']) ? round(max(0.00, (float)$data['overtime_amount']), 2) : (float)$entry['overtime_amount'];

            $advDeduct = isset($data['salary_advances_deducted']) ? round(max(0.00, (float)$data['salary_advances_deducted']), 2) : (float)$entry['salary_advances_deducted'];
            $otherDeduct = isset($data['other_deductions']) ? round(max(0.00, (float)$data['other_deductions']), 2) : (float)$entry['other_deductions'];
            $notes = isset($data['notes']) ? trim($data['notes']) : $entry['notes'];

            // Under Sri Lankan labor laws (Employees' Provident Fund Act No. 15 of 1958),
            // total monthly earnings for EPF/ETF calculations explicitly include Basic Salary, Commissions,
            // Allowances, and Bonuses (Overtime is exempt under Section 47).
            $empEpfRate = (float)($data['employee_epf_rate'] ?? 8.00);
            $emprEpfRate = (float)($data['employer_epf_rate'] ?? 12.00);
            $emprEtfRate = (float)($data['employer_etf_rate'] ?? 3.00);

            $epfEligibleBase = round($basic + $commissions + $allowances + $bonuses, 2);
            $empEpf  = round($epfEligibleBase * ($empEpfRate / 100), 2);
            $emprEpf = round($epfEligibleBase * ($emprEpfRate / 100), 2);
            $emprEtf = round($epfEligibleBase * ($emprEtfRate / 100), 2);

            $gross       = round($basic + $allowances + $bonuses + $commissions + $overtime, 2);
            $totalDeduct = round($empEpf + $advDeduct + $otherDeduct, 2);
            $net         = max(0.00, round($gross - $totalDeduct, 2));

            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            Database::execute("
                UPDATE payroll_entries SET
                    basic_salary = :basic,
                    allowances = :allowances,
                    bonuses = :bonuses,
                    commissions = :commissions,
                    overtime_amount = :overtime,
                    gross_salary = :gross,
                    employee_epf_rate = :emp_rate,
                    employee_epf_amount = :emp_epf,
                    salary_advances_deducted = :adv_deduct,
                    other_deductions = :other_deduct,
                    total_deductions = :total_deduct,
                    net_salary = :net,
                    employer_epf_rate = :empr_epf_rate,
                    employer_epf_amount = :empr_epf,
                    employer_etf_rate = :empr_etf_rate,
                    employer_etf_amount = :empr_etf,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id
            ", [
                ':basic'          => $basic,
                ':allowances'     => $allowances,
                ':bonuses'        => $bonuses,
                ':commissions'    => $commissions,
                ':overtime'       => $overtime,
                ':gross'          => $gross,
                ':emp_rate'       => $empEpfRate,
                ':emp_epf'        => $empEpf,
                ':adv_deduct'     => $advDeduct,
                ':other_deduct'   => $otherDeduct,
                ':total_deduct'   => $totalDeduct,
                ':net'            => $net,
                ':empr_epf_rate'  => $emprEpfRate,
                ':empr_epf'       => $emprEpf,
                ':empr_etf_rate'  => $emprEtfRate,
                ':empr_etf'       => $emprEtf,
                ':notes'          => $notes,
                ':id'             => $entryId
            ]);

            // Recompute period totals
            self::recomputePeriodTotals((int)$entry['payroll_period_id']);

            $db->commit();

            Audit::log('PAYROLL_ENTRY_UPDATED', 'payroll', (string)$entryId, $entry, [
                'basic_salary' => $basic,
                'gross_salary' => $gross,
                'net_salary'   => $net
            ]);

            $updatedEntry = Database::fetchOne("
                SELECT pe.*, e.employee_code, e.full_name AS employee_name, e.job_role, e.is_sales_rep, b.name AS branch_name
                FROM payroll_entries pe
                JOIN employees e ON pe.employee_id = e.id
                JOIN branches b ON pe.branch_id = b.id
                WHERE pe.id = :id
            ", [':id' => $entryId]);
            $updatedPeriod = self::findPeriod((int)$entry['payroll_period_id']);

            return [
                'success' => true,
                'message' => 'Payroll entry updated successfully.',
                'entry'   => $updatedEntry,
                'period'  => $updatedPeriod
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Payroll::updateEntry failed for entry {$entryId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error updating payroll entry: ' . $e->getMessage()];
        }
    }

    /**
     * Helper to recompute and persist period summary totals
     */
    private static function recomputePeriodTotals(int $periodId): void
    {
        $sums = Database::fetchOne("
            SELECT 
                COALESCE(SUM(basic_salary), 0) AS total_basic,
                COALESCE(SUM(allowances), 0) AS total_allowances,
                COALESCE(SUM(commissions), 0) AS total_commissions,
                COALESCE(SUM(gross_salary), 0) AS total_gross,
                COALESCE(SUM(employee_epf_amount), 0) AS total_employee_epf,
                COALESCE(SUM(employer_epf_amount), 0) AS total_employer_epf,
                COALESCE(SUM(employer_etf_amount), 0) AS total_employer_etf,
                COALESCE(SUM(total_deductions), 0) AS total_deductions,
                COALESCE(SUM(net_salary), 0) AS total_net_salary,
                COALESCE(SUM(paid_amount), 0) AS total_paid
            FROM payroll_entries
            WHERE payroll_period_id = :pid
        ", [':pid' => $periodId]);

        $curr = Database::fetchOne("SELECT status FROM payroll_periods WHERE id = :id", [':id' => $periodId]);
        $currStatus = $curr['status'] ?? 'draft';
        $newStatus = $currStatus;
        $tNet = (float)($sums['total_net_salary'] ?? 0);
        $tPaid = (float)($sums['total_paid'] ?? 0);
        if ($currStatus !== 'draft' && $currStatus !== 'cancelled') {
            if ($tPaid >= ($tNet - 0.01) && $tNet > 0) {
                $newStatus = 'completed';
            } else {
                $newStatus = 'approved';
            }
        }

        Database::execute("
            UPDATE payroll_periods SET
                total_basic = :tb,
                total_allowances = :ta,
                total_commissions = :tc,
                total_gross = :tg,
                total_employee_epf = :te_epf,
                total_employer_epf = :ter_epf,
                total_employer_etf = :ter_etf,
                total_deductions = :td,
                total_net_salary = :tn,
                total_paid = :tp,
                status = :status,
                updated_at = NOW()
            WHERE id = :id
        ", [
            ':tb'      => (float)($sums['total_basic'] ?? 0),
            ':ta'      => (float)($sums['total_allowances'] ?? 0),
            ':tc'      => (float)($sums['total_commissions'] ?? 0),
            ':tg'      => (float)($sums['total_gross'] ?? 0),
            ':te_epf'  => (float)($sums['total_employee_epf'] ?? 0),
            ':ter_epf' => (float)($sums['total_employer_epf'] ?? 0),
            ':ter_etf' => (float)($sums['total_employer_etf'] ?? 0),
            ':td'      => (float)($sums['total_deductions'] ?? 0),
            ':tn'      => (float)($sums['total_net_salary'] ?? 0),
            ':tp'      => (float)($sums['total_paid'] ?? 0),
            ':status'  => $newStatus,
            ':id'      => $periodId
        ]);
    }

    /**
     * Approve payroll period & post balanced double-entry journals to Chart of Accounts
     * Enforces:
     * Dr 5200: Basic Salaries Expense
     * Dr 5220: Allowances, Bonuses, Commissions & Overtime Expense
     * Dr 5210: Employer EPF & ETF Expense (12% + 3% = 15%)
     * Cr 2050: Salaries & Wages Payable (Total Net Salary)
     * Cr 2055: EPF Payable (Employee 8% + Employer 12% = 20%)
     * Cr 2060: ETF Payable (Employer 3%)
     * Cr 1048: Employee Salary Advances (Recovered portion)
     * Strict verification: Total Debits == Total Credits.
     */
    public static function approvePeriod(int $periodId, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $period = self::findPeriod($periodId);
            if (!$period) {
                return ['success' => false, 'message' => 'Payroll period not found.'];
            }

            if ($period['status'] !== 'draft') {
                return ['success' => false, 'message' => "Payroll period is already {$period['status']}."];
            }

            $entries = self::getPeriodEntries($periodId);
            if (empty($entries)) {
                return ['success' => false, 'message' => 'Payroll period has no employee entries to approve.'];
            }

            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            // Gather grand totals
            $totalBasic        = 0.00;
            $totalAdditions    = 0.00; // Allowances + Bonuses + Commissions + Overtime
            $totalEmpEpf       = 0.00;
            $totalEmprEpf      = 0.00;
            $totalEmprEtf      = 0.00;
            $totalNetPayable   = 0.00;
            $totalAdvRecovered = 0.00;
            $totalOtherDeduct  = 0.00;

            foreach ($entries as $e) {
                $basic       = (float)$e['basic_salary'];
                $additions   = (float)$e['allowances'] + (float)$e['bonuses'] + (float)$e['commissions'] + (float)$e['overtime_amount'];
                $empEpf      = (float)$e['employee_epf_amount'];
                $emprEpf     = (float)$e['employer_epf_amount'];
                $emprEtf     = (float)$e['employer_etf_amount'];
                $net         = (float)$e['net_salary'];
                $advDeduct   = (float)$e['salary_advances_deducted'];
                $otherDeduct = (float)$e['other_deductions'];

                $totalBasic        += $basic;
                $totalAdditions    += $additions;
                $totalEmpEpf       += $empEpf;
                $totalEmprEpf      += $emprEpf;
                $totalEmprEtf      += $emprEtf;
                $totalNetPayable   += $net;
                $totalAdvRecovered += $advDeduct;
                $totalOtherDeduct  += $otherDeduct;

                // Recover active advances for this employee if advance deduction was specified
                if ($advDeduct > 0) {
                    $advances = Database::fetchAll("
                        SELECT id, remaining_amount, recovered_amount 
                        FROM employee_advances 
                        WHERE employee_id = :eid AND status = 'active'
                        ORDER BY (CASE WHEN recovery_period_id = :pid THEN 0 ELSE 1 END) ASC, id ASC
                    ", [':eid' => (int)$e['employee_id'], ':pid' => $periodId]);

                    $remainingToRecover = $advDeduct;
                    foreach ($advances as $adv) {
                        if ($remainingToRecover <= 0) break;
                        $advRem = (float)$adv['remaining_amount'];
                        $advDeductAmount = min($advRem, $remainingToRecover);
                        $newRem = max(0.00, $advRem - $advDeductAmount);
                        $newRec = (float)$adv['recovered_amount'] + $advDeductAmount;
                        $newStatus = $newRem <= 0.00 ? 'fully_recovered' : 'active';

                        Database::execute("
                            UPDATE employee_advances 
                            SET remaining_amount = :rem, recovered_amount = :rec, status = :st, updated_at = NOW() 
                            WHERE id = :id
                        ", [
                            ':rem' => $newRem,
                            ':rec' => $newRec,
                            ':st'  => $newStatus,
                            ':id'  => (int)$adv['id']
                        ]);

                        $remainingToRecover -= $advDeductAmount;
                    }
                }

                // Update entry status to approved
                Database::execute("
                    UPDATE payroll_entries SET status = 'approved', updated_at = NOW() WHERE id = :id
                ", [':id' => (int)$e['id']]);
            }

            // Total employer statutory cost
            $totalEmprStatutory = round($totalEmprEpf + $totalEmprEtf, 2);
            // Combined EPF liability (Employee 8% + Employer 12% = 20%)
            $totalEpfLiability  = round($totalEmpEpf + $totalEmprEpf, 2);

            // Chart of Accounts IDs
            $accBasicExpense   = Accounting::getAccountIdByCode(Accounting::CODE_SALARY_EXPENSE); // 5200
            $accCommExpense    = Accounting::getAccountIdByCode(Accounting::CODE_COMMISSION_ALLOWANCE_EXPENSE); // 5220
            $accEmprStatExp    = Accounting::getAccountIdByCode(Accounting::CODE_EMPLOYER_EPF_ETF_EXPENSE); // 5210
            $accSalaryPayable  = Accounting::getAccountIdByCode(Accounting::CODE_SALARY_PAYABLE); // 2050
            $accEpfPayable     = Accounting::getAccountIdByCode(Accounting::CODE_EPF_PAYABLE); // 2055
            $accEtfPayable     = Accounting::getAccountIdByCode(Accounting::CODE_ETF_PAYABLE); // 2060
            $accEmpAdvances    = Accounting::getAccountIdByCode(Accounting::CODE_EMPLOYEE_ADVANCES); // 1048

            $branchId = !empty($period['branch_id']) ? (int)$period['branch_id'] : (int)(Auth::getCurrentBranchId() ?? 1);

            $journalLines = [];

            // 1. Debits (Expenses)
            if ($totalBasic > 0) {
                $journalLines[] = [
                    'account_id' => $accBasicExpense,
                    'debit'      => $totalBasic,
                    'credit'     => 0.00,
                    'memo'       => "Basic Salaries & Wages - Period {$period['period_name']}"
                ];
            }

            if ($totalAdditions > 0) {
                $journalLines[] = [
                    'account_id' => $accCommExpense,
                    'debit'      => $totalAdditions,
                    'credit'     => 0.00,
                    'memo'       => "Allowances, Commissions & Overtime - Period {$period['period_name']}"
                ];
            }

            if ($totalEmprStatutory > 0) {
                $journalLines[] = [
                    'account_id' => $accEmprStatExp,
                    'debit'      => $totalEmprStatutory,
                    'credit'     => 0.00,
                    'memo'       => "Employer EPF (12%) & ETF (3%) Expense - Period {$period['period_name']}"
                ];
            }

            // 2. Credits (Payables & Asset Deductions)
            if ($totalNetPayable > 0) {
                $journalLines[] = [
                    'account_id' => $accSalaryPayable,
                    'debit'      => 0.00,
                    'credit'     => $totalNetPayable,
                    'memo'       => "Net Salaries Payable - Period {$period['period_name']}"
                ];
            }

            if ($totalEpfLiability > 0) {
                $journalLines[] = [
                    'account_id' => $accEpfPayable,
                    'debit'      => 0.00,
                    'credit'     => $totalEpfLiability,
                    'memo'       => "EPF Liability (Employee 8% + Employer 12%) - Period {$period['period_name']}"
                ];
            }

            if ($totalEmprEtf > 0) {
                $journalLines[] = [
                    'account_id' => $accEtfPayable,
                    'debit'      => 0.00,
                    'credit'     => $totalEmprEtf,
                    'memo'       => "ETF Liability (Employer 3%) - Period {$period['period_name']}"
                ];
            }

            if ($totalAdvRecovered > 0) {
                $journalLines[] = [
                    'account_id' => $accEmpAdvances,
                    'debit'      => 0.00,
                    'credit'     => $totalAdvRecovered,
                    'memo'       => "Salary Advances Recovered from Payroll - Period {$period['period_name']}"
                ];
            }

            if ($totalOtherDeduct > 0) {
                // If there are other miscellaneous deductions, credit Salary Payable or other liabilities
                $journalLines[] = [
                    'account_id' => $accSalaryPayable,
                    'debit'      => 0.00,
                    'credit'     => $totalOtherDeduct,
                    'memo'       => "Other Payroll Deductions - Period {$period['period_name']}"
                ];
            }

            // Post Double-Entry Journal
            $journalHeader = [
                'branch_id'   => $branchId,
                'entry_date'  => $period['end_date'],
                'source_type' => 'payroll',
                'source_id'   => $periodId,
                'description' => "Payroll Approval & Accruals: {$period['period_name']} ({$period['period_code']})",
                'created_by'  => $userId
            ];

            $accRes = Accounting::postJournalEntry($journalHeader, $journalLines);
            if (empty($accRes['success']) && empty($accRes['already_posted'])) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Double-entry journal error: ' . ($accRes['message'] ?? 'Failed to post payroll journal.')];
            }

            // Update period status to approved
            Database::execute("
                UPDATE payroll_periods SET
                    status = 'approved',
                    approved_by = :uid,
                    approved_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id
            ", [
                ':uid' => $userId,
                ':id'  => $periodId
            ]);

            $db->commit();

            Audit::log('PAYROLL_PERIOD_APPROVED', 'payroll', (string)$periodId, null, [
                'period_code'   => $period['period_code'],
                'total_gross'   => $period['total_gross'],
                'total_net'     => $totalNetPayable,
                'total_epf'     => $totalEpfLiability,
                'total_etf'     => $totalEmprEtf,
                'journal_id'    => $accRes['journal_id'] ?? null
            ]);

            return [
                'success'          => true,
                'message'          => "Payroll period '{$period['period_name']}' approved and journals posted.",
                'journal_id'       => $accRes['journal_id'] ?? null,
                'journal_entry_id' => $accRes['journal_id'] ?? null
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Payroll::approvePeriod failed for period {$periodId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error approving payroll period: ' . $e->getMessage()];
        }
    }

    /**
     * Record salary payment for an entire payroll period
     * Allocates the disbursed payment amount across the period's unpaid entries,
     * updates each entry's paid_amount & status, records payment splits,
     * and posts double-entry accounting journal:
     * Dr 2050 (Salaries & Wages Payable)
     * Cr Each Split Account (1012 Cash Drawer, 1010 Cash in Hand, 1015 Petty Cash, Bank Accounts)
     */
    public static function recordPeriodPayment(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $periodId = (int)($data['payroll_period_id'] ?? 0);
            $amount   = round(max(0.00, (float)($data['amount'] ?? 0)), 2);
            $date     = !empty($data['payment_date']) ? date('Y-m-d', strtotime($data['payment_date'])) : date('Y-m-d');
            $notes    = trim($data['notes'] ?? '');

            if ($periodId <= 0 || $amount <= 0.00) {
                return ['success' => false, 'message' => 'Valid payroll period and positive payment amount are required.'];
            }

            $period = Database::fetchOne("
                SELECT pp.*, b.name AS branch_name
                FROM payroll_periods pp
                JOIN branches b ON pp.branch_id = b.id
                WHERE pp.id = :id
            ", [':id' => $periodId]);

            if (!$period) {
                return ['success' => false, 'message' => 'Payroll period not found.'];
            }

            if (!in_array($period['status'], ['approved', 'completed'], true)) {
                return ['success' => false, 'message' => 'Payments can only be recorded for approved payroll periods.'];
            }

            $totalNet    = (float)$period['total_net_salary'];
            $totalPaid   = (float)$period['total_paid'];
            $outstanding = max(0.00, round($totalNet - $totalPaid, 2));

            if ($amount > ($outstanding + 0.01)) {
                return ['success' => false, 'message' => "Payment amount (LKR {$amount}) exceeds outstanding salaries (LKR {$outstanding})."];
            }

            // Fetch unpaid employee entries for this period
            $entries = Database::fetchAll("
                SELECT pe.*, e.full_name AS emp_name, e.employee_code 
                FROM payroll_entries pe
                JOIN employees e ON pe.employee_id = e.id
                WHERE pe.payroll_period_id = :pid AND pe.net_salary > pe.paid_amount
                ORDER BY pe.id ASC
            ", [':pid' => $periodId]);

            if (empty($entries)) {
                return ['success' => false, 'message' => 'All employee salaries in this period are already fully paid.'];
            }

            // Parse splits
            $rawSplits = $data['splits'] ?? null;
            $splits = [];
            if (!empty($rawSplits)) {
                if (is_string($rawSplits)) {
                    $decoded = json_decode($rawSplits, true);
                    if (is_array($decoded)) {
                        $rawSplits = $decoded;
                    }
                }
                if (is_array($rawSplits)) {
                    foreach ($rawSplits as $s) {
                        $sAmt = round(max(0.00, (float)($s['amount'] ?? 0)), 2);
                        if ($sAmt > 0) {
                            $splits[] = [
                                'amount'          => $sAmt,
                                'payment_method'  => trim($s['payment_method'] ?? 'Cash in Hand'),
                                'account_id'      => !empty($s['account_id']) ? (int)$s['account_id'] : null,
                                'bank_account_id' => !empty($s['bank_account_id']) ? (int)$s['bank_account_id'] : null,
                                'cheque_number'   => trim($s['cheque_number'] ?? ''),
                                'cheque_date'     => !empty($s['cheque_date']) ? date('Y-m-d', strtotime($s['cheque_date'])) : null,
                                'reference_no'    => trim($s['reference_no'] ?? ''),
                                'notes'           => trim($s['notes'] ?? '')
                            ];
                        }
                    }
                }
            }

            if (empty($splits)) {
                $splits[] = [
                    'amount'          => $amount,
                    'payment_method'  => trim($data['payment_method'] ?? 'Cash in Hand'),
                    'account_id'      => !empty($data['account_id']) ? (int)$data['account_id'] : null,
                    'bank_account_id' => !empty($data['bank_account_id']) ? (int)$data['bank_account_id'] : null,
                    'cheque_number'   => trim($data['cheque_number'] ?? ''),
                    'cheque_date'     => !empty($data['cheque_date']) ? date('Y-m-d', strtotime($data['cheque_date'])) : null,
                    'reference_no'    => trim($data['reference_no'] ?? ''),
                    'notes'           => $notes
                ];
            }

            $splitTotal = round(array_sum(array_column($splits, 'amount')), 2);
            if (abs($splitTotal - $amount) > 0.01) {
                return ['success' => false, 'message' => "Total of payment splits (LKR {$splitTotal}) does not match payment amount (LKR {$amount})."];
            }

            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            $branchId = (int)$period['branch_id'];
            $basePaymentNo = self::generatePaymentNo();
            $paymentIds = [];
            $journalCreditLines = [];

            $drawerCoaId = Accounting::getAccountIdByCode(Accounting::CODE_CASH_DRAWER);
            $cashCoaId   = Accounting::getAccountIdByCode(Accounting::CODE_CASH_IN_HAND);
            $pettyCoaId  = Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);
            $bankCoaId   = Accounting::getAccountIdByCode(Accounting::CODE_BANK);

            // 1. Allocate payment across unpaid entries
            $remToDisburse = $amount;
            $entryAllocations = [];
            foreach ($entries as $entry) {
                if ($remToDisburse <= 0.00) break;
                $entryRem = max(0.00, round((float)$entry['net_salary'] - (float)$entry['paid_amount'], 2));
                $payForThis = min($entryRem, $remToDisburse);
                if ($payForThis > 0.00) {
                    $entryAllocations[] = [
                        'entry'  => $entry,
                        'amount' => $payForThis
                    ];
                    $remToDisburse = round($remToDisburse - $payForThis, 2);
                }
            }

            // Update each allocated entry
            foreach ($entryAllocations as $alloc) {
                $e = $alloc['entry'];
                $newPaid = round((float)$e['paid_amount'] + $alloc['amount'], 2);
                $newStatus = ($newPaid >= ((float)$e['net_salary'] - 0.01)) ? 'paid' : 'partially_paid';

                Database::execute("
                    UPDATE payroll_entries SET
                        paid_amount = :paid,
                        status = :status,
                        updated_at = NOW()
                    WHERE id = :id
                ", [
                    ':paid'   => $newPaid,
                    ':status' => $newStatus,
                    ':id'     => (int)$e['id']
                ]);
            }

            // 2. Insert payment records for each split
            $primaryEntryId = (int)$entryAllocations[0]['entry']['id'];
            $primaryEmpId   = (int)$entryAllocations[0]['entry']['employee_id'];

            foreach ($splits as $idx => $split) {
                $splitAmt      = $split['amount'];
                $splitMethod   = $split['payment_method'];
                $splitAccId    = $split['account_id'];
                $splitBankId   = $split['bank_account_id'];
                $splitCheque   = $split['cheque_number'];
                $splitChequeDt = $split['cheque_date'];
                $splitRef      = $split['reference_no'];
                $splitNotes    = $split['notes'] ?: $notes;

                $splitNo = (count($splits) > 1) ? "{$basePaymentNo}-" . ($idx + 1) : $basePaymentNo;

                // Resolve Credit Account ID
                $crAccountId = $splitAccId;
                if (!$crAccountId) {
                    $mLower = strtolower($splitMethod);
                    if (strpos($mLower, 'drawer') !== false) {
                        $crAccountId = $drawerCoaId;
                    } elseif (strpos($mLower, 'petty') !== false) {
                        $crAccountId = $pettyCoaId;
                    } elseif (strpos($mLower, 'bank') !== false || strpos($mLower, 'cheque') !== false) {
                        if ($splitBankId) {
                            $bRow = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :id", [':id' => $splitBankId]);
                            if ($bRow && !empty($bRow['account_id'])) {
                                $crAccountId = (int)$bRow['account_id'];
                            }
                        }
                        if (!$crAccountId) $crAccountId = $bankCoaId;
                    } else {
                        $crAccountId = $cashCoaId;
                    }
                }

                Database::execute("
                    INSERT INTO payroll_payments (
                        payment_no, payroll_entry_id, employee_id, branch_id, 
                        amount, payment_method, payment_date, bank_account_id, account_id,
                        cheque_number, cheque_date, reference_no, notes, created_by, created_at
                    ) VALUES (
                        :payment_no, :payroll_entry_id, :employee_id, :branch_id,
                        :amount, :payment_method, :payment_date, :bank_account_id, :account_id,
                        :cheque_number, :cheque_date, :reference_no, :notes, :created_by, NOW()
                    )
                ", [
                    ':payment_no'        => $splitNo,
                    ':payroll_entry_id'  => $primaryEntryId,
                    ':employee_id'       => $primaryEmpId,
                    ':branch_id'         => $branchId,
                    ':amount'            => $splitAmt,
                    ':payment_method'    => $splitMethod,
                    ':payment_date'      => $date,
                    ':bank_account_id'   => $splitBankId,
                    ':account_id'        => $crAccountId,
                    ':cheque_number'     => $splitCheque ?: null,
                    ':cheque_date'       => $splitChequeDt ?: null,
                    ':reference_no'      => $splitRef ?: null,
                    ':notes'             => $splitNotes ?: ("Period Payroll: " . $period['period_code']),
                    ':created_by'        => $userId
                ]);

                $pId = (int)$db->lastInsertId();
                $paymentIds[] = $pId;

                // Cash drawer integration if paid from Cash Drawer
                if ($crAccountId === $drawerCoaId || strpos(strtolower($splitMethod), 'drawer') !== false) {
                    $activeDay = BusinessDay::getActiveDay($branchId);
                    if ($activeDay) {
                        BusinessDay::recordDrawerMovement([
                            'business_day_id' => (int)$activeDay['id'],
                            'branch_id'       => $branchId,
                            'movement_type'   => 'salary_payment',
                            'source_type'     => 'payroll_payments',
                            'source_id'       => $pId,
                            'amount'          => $splitAmt,
                            'direction'       => 'out',
                            'reason'          => "Payroll Salary Disbursement ({$period['period_code']})",
                            'notes'           => $splitNotes ?: null,
                            'user_id'         => $userId
                        ]);
                    }
                }

                $journalCreditLines[] = [
                    'account_id' => $crAccountId,
                    'debit'      => 0.00,
                    'credit'     => $splitAmt,
                    'memo'       => "Payroll Disbursement ({$splitMethod}) Ref: {$splitNo}" . ($splitCheque ? " Chq: {$splitCheque}" : "")
                ];
            }

            // Recompute period totals and status
            self::recomputePeriodTotals($periodId);

            // Double-entry Journal Posting:
            // Dr 2050 (Salaries & Wages Payable) for total amount
            // Cr Each Split Account for split amount
            $accSalaryPayable = Accounting::getAccountIdByCode(Accounting::CODE_SALARY_PAYABLE);
            $journalLines = [
                [
                    'account_id' => $accSalaryPayable,
                    'debit'      => $amount,
                    'credit'     => 0.00,
                    'memo'       => "Period Payroll Salaries Disbursement: {$period['period_name']} ({$period['period_code']})"
                ]
            ];
            foreach ($journalCreditLines as $cLine) {
                $journalLines[] = $cLine;
            }

            $primaryPaymentId = $paymentIds[0] ?? 0;
            $journalHeader = [
                'branch_id'   => $branchId,
                'entry_date'  => $date,
                'source_type' => 'payroll_payment',
                'source_id'   => $primaryPaymentId,
                'description' => "Payroll Salaries Payment #{$basePaymentNo}: {$period['period_name']} (LKR " . number_format($amount, 2) . ")" . (count($splits) > 1 ? " in " . count($splits) . " splits" : ""),
                'created_by'  => $userId
            ];

            $accRes = Accounting::postJournalEntry($journalHeader, $journalLines);
            if (empty($accRes['success']) && empty($accRes['already_posted'])) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Accounting Journal error: ' . ($accRes['message'] ?? 'Failed to post payment journal.')];
            }

            $db->commit();

            Audit::log('PAYROLL_PERIOD_PAYMENT_RECORDED', 'payroll', (string)$periodId, null, [
                'payment_no'  => $basePaymentNo,
                'period_id'   => $periodId,
                'amount'      => $amount,
                'splits'      => count($splits)
            ]);

            return [
                'success'      => true,
                'message'      => "Salary payment of LKR " . number_format($amount, 2) . " disbursed successfully for {$period['period_name']}.",
                'payment_no'   => $basePaymentNo,
                'payment_id'   => $primaryPaymentId,
                'payment_ids'  => $paymentIds
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Payroll::recordPeriodPayment failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error recording payment: ' . $e->getMessage()];
        }
    }

    /**
     * Record salary payment (with multi-account split payments, Cash Drawer, Bank Transfer & Cheques)
     * Double-entry:
     * Dr 2050 (Salaries & Wages Payable)
     * Cr Each Split Account (1012 Cash Drawer, 1010 Cash in Hand, 1015 Petty Cash, Bank Accounts)
     */
    public static function recordPayment(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            if (!empty($data['payroll_period_id']) && empty($data['payroll_entry_id'])) {
                return self::recordPeriodPayment($data, $userId);
            }

            $entryId = (int)($data['payroll_entry_id'] ?? 0);
            $amount  = round(max(0.00, (float)($data['amount'] ?? 0)), 2);
            $date    = !empty($data['payment_date']) ? date('Y-m-d', strtotime($data['payment_date'])) : date('Y-m-d');
            $notes   = trim($data['notes'] ?? '');

            if ($entryId <= 0 || $amount <= 0.00) {
                return ['success' => false, 'message' => 'Valid entry ID and positive payment amount are required.'];
            }

            $entry = Database::fetchOne("
                SELECT pe.*, pp.status AS period_status, pp.period_name, e.full_name AS emp_name, e.employee_code 
                FROM payroll_entries pe
                JOIN payroll_periods pp ON pe.payroll_period_id = pp.id
                JOIN employees e ON pe.employee_id = e.id
                WHERE pe.id = :id
            ", [':id' => $entryId]);

            if (!$entry) {
                return ['success' => false, 'message' => 'Payroll entry not found.'];
            }

            if (!in_array($entry['period_status'], ['approved', 'completed'], true)) {
                return ['success' => false, 'message' => 'Payments can only be recorded for approved payroll periods.'];
            }

            $netSalary = (float)$entry['net_salary'];
            $currentPaid = (float)$entry['paid_amount'];
            $outstanding = max(0.00, round($netSalary - $currentPaid, 2));

            if ($amount > ($outstanding + 0.01)) {
                return ['success' => false, 'message' => "Payment amount (LKR {$amount}) exceeds outstanding salary (LKR {$outstanding})."];
            }

            // Parse splits
            $rawSplits = $data['splits'] ?? null;
            $splits = [];
            if (!empty($rawSplits)) {
                if (is_string($rawSplits)) {
                    $decoded = json_decode($rawSplits, true);
                    if (is_array($decoded)) {
                        $rawSplits = $decoded;
                    }
                }
                if (is_array($rawSplits)) {
                    foreach ($rawSplits as $s) {
                        $sAmt = round(max(0.00, (float)($s['amount'] ?? 0)), 2);
                        if ($sAmt > 0) {
                            $splits[] = [
                                'amount'          => $sAmt,
                                'payment_method'  => trim($s['payment_method'] ?? 'Cash in Hand'),
                                'account_id'      => !empty($s['account_id']) ? (int)$s['account_id'] : null,
                                'bank_account_id' => !empty($s['bank_account_id']) ? (int)$s['bank_account_id'] : null,
                                'cheque_number'   => trim($s['cheque_number'] ?? ''),
                                'cheque_date'     => !empty($s['cheque_date']) ? date('Y-m-d', strtotime($s['cheque_date'])) : null,
                                'reference_no'    => trim($s['reference_no'] ?? ''),
                                'notes'           => trim($s['notes'] ?? '')
                            ];
                        }
                    }
                }
            }

            // Fallback to single payment if no splits provided
            if (empty($splits)) {
                $method    = trim($data['payment_method'] ?? 'Cash in Hand');
                $bankAccId = !empty($data['bank_account_id']) ? (int)$data['bank_account_id'] : null;
                $accId     = !empty($data['account_id']) ? (int)$data['account_id'] : null;
                $chequeNo  = trim($data['cheque_number'] ?? '');
                $chequeDate= !empty($data['cheque_date']) ? date('Y-m-d', strtotime($data['cheque_date'])) : null;
                $refNo     = trim($data['reference_no'] ?? '');

                $splits[] = [
                    'amount'          => $amount,
                    'payment_method'  => $method,
                    'account_id'      => $accId,
                    'bank_account_id' => $bankAccId,
                    'cheque_number'   => $chequeNo,
                    'cheque_date'     => $chequeDate,
                    'reference_no'    => $refNo,
                    'notes'           => $notes
                ];
            }

            // Verify split total equals payment amount
            $splitTotal = round(array_sum(array_column($splits, 'amount')), 2);
            if (abs($splitTotal - $amount) > 0.01) {
                return ['success' => false, 'message' => "Total of payment splits (LKR {$splitTotal}) does not match payment amount (LKR {$amount})."];
            }

            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            $branchId = (int)$entry['branch_id'];
            $basePaymentNo = self::generatePaymentNo();
            $paymentIds = [];
            $journalCreditLines = [];

            $drawerCoaId = Accounting::getAccountIdByCode(Accounting::CODE_CASH_DRAWER);
            $cashCoaId   = Accounting::getAccountIdByCode(Accounting::CODE_CASH_IN_HAND);
            $pettyCoaId  = Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);
            $bankCoaId   = Accounting::getAccountIdByCode(Accounting::CODE_BANK);

            foreach ($splits as $idx => $split) {
                $splitAmt      = $split['amount'];
                $splitMethod   = $split['payment_method'];
                $splitAccId    = $split['account_id'];
                $splitBankId   = $split['bank_account_id'];
                $splitCheque   = $split['cheque_number'];
                $splitChequeDt = $split['cheque_date'];
                $splitRef      = $split['reference_no'];
                $splitNotes    = $split['notes'] ?: $notes;

                $splitNo = (count($splits) > 1) ? "{$basePaymentNo}-" . ($idx + 1) : $basePaymentNo;

                // Resolve Credit Account ID
                $crAccountId = $splitAccId;
                if (!$crAccountId) {
                    $mLower = strtolower($splitMethod);
                    if (strpos($mLower, 'drawer') !== false) {
                        $crAccountId = $drawerCoaId;
                    } elseif (strpos($mLower, 'petty') !== false) {
                        $crAccountId = $pettyCoaId;
                    } elseif (strpos($mLower, 'bank') !== false || strpos($mLower, 'cheque') !== false) {
                        if ($splitBankId) {
                            $bRow = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :id", [':id' => $splitBankId]);
                            if ($bRow && !empty($bRow['account_id'])) {
                                $crAccountId = (int)$bRow['account_id'];
                            }
                        }
                        if (!$crAccountId) $crAccountId = $bankCoaId;
                    } else {
                        $crAccountId = $cashCoaId;
                    }
                }

                Database::execute("
                    INSERT INTO payroll_payments (
                        payment_no, payroll_entry_id, employee_id, branch_id, 
                        amount, payment_method, payment_date, bank_account_id, account_id,
                        cheque_number, cheque_date, reference_no, notes, created_by, created_at
                    ) VALUES (
                        :payment_no, :payroll_entry_id, :employee_id, :branch_id,
                        :amount, :payment_method, :payment_date, :bank_account_id, :account_id,
                        :cheque_number, :cheque_date, :reference_no, :notes, :created_by, NOW()
                    )
                ", [
                    ':payment_no'        => $splitNo,
                    ':payroll_entry_id'  => $entryId,
                    ':employee_id'       => (int)$entry['employee_id'],
                    ':branch_id'         => $branchId,
                    ':amount'            => $splitAmt,
                    ':payment_method'    => $splitMethod,
                    ':payment_date'      => $date,
                    ':bank_account_id'   => $splitBankId,
                    ':account_id'        => $crAccountId,
                    ':cheque_number'     => $splitCheque ?: null,
                    ':cheque_date'       => $splitChequeDt ?: null,
                    ':reference_no'      => $splitRef ?: null,
                    ':notes'             => $splitNotes ?: null,
                    ':created_by'        => $userId
                ]);

                $pId = (int)$db->lastInsertId();
                $paymentIds[] = $pId;

                // Cash drawer integration if paid from Cash Drawer
                if ($crAccountId === $drawerCoaId || strpos(strtolower($splitMethod), 'drawer') !== false) {
                    $activeDay = BusinessDay::getActiveDay($branchId);
                    if ($activeDay) {
                        BusinessDay::recordDrawerMovement([
                            'business_day_id' => (int)$activeDay['id'],
                            'branch_id'       => $branchId,
                            'movement_type'   => 'salary_payment',
                            'source_type'     => 'payroll_payments',
                            'source_id'       => $pId,
                            'amount'          => $splitAmt,
                            'direction'       => 'out',
                            'reason'          => "Salary Payment ({$splitNo}) - {$entry['emp_name']}",
                            'notes'           => $splitNotes ?: null,
                            'user_id'         => $userId
                        ]);
                    }
                }

                $journalCreditLines[] = [
                    'account_id' => $crAccountId,
                    'debit'      => 0.00,
                    'credit'     => $splitAmt,
                    'memo'       => "Salary Disbursement ({$splitMethod}) Ref: {$splitNo}" . ($splitCheque ? " Chq: {$splitCheque}" : "")
                ];
            }

            // Update entry paid amount and status
            $newPaid = round($currentPaid + $amount, 2);
            $newStatus = ($newPaid >= ($netSalary - 0.01)) ? 'paid' : 'partially_paid';

            Database::execute("
                UPDATE payroll_entries SET
                    paid_amount = :paid,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ", [
                ':paid'   => $newPaid,
                ':status' => $newStatus,
                ':id'     => $entryId
            ]);

            // Recompute period totals
            self::recomputePeriodTotals((int)$entry['payroll_period_id']);

            // Double-entry Journal Posting:
            // Dr 2050 (Salaries & Wages Payable) for total amount
            // Cr Each Split Account for split amount
            $accSalaryPayable = Accounting::getAccountIdByCode(Accounting::CODE_SALARY_PAYABLE);
            $journalLines = [
                [
                    'account_id' => $accSalaryPayable,
                    'debit'      => $amount,
                    'credit'     => 0.00,
                    'memo'       => "Salary Payment to {$entry['emp_name']} ({$entry['employee_code']}) - {$entry['period_name']}"
                ]
            ];
            foreach ($journalCreditLines as $cLine) {
                $journalLines[] = $cLine;
            }

            $primaryPaymentId = $paymentIds[0] ?? 0;
            $journalHeader = [
                'branch_id'   => $branchId,
                'entry_date'  => $date,
                'source_type' => 'payroll_payment',
                'source_id'   => $primaryPaymentId,
                'description' => "Salary Payment #{$basePaymentNo}: {$entry['emp_name']} (LKR {$amount})" . (count($splits) > 1 ? " in " . count($splits) . " splits" : ""),
                'created_by'  => $userId
            ];

            $accRes = Accounting::postJournalEntry($journalHeader, $journalLines);
            if (empty($accRes['success']) && empty($accRes['already_posted'])) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Accounting Journal error: ' . ($accRes['message'] ?? 'Failed to post payment journal.')];
            }

            $db->commit();

            Audit::log('PAYROLL_PAYMENT_RECORDED', 'payroll', (string)$primaryPaymentId, null, [
                'payment_no'  => $basePaymentNo,
                'employee_id' => $entry['employee_id'],
                'amount'      => $amount,
                'splits'      => count($splits)
            ]);

            return [
                'success'      => true,
                'message'      => "Salary payment of LKR " . number_format($amount, 2) . " recorded successfully.",
                'payment_no'   => $basePaymentNo,
                'payment_id'   => $primaryPaymentId,
                'payment_ids'  => $paymentIds
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Payroll::recordPayment failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error recording payment: ' . $e->getMessage()];
        }
    }

    /**
     * Disburse an employee salary advance
    /**
     * Get eligible active accounts for salary advance and salary payment disbursement
     * Matches Universal Payment Method Rule 25 & Supplier Payments architecture:
     * - Cash Drawer (1012)
     * - Cash in Hand (1010)
     * - Petty Cash (1015)
     * - Active Bank Accounts (Bank Transfer)
     */
    public static function getEligiblePaymentAccounts(int $branchId): array
    {
        try {
            $accounts = [];

            // 1. Cash Drawer (1012)
            $drawerCoa = Accounting::getAccountByCode(Accounting::CODE_CASH_DRAWER);
            $drawerBalance = BusinessDay::getExpectedDrawerCash($branchId);
            if ($drawerCoa) {
                $accounts[] = [
                    'account_id'      => (int)$drawerCoa['id'],
                    'account_code'    => '1012',
                    'account_name'    => 'Cash Drawer (POS Register)',
                    'payment_method'  => 'Cash Drawer',
                    'type'            => 'drawer',
                    'bank_account_id' => null,
                    'balance'         => $drawerBalance,
                    'formatted_label' => 'Cash Drawer (1012) - POS Register [Bal: LKR ' . number_format($drawerBalance, 2) . ']'
                ];
            }

            // 2. Cash in Hand (1010)
            $cashCoa = Accounting::getAccountByCode(Accounting::CODE_CASH_IN_HAND);
            $cashBalance = 0.00;
            if ($cashCoa) {
                $cRow = Database::fetchOne("
                    SELECT COALESCE(SUM(jl.debit) - SUM(jl.credit), 0) AS balance
                    FROM journal_lines jl
                    JOIN journal_entries je ON jl.journal_entry_id = je.id
                    WHERE jl.account_id = :acc_id AND je.posting_status = 'posted'
                ", [':acc_id' => (int)$cashCoa['id']]);
                $cashBalance = (float)($cRow['balance'] ?? 0);
                $accounts[] = [
                    'account_id'      => (int)$cashCoa['id'],
                    'account_code'    => '1010',
                    'account_name'    => 'Cash in Hand (Main Safe)',
                    'payment_method'  => 'Cash in Hand',
                    'type'            => 'cash',
                    'bank_account_id' => null,
                    'balance'         => $cashBalance,
                    'formatted_label' => 'Cash in Hand (1010) - Main Safe [Bal: LKR ' . number_format($cashBalance, 2) . ']'
                ];
            }

            // 3. Petty Cash (1015)
            $pettyCoa = Accounting::getAccountByCode(Accounting::CODE_PETTY_CASH);
            $pettyBalance = PettyCash::getPettyCashBalance($branchId);
            if ($pettyCoa) {
                $accounts[] = [
                    'account_id'      => (int)$pettyCoa['id'],
                    'account_code'    => '1015',
                    'account_name'    => 'Petty Cash Account',
                    'payment_method'  => 'Petty Cash',
                    'type'            => 'petty_cash',
                    'bank_account_id' => null,
                    'balance'         => $pettyBalance,
                    'formatted_label' => 'Petty Cash (1015) [Bal: LKR ' . number_format($pettyBalance, 2) . ']'
                ];
            }

            // 4. Active Operational Bank Accounts
            $bankAccounts = Banking::getBankAccounts($branchId, true);
            foreach ($bankAccounts as $ba) {
                $bBal = (float)($ba['current_balance'] ?? 0);
                $accounts[] = [
                    'account_id'      => (int)$ba['account_id'],
                    'account_code'    => $ba['account_code'],
                    'account_name'    => "Bank Transfer: {$ba['bank_name']} - {$ba['account_name']}",
                    'payment_method'  => 'Bank Transfer',
                    'type'            => 'bank',
                    'bank_account_id' => (int)$ba['id'],
                    'bank_name'       => $ba['bank_name'],
                    'account_number'  => $ba['account_number'],
                    'balance'         => $bBal,
                    'formatted_label' => "Bank Transfer: {$ba['bank_name']} - {$ba['account_name']} ({$ba['account_number']}) [Bal: LKR " . number_format($bBal, 2) . "]"
                ];
            }

            return $accounts;
        } catch (Throwable $e) {
            Logger::error("Payroll::getEligiblePaymentAccounts failed: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get employee-wise advance summary, outstanding balance, and history
     */
    public static function getEmployeeAdvanceSummary(int $employeeId): array
    {
        try {
            $emp = Employee::find($employeeId);
            if (!$emp) {
                return ['success' => false, 'message' => 'Employee not found.'];
            }

            $totals = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(amount), 0) AS total_advanced,
                    COALESCE(SUM(recovered_amount), 0) AS total_recovered,
                    COALESCE(SUM(remaining_amount), 0) AS remaining_balance,
                    COUNT(CASE WHEN status = 'active' AND remaining_amount > 0 THEN 1 END) AS active_count
                FROM employee_advances
                WHERE employee_id = :eid AND status != 'cancelled'
            ", [':eid' => $employeeId]);

            $advances = Database::fetchAll("
                SELECT 
                    ea.*,
                    b.name AS branch_name,
                    u.full_name AS created_by_name,
                    cu.full_name AS cancelled_by_name,
                    pp.period_name AS recovery_period_name,
                    coa.account_name AS source_account_name,
                    ba.bank_name,
                    ba.account_number
                FROM employee_advances ea
                LEFT JOIN branches b ON ea.branch_id = b.id
                LEFT JOIN users u ON ea.created_by = u.id
                LEFT JOIN users cu ON ea.cancelled_by = cu.id
                LEFT JOIN payroll_periods pp ON ea.recovery_period_id = pp.id
                LEFT JOIN chart_of_accounts coa ON ea.source_account_id = coa.id
                LEFT JOIN bank_accounts ba ON ea.bank_account_id = ba.id
                WHERE ea.employee_id = :eid
                ORDER BY ea.disbursement_date DESC, ea.id DESC
            ", [':eid' => $employeeId]);

            return [
                'success'           => true,
                'employee'          => [
                    'id'            => (int)$emp['id'],
                    'employee_code' => $emp['employee_code'],
                    'full_name'     => $emp['full_name'],
                    'basic_salary'  => (float)$emp['basic_salary'],
                    'job_role'      => $emp['job_role'],
                    'branch_id'     => (int)$emp['branch_id'],
                    'status'        => $emp['status']
                ],
                'basic_salary'      => (float)$emp['basic_salary'],
                'total_advanced'    => (float)($totals['total_advanced'] ?? 0),
                'total_recovered'   => (float)($totals['total_recovered'] ?? 0),
                'remaining_balance' => (float)($totals['remaining_balance'] ?? 0),
                'active_count'      => (int)($totals['active_count'] ?? 0),
                'advances'          => $advances
            ];
        } catch (Throwable $e) {
            Logger::error("Payroll::getEmployeeAdvanceSummary failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Disburse an employee salary advance
     * Universal Payment Method Support (Rule 25):
     * - Cash Drawer (1012) -> records cash drawer cash-out movement
     * - Cash in Hand (1010)
     * - Petty Cash (1015)
     * - Bank Accounts (Bank Transfer)
     * Double-entry:
     * Dr 1048 (Employee Salary Advances)
     * Cr Selected Cash/Bank Account
     */
    public static function disburseAdvance(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $employeeId = (int)($data['employee_id'] ?? 0);
            $amount     = round(max(0.00, (float)($data['amount'] ?? 0)), 2);
            $date       = !empty($data['disbursement_date']) ? date('Y-m-d', strtotime($data['disbursement_date'])) : date('Y-m-d');
            $method     = trim($data['payment_method'] ?? 'Cash in Hand');
            $sourceAccId = !empty($data['source_account_id']) ? (int)$data['source_account_id'] : null;
            $bankAccId  = !empty($data['bank_account_id']) ? (int)$data['bank_account_id'] : null;
            $referenceNo = trim($data['reference_no'] ?? '');
            $recoveryPeriodId = !empty($data['recovery_period_id']) ? (int)$data['recovery_period_id'] : null;
            $notes      = trim($data['notes'] ?? '');

            if ($employeeId <= 0 || $amount <= 0.00) {
                return ['success' => false, 'message' => 'Valid employee and positive advance amount are required.'];
            }

            $emp = Employee::find($employeeId);
            if (!$emp || $emp['status'] !== 'active') {
                return ['success' => false, 'message' => 'Selected employee is inactive or not found.'];
            }

            // Normalize payment method string
            $mLower = strtolower($method);
            if (in_array($mLower, ['cash drawer', 'drawer'], true)) {
                $method = 'Cash Drawer';
            } elseif (in_array($mLower, ['cash in hand', 'cash', 'main safe'], true)) {
                $method = 'Cash in Hand';
            } elseif (in_array($mLower, ['petty cash', 'petty'], true)) {
                $method = 'Petty Cash';
            } elseif (in_array($mLower, ['bank', 'bank transfer'], true)) {
                $method = 'Bank Transfer';
            }

            if (!in_array($method, ['Cash Drawer', 'Cash in Hand', 'Petty Cash', 'Bank Transfer'], true)) {
                return ['success' => false, 'message' => "Invalid payment method '{$method}'. Must be Cash Drawer, Cash in Hand, Petty Cash, or Bank Transfer."];
            }

            if ($method === 'Bank Transfer' && empty($bankAccId)) {
                // If source account was provided, see if it links to a bank account
                if ($sourceAccId) {
                    $baCheck = Database::fetchOne("SELECT id FROM bank_accounts WHERE account_id = :aid", [':aid' => $sourceAccId]);
                    if ($baCheck) {
                        $bankAccId = (int)$baCheck['id'];
                    }
                }
                if (empty($bankAccId)) {
                    return ['success' => false, 'message' => 'Please select a specific Bank Account for bank disbursements.'];
                }
            }

            // Idempotency check: prevent duplicate advance for the same employee, amount, and date within 3 minutes
            $dupCheck = Database::fetchOne("
                SELECT id FROM employee_advances
                WHERE employee_id = :eid 
                  AND amount = :amt 
                  AND disbursement_date = :dt 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MINUTE)
                  AND status != 'cancelled'
            ", [
                ':eid' => $employeeId,
                ':amt' => $amount,
                ':dt'  => $date
            ]);
            if ($dupCheck) {
                return [
                    'success' => false, 
                    'message' => "Duplicate payment prevented: An identical advance of LKR " . number_format($amount, 2) . " was already disbursed just now."
                ];
            }

            // Resolve Credit COA Account ID
            $crAccountId = null;
            if ($method === 'Cash Drawer') {
                $crAccountId = Accounting::getAccountIdByCode(Accounting::CODE_CASH_DRAWER);
            } elseif ($method === 'Cash in Hand') {
                $crAccountId = Accounting::getAccountIdByCode(Accounting::CODE_CASH_IN_HAND);
            } elseif ($method === 'Petty Cash') {
                $crAccountId = Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);
            } elseif ($method === 'Bank Transfer') {
                if ($bankAccId) {
                    $baRow = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :id", [':id' => $bankAccId]);
                    if ($baRow && !empty($baRow['account_id'])) {
                        $crAccountId = (int)$baRow['account_id'];
                    }
                }
                if (!$crAccountId) {
                    $crAccountId = Accounting::getAccountIdByCode(Accounting::CODE_BANK);
                }
            }

            if (!$crAccountId) {
                return ['success' => false, 'message' => 'Unable to determine Chart of Accounts credit account for selected payment method.'];
            }

            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            $advanceNo = self::generateAdvanceNo();
            $branchId = (int)$emp['branch_id'];

            Database::execute("
                INSERT INTO employee_advances (
                    advance_no, employee_id, branch_id, amount, recovered_amount, 
                    remaining_amount, disbursement_date, payment_method, bank_account_id, 
                    source_account_id, reference_no, recovery_period_id, status, notes, 
                    created_by, created_at
                ) VALUES (
                    :advance_no, :employee_id, :branch_id, :amount, 0.00,
                    :remaining_amount, :disbursement_date, :payment_method, :bank_account_id,
                    :source_account_id, :reference_no, :recovery_period_id, 'active', :notes,
                    :created_by, NOW()
                )
            ", [
                ':advance_no'        => $advanceNo,
                ':employee_id'       => $employeeId,
                ':branch_id'         => $branchId,
                ':amount'            => $amount,
                ':remaining_amount'  => $amount,
                ':disbursement_date' => $date,
                ':payment_method'    => $method,
                ':bank_account_id'   => $bankAccId,
                ':source_account_id' => $crAccountId,
                ':reference_no'      => $referenceNo ?: null,
                ':recovery_period_id'=> $recoveryPeriodId,
                ':notes'             => $notes ?: null,
                ':created_by'        => $userId
            ]);

            $advanceId = (int)$db->lastInsertId();

            // POS Cash Drawer Movement Integration
            if ($method === 'Cash Drawer') {
                $activeDay = BusinessDay::getActiveDay($branchId);
                if ($activeDay) {
                    BusinessDay::recordDrawerMovement([
                        'business_day_id' => (int)$activeDay['id'],
                        'branch_id'       => $branchId,
                        'movement_type'   => 'employee_advance',
                        'source_type'     => 'employee_advances',
                        'source_id'       => $advanceId,
                        'amount'          => $amount,
                        'direction'       => 'out',
                        'reason'          => "Employee Salary Advance ({$advanceNo}) - {$emp['full_name']}",
                        'notes'           => $notes ?: null,
                        'user_id'         => $userId
                    ]);
                }
            }

            // Double Entry:
            // Dr 1048 (Employee Salary Advances)
            // Cr Selected Credit Account
            $accEmpAdvances = Accounting::getAccountIdByCode(Accounting::CODE_EMPLOYEE_ADVANCES);

            $journalLines = [
                [
                    'account_id' => $accEmpAdvances,
                    'debit'      => $amount,
                    'credit'     => 0.00,
                    'memo'       => "Salary Advance Disbursed to {$emp['full_name']} ({$emp['employee_code']})"
                ],
                [
                    'account_id' => $crAccountId,
                    'debit'      => 0.00,
                    'credit'     => $amount,
                    'memo'       => "Salary Advance Disbursement ({$method}) Ref: {$advanceNo}" . ($referenceNo ? " / {$referenceNo}" : "")
                ]
            ];

            $journalHeader = [
                'branch_id'   => $branchId,
                'entry_date'  => $date,
                'source_type' => 'employee_advance',
                'source_id'   => $advanceId,
                'description' => "Salary Advance #{$advanceNo}: {$emp['full_name']} (LKR {$amount}) via {$method}",
                'created_by'  => $userId
            ];

            $accRes = Accounting::postJournalEntry($journalHeader, $journalLines);
            if (empty($accRes['success']) && empty($accRes['already_posted'])) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Accounting Journal error: ' . ($accRes['message'] ?? 'Failed to post advance journal.')];
            }

            $db->commit();

            Audit::log('EMPLOYEE_ADVANCE_DISBURSED', 'payroll', (string)$advanceId, null, [
                'advance_no'     => $advanceNo,
                'employee_id'    => $employeeId,
                'amount'         => $amount,
                'payment_method' => $method,
                'cr_account_id'  => $crAccountId,
                'journal_id'     => $accRes['journal_id'] ?? null
            ]);

            return [
                'success'    => true,
                'message'    => "Salary advance of LKR " . number_format($amount, 2) . " disbursed to {$emp['full_name']} via {$method}.",
                'advance_no' => $advanceNo,
                'advance_id' => $advanceId
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Payroll::disburseAdvance failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error disbursing salary advance: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel an employee salary advance
     * Checks that no recovery has occurred, reverses double-entry journal, 
     * restores cash drawer if paid from cash drawer, and marks as cancelled.
     */
    public static function cancelAdvance(int $advanceId, string $reason, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $adv = Database::fetchOne("
                SELECT ea.*, e.full_name AS employee_name, e.employee_code
                FROM employee_advances ea
                JOIN employees e ON ea.employee_id = e.id
                WHERE ea.id = :id
            ", [':id' => $advanceId]);

            if (!$adv) {
                return ['success' => false, 'message' => 'Salary advance record not found.'];
            }

            if ($adv['status'] === 'cancelled') {
                return ['success' => false, 'message' => 'Salary advance is already cancelled.'];
            }

            $recovered = (float)$adv['recovered_amount'];
            if ($recovered > 0.001) {
                return ['success' => false, 'message' => "Cannot cancel advance: LKR " . number_format($recovered, 2) . " has already been recovered through payroll."];
            }

            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            $branchId = (int)$adv['branch_id'];
            $amount   = (float)$adv['amount'];
            $method   = trim($adv['payment_method']);

            // 1. Reverse Journal Entry
            $je = Database::fetchOne("
                SELECT id FROM journal_entries 
                WHERE source_type = 'employee_advance' AND source_id = :id AND posting_status = 'posted'
            ", [':id' => $advanceId]);

            if ($je && !empty($je['id'])) {
                $revRes = Accounting::reverseJournalEntry((int)$je['id'], "Cancelled salary advance #{$adv['advance_no']}: " . $reason, $userId);
                if (empty($revRes['success'])) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Failed to reverse accounting journal: ' . ($revRes['message'] ?? '')];
                }
            }

            // 2. If Cash Drawer was used, restore drawer cash via drawer movement
            $isDrawerPayment = (strtolower($method) === 'cash drawer') || ((int)($adv['source_account_id'] ?? 0) === 49);
            if ($isDrawerPayment) {
                $activeDay = BusinessDay::getActiveDay($branchId);
                if ($activeDay) {
                    BusinessDay::recordDrawerMovement([
                        'business_day_id' => (int)$activeDay['id'],
                        'branch_id'       => $branchId,
                        'movement_type'   => 'cash_in',
                        'source_type'     => 'employee_advances',
                        'source_id'       => $advanceId,
                        'amount'          => $amount,
                        'direction'       => 'in',
                        'reason'          => "Cancelled Salary Advance Reversal ({$adv['advance_no']}) - {$adv['employee_name']}",
                        'notes'           => $reason,
                        'user_id'         => $userId
                    ]);
                }
            }

            // 3. Mark advance as cancelled
            Database::execute("
                UPDATE employee_advances SET
                    status = 'cancelled',
                    remaining_amount = 0.00,
                    cancelled_at = NOW(),
                    cancelled_by = :uid,
                    cancel_reason = :reason,
                    updated_at = NOW()
                WHERE id = :id
            ", [
                ':uid'    => $userId,
                ':reason' => $reason,
                ':id'     => $advanceId
            ]);

            $db->commit();

            Audit::log('EMPLOYEE_ADVANCE_CANCELLED', 'payroll', (string)$advanceId, $adv, [
                'advance_no'    => $adv['advance_no'],
                'cancel_reason' => $reason,
                'cancelled_by'  => $userId
            ]);

            return [
                'success' => true,
                'message' => "Salary advance #{$adv['advance_no']} has been successfully cancelled and reversed."
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Payroll::cancelAdvance failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error cancelling advance: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel / Reverse a payroll period
     * Restores any recovered salary advances, reverses double-entry GL journal, and cancels entries.
     */
    public static function cancelPeriod(int $periodId, string $reason, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $period = self::findPeriod($periodId);
            if (!$period) {
                return ['success' => false, 'message' => 'Payroll period not found.'];
            }

            if ($period['status'] === 'cancelled') {
                return ['success' => false, 'message' => 'Payroll period is already cancelled.'];
            }

            if ($period['status'] === 'completed') {
                return ['success' => false, 'message' => 'Cannot cancel a completed payroll period.'];
            }

            // Check if any salary payments have already been disbursed
            $payCount = Database::fetchOne("
                SELECT COUNT(pp.id) AS cnt 
                FROM payroll_payments pp
                JOIN payroll_entries pe ON pp.payroll_entry_id = pe.id
                WHERE pe.payroll_period_id = :pid
            ", [':pid' => $periodId]);

            if ((int)($payCount['cnt'] ?? 0) > 0) {
                return ['success' => false, 'message' => 'Cannot cancel payroll period: Salary payments have already been disbursed for this period.'];
            }

            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            // 1. If period was approved, restore any recovered salary advances
            if ($period['status'] === 'approved') {
                $entries = Database::fetchAll("
                    SELECT id, employee_id, salary_advances_deducted 
                    FROM payroll_entries 
                    WHERE payroll_period_id = :pid AND salary_advances_deducted > 0
                ", [':pid' => $periodId]);

                foreach ($entries as $e) {
                    $deducted = (float)$e['salary_advances_deducted'];
                    $empId = (int)$e['employee_id'];

                    if ($deducted > 0) {
                        // Find advances for this employee in reverse order of recovery
                        $advances = Database::fetchAll("
                            SELECT id, amount, recovered_amount, remaining_amount, status 
                            FROM employee_advances 
                            WHERE employee_id = :eid AND recovered_amount > 0 AND status != 'cancelled'
                            ORDER BY id DESC
                        ", [':eid' => $empId]);

                        $toRestore = $deducted;
                        foreach ($advances as $adv) {
                            if ($toRestore <= 0) break;
                            $rec = (float)$adv['recovered_amount'];
                            $restoreAmt = min($rec, $toRestore);

                            $newRec = max(0.00, $rec - $restoreAmt);
                            $newRem = (float)$adv['remaining_amount'] + $restoreAmt;

                            Database::execute("
                                UPDATE employee_advances SET
                                    recovered_amount = :rec,
                                    remaining_amount = :rem,
                                    status = 'active',
                                    updated_at = NOW()
                                WHERE id = :id
                            ", [
                                ':rec' => $newRec,
                                ':rem' => $newRem,
                                ':id'  => (int)$adv['id']
                            ]);

                            $toRestore -= $restoreAmt;
                        }
                    }
                }

                // 2. Reverse Double-Entry Journal for this payroll period
                $je = Database::fetchOne("
                    SELECT id FROM journal_entries 
                    WHERE source_type = 'payroll' AND source_id = :pid AND posting_status = 'posted'
                ", [':pid' => $periodId]);

                if ($je && !empty($je['id'])) {
                    $revRes = Accounting::reverseJournalEntry((int)$je['id'], "Payroll Period Cancelled: " . $reason, $userId);
                    if (empty($revRes['success'])) {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Failed to reverse payroll journal: ' . ($revRes['message'] ?? '')];
                    }
                }
            }

            // 3. Mark period and entries as cancelled
            Database::execute("
                UPDATE payroll_periods SET
                    status = 'cancelled',
                    notes = CONCAT(COALESCE(notes, ''), ' [Cancelled: ', :reason, ']'),
                    updated_at = NOW()
                WHERE id = :id
            ", [
                ':reason' => $reason,
                ':id'     => $periodId
            ]);

            Database::execute("
                UPDATE payroll_entries SET
                    status = 'cancelled',
                    updated_at = NOW()
                WHERE payroll_period_id = :pid
            ", [':pid' => $periodId]);

            $db->commit();

            Audit::log('PAYROLL_PERIOD_CANCELLED', 'payroll', (string)$periodId, $period, [
                'cancel_reason' => $reason,
                'cancelled_by'  => $userId
            ]);

            return [
                'success' => true,
                'message' => "Payroll period '{$period['period_name']}' has been cancelled and any recovered advances restored."
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Payroll::cancelPeriod failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error cancelling payroll period: ' . $e->getMessage()];
        }
    }

    /**
     * Remit statutory EPF/ETF to Central Bank / ETF Board
     * Double-entry:
     * Dr 2055 (EPF Payable)
     * Dr 2060 (ETF Payable)
     * Cr Dedicated Bank Account COA
     */
    public static function remitEpfEtf(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $periodId  = (int)($data['payroll_period_id'] ?? 0);
            $type      = trim($data['remittance_type'] ?? 'Combined'); // EPF, ETF, Combined
            $epfAmount = round(max(0.00, (float)($data['epf_amount'] ?? 0)), 2);
            $etfAmount = round(max(0.00, (float)($data['etf_amount'] ?? 0)), 2);
            $date      = !empty($data['payment_date']) ? date('Y-m-d', strtotime($data['payment_date'])) : date('Y-m-d');
            $notes     = trim($data['notes'] ?? '');

            // EPF account & cheque
            $epfBankId   = !empty($data['epf_bank_account_id']) ? (int)$data['epf_bank_account_id'] : (!empty($data['bank_account_id']) ? (int)$data['bank_account_id'] : null);
            $epfAccId    = !empty($data['epf_account_id']) ? (int)$data['epf_account_id'] : (!empty($data['account_id']) ? (int)$data['account_id'] : null);
            $epfChequeNo = trim($data['epf_cheque_no'] ?? ($data['cheque_no'] ?? ''));
            $epfChequeDt = !empty($data['epf_cheque_date']) ? date('Y-m-d', strtotime($data['epf_cheque_date'])) : (!empty($data['cheque_date']) ? date('Y-m-d', strtotime($data['cheque_date'])) : null);
            $epfRefNo    = trim($data['epf_reference_no'] ?? ($data['reference_no'] ?? ''));

            // ETF account & cheque
            $etfBankId   = !empty($data['etf_bank_account_id']) ? (int)$data['etf_bank_account_id'] : $epfBankId;
            $etfAccId    = !empty($data['etf_account_id']) ? (int)$data['etf_account_id'] : $epfAccId;
            $etfChequeNo = trim($data['etf_cheque_no'] ?? ($data['cheque_no'] ?? ''));
            $etfChequeDt = !empty($data['etf_cheque_date']) ? date('Y-m-d', strtotime($data['etf_cheque_date'])) : (!empty($data['cheque_date']) ? date('Y-m-d', strtotime($data['cheque_date'])) : null);
            $etfRefNo    = trim($data['etf_reference_no'] ?? ($data['reference_no'] ?? ''));

            if ($periodId <= 0) {
                return ['success' => false, 'message' => 'Valid payroll period is required.'];
            }

            $totalAmount = round($epfAmount + $etfAmount, 2);
            if ($totalAmount <= 0.00) {
                return ['success' => false, 'message' => 'Remittance total amount must be greater than zero.'];
            }

            $period = self::findPeriod($periodId);
            if (!$period || !in_array($period['status'], ['approved', 'completed'], true)) {
                return ['success' => false, 'message' => 'Remittances can only be filed for approved payroll periods.'];
            }

            // Resolve EPF Credit COA Account ID
            $epfCoaId = $epfAccId;
            if (!$epfCoaId) {
                if ($epfBankId) {
                    $bRow = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :id", [':id' => $epfBankId]);
                    if ($bRow && !empty($bRow['account_id'])) {
                        $epfCoaId = (int)$bRow['account_id'];
                    }
                }
                if (!$epfCoaId) $epfCoaId = Accounting::getAccountIdByCode(Accounting::CODE_BANK);
            }

            // Resolve ETF Credit COA Account ID
            $etfCoaId = $etfAccId;
            if (!$etfCoaId) {
                if ($etfBankId) {
                    $bRow = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :id", [':id' => $etfBankId]);
                    if ($bRow && !empty($bRow['account_id'])) {
                        $etfCoaId = (int)$bRow['account_id'];
                    }
                }
                if (!$etfCoaId) $etfCoaId = Accounting::getAccountIdByCode(Accounting::CODE_BANK);
            }

            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            $remittanceNo = self::generateRemittanceNo();
            $branchId = !empty($period['branch_id']) ? (int)$period['branch_id'] : (int)(Auth::getCurrentBranchId() ?? 1);

            Database::execute("
                INSERT INTO epf_etf_remittances (
                    remittance_no, payroll_period_id, branch_id, remittance_type, 
                    total_employee_epf, total_employer_epf, total_employer_etf, 
                    total_amount, payment_date, bank_account_id, account_id, reference_no, cheque_no, cheque_date,
                    etf_bank_account_id, etf_reference_no, etf_cheque_no, notes, created_by, created_at
                ) VALUES (
                    :remittance_no, :payroll_period_id, :branch_id, :remittance_type,
                    :te_epf, :ter_epf, :ter_etf,
                    :total_amount, :payment_date, :bank_account_id, :account_id, :reference_no, :cheque_no, :cheque_date,
                    :etf_bank_account_id, :etf_reference_no, :etf_cheque_no, :notes, :created_by, NOW()
                )
            ", [
                ':remittance_no'       => $remittanceNo,
                ':payroll_period_id'   => $periodId,
                ':branch_id'           => $branchId,
                ':remittance_type'     => $type,
                ':te_epf'              => $period['total_employee_epf'],
                ':ter_epf'             => $period['total_employer_epf'],
                ':ter_etf'             => $period['total_employer_etf'],
                ':total_amount'        => $totalAmount,
                ':payment_date'        => $date,
                ':bank_account_id'     => $epfBankId,
                ':account_id'          => $epfCoaId,
                ':reference_no'        => $epfRefNo ?: null,
                ':cheque_no'           => $epfChequeNo ?: null,
                ':cheque_date'         => $epfChequeDt ?: null,
                ':etf_bank_account_id' => $etfBankId,
                ':etf_reference_no'    => $etfRefNo ?: null,
                ':etf_cheque_no'       => $etfChequeNo ?: null,
                ':notes'               => $notes ?: null,
                ':created_by'          => $userId
            ]);

            $remitId = (int)$db->lastInsertId();

            // Double Entry:
            // Dr 2055 (EPF Payable) for EPF portion
            // Cr EPF Credit COA Account
            // Dr 2060 (ETF Payable) for ETF portion
            // Cr ETF Credit COA Account
            $journalLines = [];

            if ($epfAmount > 0) {
                $accEpfPayable = Accounting::getAccountIdByCode(Accounting::CODE_EPF_PAYABLE);
                $journalLines[] = [
                    'account_id' => $accEpfPayable,
                    'debit'      => $epfAmount,
                    'credit'     => 0.00,
                    'memo'       => "EPF Statutory Remittance to Central Bank - Period {$period['period_name']}"
                ];
                $journalLines[] = [
                    'account_id' => $epfCoaId,
                    'debit'      => 0.00,
                    'credit'     => $epfAmount,
                    'memo'       => "EPF Remittance Payment Ref: {$remittanceNo}" . ($epfChequeNo ? " Chq: {$epfChequeNo}" : "")
                ];
            }

            if ($etfAmount > 0) {
                $accEtfPayable = Accounting::getAccountIdByCode(Accounting::CODE_ETF_PAYABLE);
                $journalLines[] = [
                    'account_id' => $accEtfPayable,
                    'debit'      => $etfAmount,
                    'credit'     => 0.00,
                    'memo'       => "ETF Statutory Remittance to ETF Board - Period {$period['period_name']}"
                ];
                $journalLines[] = [
                    'account_id' => $etfCoaId,
                    'debit'      => 0.00,
                    'credit'     => $etfAmount,
                    'memo'       => "ETF Remittance Payment Ref: {$remittanceNo}" . ($etfChequeNo ? " Chq: {$etfChequeNo}" : "")
                ];
            }

            $journalHeader = [
                'branch_id'   => $branchId,
                'entry_date'  => $date,
                'source_type' => 'epf_etf_remittance',
                'source_id'   => $remitId,
                'description' => "EPF/ETF Remittance #{$remittanceNo}: {$period['period_name']} (LKR {$totalAmount})",
                'created_by'  => $userId
            ];

            $accRes = Accounting::postJournalEntry($journalHeader, $journalLines);
            if (empty($accRes['success']) && empty($accRes['already_posted'])) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Accounting Journal error: ' . ($accRes['message'] ?? 'Failed to post remittance journal.')];
            }

            $db->commit();

            Audit::log('EPF_ETF_REMITTANCE_RECORDED', 'payroll', (string)$remitId, null, [
                'remittance_no' => $remittanceNo,
                'period_id'     => $periodId,
                'total_amount'  => $totalAmount
            ]);

            return [
                'success'       => true,
                'message'       => "EPF/ETF Remittance of LKR " . number_format($totalAmount, 2) . " posted successfully.",
                'remittance_no' => $remittanceNo,
                'remittance_id' => $remitId
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Payroll::remitEpfEtf failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error recording remittance: ' . $e->getMessage()];
        }
    }

    /**
     * Get detailed Payslip data for a single payroll entry
     */
    public static function getPayslip(int $entryId): ?array
    {
        try {
            $entry = Database::fetchOne("
                SELECT 
                    pe.*,
                    pp.period_code,
                    pp.period_name,
                    pp.start_date AS period_start,
                    pp.end_date AS period_end,
                    pp.status AS period_status,
                    e.employee_code,
                    e.full_name,
                    e.nic,
                    e.job_role,
                    e.joining_date,
                    e.bank_name,
                    e.bank_account_name,
                    e.bank_account_no,
                    b.name AS branch_name,
                    b.address AS branch_address,
                    b.phone AS branch_phone
                FROM payroll_entries pe
                JOIN payroll_periods pp ON pe.payroll_period_id = pp.id
                JOIN employees e ON pe.employee_id = e.id
                JOIN branches b ON pe.branch_id = b.id
                WHERE pe.id = :id
            ", [':id' => $entryId]);

            if (!$entry) {
                return null;
            }

            // Fetch payment history for this entry
            $payments = Database::fetchAll("
                SELECT pp.*, u.full_name AS paid_by_name, ba.bank_name AS bank_acc_name
                FROM payroll_payments pp
                LEFT JOIN users u ON pp.created_by = u.id
                LEFT JOIN bank_accounts ba ON pp.bank_account_id = ba.id
                WHERE pp.payroll_entry_id = :id
                ORDER BY pp.payment_date ASC, pp.id ASC
            ", [':id' => $entryId]);

            $entry['payments'] = $payments;
            $entry['outstanding_amount'] = max(0.00, round((float)$entry['net_salary'] - (float)$entry['paid_amount'], 2));

            // Fetch sales covered by this employee during period
            $startDt = $entry['period_start'] . ' 00:00:00';
            $endDt   = $entry['period_end'] . ' 23:59:59';
            $salesRow = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(total_amount), 0.00) AS period_sales,
                    COUNT(id) AS sales_count
                FROM sales
                WHERE sales_rep_id = :empid
                  AND status != 'cancelled'
                  AND created_at >= :start_dt
                  AND created_at <= :end_dt
            ", [
                ':empid'    => (int)$entry['employee_id'],
                ':start_dt' => $startDt,
                ':end_dt'   => $endDt
            ]);
            $entry['period_sales'] = (float)($salesRow['period_sales'] ?? 0);
            $entry['sales_count']  = (int)($salesRow['sales_count'] ?? 0);

            // Fetch remaining active advance balance
            $advRow = Database::fetchOne("
                SELECT COALESCE(SUM(remaining_amount), 0.00) AS remaining_advance_balance
                FROM employee_advances
                WHERE employee_id = :empid AND status = 'active'
            ", [':empid' => (int)$entry['employee_id']]);
            $entry['remaining_advance_balance'] = (float)($advRow['remaining_advance_balance'] ?? 0);

            return $entry;
        } catch (Throwable $e) {
            Logger::error("Payroll::getPayslip failed for entry {$entryId}: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Get all comprehensive payslips for an entire period
     * Fully batched queries to prevent N+1 queries.
     */
    public static function getPeriodPayslips(int $periodId): array
    {
        try {
            $entries = Database::fetchAll("
                SELECT 
                    pe.*,
                    pp.period_code,
                    pp.period_name,
                    pp.start_date AS period_start,
                    pp.end_date AS period_end,
                    pp.status AS period_status,
                    e.employee_code,
                    e.full_name,
                    e.nic,
                    e.job_role,
                    e.joining_date,
                    e.bank_name,
                    e.bank_account_name,
                    e.bank_account_no,
                    b.name AS branch_name,
                    b.address AS branch_address,
                    b.phone AS branch_phone
                FROM payroll_entries pe
                JOIN payroll_periods pp ON pe.payroll_period_id = pp.id
                JOIN employees e ON pe.employee_id = e.id
                JOIN branches b ON pe.branch_id = b.id
                WHERE pe.payroll_period_id = :pid
                ORDER BY e.employee_code ASC
            ", [':pid' => $periodId]);

            if (empty($entries)) {
                return [];
            }

            $entryIds = array_column($entries, 'id');
            $empIds   = array_column($entries, 'employee_id');
            $periodStart = $entries[0]['period_start'] . ' 00:00:00';
            $periodEnd   = $entries[0]['period_end'] . ' 23:59:59';

            // 1. Batch fetch payments
            $inEntries = implode(',', array_map('intval', $entryIds));
            $allPayments = Database::fetchAll("
                SELECT pp.*, u.full_name AS paid_by_name, ba.bank_name AS bank_acc_name
                FROM payroll_payments pp
                LEFT JOIN users u ON pp.created_by = u.id
                LEFT JOIN bank_accounts ba ON pp.bank_account_id = ba.id
                WHERE pp.payroll_entry_id IN ({$inEntries})
                ORDER BY pp.payment_date ASC, pp.id ASC
            ");
            $paymentsByEntry = [];
            foreach ($allPayments as $p) {
                $paymentsByEntry[(int)$p['payroll_entry_id']][] = $p;
            }

            // 2. Batch fetch sales
            $salesAgg = Database::fetchAll("
                SELECT 
                    s.sales_rep_id,
                    COALESCE(SUM(s.total_amount), 0.00) AS period_sales,
                    COUNT(s.id) AS sales_count
                FROM sales s
                WHERE s.sales_rep_id IS NOT NULL
                  AND s.status != 'cancelled'
                  AND s.created_at >= :start_dt
                  AND s.created_at <= :end_dt
                GROUP BY s.sales_rep_id
            ", [':start_dt' => $periodStart, ':end_dt' => $periodEnd]);
            $salesByRep = [];
            foreach ($salesAgg as $s) {
                $salesByRep[(int)$s['sales_rep_id']] = [
                    'period_sales' => round((float)$s['period_sales'], 2),
                    'sales_count'  => (int)$s['sales_count']
                ];
            }

            // 3. Batch fetch remaining advances
            $inEmps = implode(',', array_map('intval', $empIds));
            $advRows = Database::fetchAll("
                SELECT employee_id, COALESCE(SUM(remaining_amount), 0.00) AS remaining_advance_balance
                FROM employee_advances
                WHERE status = 'active' AND employee_id IN ({$inEmps})
                GROUP BY employee_id
            ");
            $advByEmp = [];
            foreach ($advRows as $a) {
                $advByEmp[(int)$a['employee_id']] = (float)$a['remaining_advance_balance'];
            }

            foreach ($entries as &$entry) {
                $eid = (int)$entry['id'];
                $empid = (int)$entry['employee_id'];
                $entry['payments'] = $paymentsByEntry[$eid] ?? [];
                $entry['outstanding_amount'] = max(0.00, round((float)$entry['net_salary'] - (float)$entry['paid_amount'], 2));
                $entry['period_sales'] = $salesByRep[$empid]['period_sales'] ?? 0.00;
                $entry['sales_count'] = $salesByRep[$empid]['sales_count'] ?? 0;
                $entry['remaining_advance_balance'] = $advByEmp[$empid] ?? 0.00;
            }
            unset($entry);

            return $entries;
        } catch (Throwable $e) {
            Logger::error("Payroll::getPeriodPayslips failed for period {$periodId}: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get employee payroll history & ledger
     */
    public static function getEmployeePayrollLedger(int $employeeId): array
    {
        try {
            return Database::fetchAll("
                SELECT 
                    pe.*,
                    pp.period_code,
                    pp.period_name,
                    pp.start_date,
                    pp.end_date,
                    b.name AS branch_name
                FROM payroll_entries pe
                JOIN payroll_periods pp ON pe.payroll_period_id = pp.id
                JOIN branches b ON pe.branch_id = b.id
                WHERE pe.employee_id = :eid
                ORDER BY pp.start_date DESC
            ", [':eid' => $employeeId]);
        } catch (Throwable $e) {
            Logger::error("Payroll::getEmployeePayrollLedger failed for employee {$employeeId}: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get active employee advances list
     */
    public static function getAdvances(array $filters = [], int $page = 1, int $limit = 25): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['employee_id'])) {
                $where[] = "ea.employee_id = :eid";
                $params[':eid'] = (int)$filters['employee_id'];
            }

            if (!empty($filters['branch_id'])) {
                $where[] = "ea.branch_id = :bid";
                $params[':bid'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['status'])) {
                $where[] = "ea.status = :st";
                $params[':st'] = $filters['status'];
            }

            if (!empty($filters['search'])) {
                $term = trim($filters['search']);
                $where[] = "(ea.advance_no LIKE :s1 OR e.full_name LIKE :s2 OR e.employee_code LIKE :s3)";
                $params[':s1'] = "%{$term}%";
                $params[':s2'] = "%{$term}%";
                $params[':s3'] = "%{$term}%";
            }

            $whereSql = !empty($where) ? implode(' AND ', $where) : '1=1';

            $countRow = Database::fetchOne("
                SELECT COUNT(ea.id) AS total 
                FROM employee_advances ea
                JOIN employees e ON ea.employee_id = e.id
                WHERE {$whereSql}
            ", $params);
            $total = (int)($countRow['total'] ?? 0);

            $offset = max(0, ($page - 1) * $limit);
            $items = Database::fetchAll("
                SELECT 
                    ea.*,
                    e.employee_code,
                    e.full_name AS employee_name,
                    e.basic_salary,
                    b.name AS branch_name,
                    u.full_name AS created_by_name,
                    cu.full_name AS cancelled_by_name,
                    pp.period_name AS recovery_period_name,
                    pp.period_code AS recovery_period_code,
                    coa.account_name AS source_account_name,
                    ba.bank_name,
                    ba.account_number
                FROM employee_advances ea
                JOIN employees e ON ea.employee_id = e.id
                JOIN branches b ON ea.branch_id = b.id
                LEFT JOIN users u ON ea.created_by = u.id
                LEFT JOIN users cu ON ea.cancelled_by = cu.id
                LEFT JOIN payroll_periods pp ON ea.recovery_period_id = pp.id
                LEFT JOIN chart_of_accounts coa ON ea.source_account_id = coa.id
                LEFT JOIN bank_accounts ba ON ea.bank_account_id = ba.id
                WHERE {$whereSql}
                ORDER BY ea.disbursement_date DESC, ea.id DESC
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
            Logger::error("Payroll::getAdvances failed: " . $e->getMessage(), $e);
            return ['items' => [], 'total' => 0, 'page' => $page, 'limit' => $limit, 'total_pages' => 1];
        }
    }

    /**
     * Get active advances for an employee
     */
    public static function getActiveAdvances(int $employeeId): array
    {
        try {
            return Database::fetchAll("
                SELECT id, advance_no, employee_id, branch_id, amount, recovered_amount, remaining_amount, disbursement_date, payment_method, recovery_period_id, status 
                FROM employee_advances 
                WHERE employee_id = :eid AND status = 'active' AND remaining_amount > 0
                ORDER BY disbursement_date ASC
            ", [':eid' => $employeeId]);
        } catch (Throwable $e) {
            Logger::error("Payroll::getActiveAdvances failed: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get comprehensive Employee Salary Advance Report & Analytics
     * Supports:
     * - KPI Summary (total disbursed, recovered, outstanding, drawer cash-out, active/cancelled counts)
     * - Branch-wise Breakdown
     * - Employee-wise Breakdown
     * - Cash Drawer Advance Cash-Out Summary
     * - Aging & Outstanding Analysis (0-30d, 31-60d, 61-90d, 90+d)
     * - Detailed Advances List
     */
    public static function getAdvanceReport(array $filters = []): array
    {
        try {
            $where = ["ea.status != 'cancelled'"];
            $params = [];

            if (!empty($filters['branch_id'])) {
                $where[] = "ea.branch_id = :bid";
                $params[':bid'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['employee_id'])) {
                $where[] = "ea.employee_id = :eid";
                $params[':eid'] = (int)$filters['employee_id'];
            }

            if (!empty($filters['start_date'])) {
                $where[] = "ea.disbursement_date >= :sdate";
                $params[':sdate'] = date('Y-m-d', strtotime($filters['start_date']));
            }

            if (!empty($filters['end_date'])) {
                $where[] = "ea.disbursement_date <= :edate";
                $params[':edate'] = date('Y-m-d', strtotime($filters['end_date']));
            }

            if (!empty($filters['payment_method'])) {
                $where[] = "ea.payment_method = :pm";
                $params[':pm'] = trim($filters['payment_method']);
            }

            $whereSql = implode(' AND ', $where);

            // 1. KPI Totals
            $kpiRow = Database::fetchOne("
                SELECT 
                    COUNT(ea.id) AS total_count,
                    COALESCE(SUM(ea.amount), 0) AS total_advanced,
                    COALESCE(SUM(ea.recovered_amount), 0) AS total_recovered,
                    COALESCE(SUM(ea.remaining_amount), 0) AS total_outstanding,
                    COALESCE(SUM(CASE WHEN LOWER(ea.payment_method) = 'cash drawer' THEN ea.amount ELSE 0 END), 0) AS total_drawer_cashout,
                    COUNT(CASE WHEN ea.status = 'active' AND ea.remaining_amount > 0 THEN 1 END) AS active_count
                FROM employee_advances ea
                WHERE {$whereSql}
            ", $params);

            // Cancelled count separately
            $cancelParams = [];
            $cancelWhere = ["ea.status = 'cancelled'"];
            if (!empty($filters['branch_id'])) {
                $cancelWhere[] = "ea.branch_id = :cbid";
                $cancelParams[':cbid'] = (int)$filters['branch_id'];
            }
            if (!empty($filters['employee_id'])) {
                $cancelWhere[] = "ea.employee_id = :ceid";
                $cancelParams[':ceid'] = (int)$filters['employee_id'];
            }
            $cancelRow = Database::fetchOne("
                SELECT COUNT(ea.id) AS cancelled_count 
                FROM employee_advances ea 
                WHERE " . implode(' AND ', $cancelWhere), $cancelParams);

            // 2. Branch-wise Breakdown
            $branchBreakdown = Database::fetchAll("
                SELECT 
                    b.id AS branch_id,
                    b.name AS branch_name,
                    COUNT(ea.id) AS advance_count,
                    COALESCE(SUM(ea.amount), 0) AS total_advanced,
                    COALESCE(SUM(ea.recovered_amount), 0) AS total_recovered,
                    COALESCE(SUM(ea.remaining_amount), 0) AS remaining_balance
                FROM employee_advances ea
                INNER JOIN branches b ON ea.branch_id = b.id
                WHERE {$whereSql}
                GROUP BY b.id, b.name
                ORDER BY total_advanced DESC
            ", $params);

            // 3. Employee-wise Breakdown
            $employeeBreakdown = Database::fetchAll("
                SELECT 
                    e.id AS employee_id,
                    e.employee_code,
                    e.full_name AS employee_name,
                    e.basic_salary,
                    b.name AS branch_name,
                    COUNT(ea.id) AS advance_count,
                    COALESCE(SUM(ea.amount), 0) AS total_advanced,
                    COALESCE(SUM(ea.recovered_amount), 0) AS total_recovered,
                    COALESCE(SUM(ea.remaining_amount), 0) AS remaining_balance
                FROM employee_advances ea
                INNER JOIN employees e ON ea.employee_id = e.id
                INNER JOIN branches b ON ea.branch_id = b.id
                WHERE {$whereSql}
                GROUP BY e.id, e.employee_code, e.full_name, e.basic_salary, b.name
                ORDER BY remaining_balance DESC, total_advanced DESC
            ", $params);

            // 4. Cash Drawer Advance Cash-Out Details
            $drawerWhere = $where;
            $drawerWhere[] = "LOWER(ea.payment_method) = 'cash drawer'";
            $drawerCashouts = Database::fetchAll("
                SELECT 
                    ea.id, ea.advance_no, ea.amount, ea.disbursement_date, ea.notes,
                    e.employee_code, e.full_name AS employee_name,
                    b.name AS branch_name,
                    u.full_name AS created_by_name
                FROM employee_advances ea
                INNER JOIN employees e ON ea.employee_id = e.id
                INNER JOIN branches b ON ea.branch_id = b.id
                LEFT JOIN users u ON ea.created_by = u.id
                WHERE " . implode(' AND ', $drawerWhere) . "
                ORDER BY ea.disbursement_date DESC, ea.id DESC
            ", $params);

            // 5. Aging / Outstanding Advances Analysis
            $agingRows = Database::fetchAll("
                SELECT 
                    ea.id,
                    ea.advance_no,
                    ea.amount,
                    ea.remaining_amount,
                    ea.disbursement_date,
                    DATEDIFF(CURRENT_DATE, ea.disbursement_date) AS days_outstanding,
                    e.employee_code,
                    e.full_name AS employee_name,
                    b.name AS branch_name
                FROM employee_advances ea
                INNER JOIN employees e ON ea.employee_id = e.id
                INNER JOIN branches b ON ea.branch_id = b.id
                WHERE ea.status = 'active' AND ea.remaining_amount > 0" . 
                (!empty($filters['branch_id']) ? " AND ea.branch_id = " . (int)$filters['branch_id'] : "") . "
                ORDER BY days_outstanding DESC
            ");

            $agingSummary = [
                'days_0_30'   => ['count' => 0, 'amount' => 0.00],
                'days_31_60'  => ['count' => 0, 'amount' => 0.00],
                'days_61_90'  => ['count' => 0, 'amount' => 0.00],
                'days_90_plus'=> ['count' => 0, 'amount' => 0.00],
            ];

            foreach ($agingRows as $ar) {
                $days = (int)$ar['days_outstanding'];
                $rem  = (float)$ar['remaining_amount'];
                if ($days <= 30) {
                    $agingSummary['days_0_30']['count']++;
                    $agingSummary['days_0_30']['amount'] += $rem;
                } elseif ($days <= 60) {
                    $agingSummary['days_31_60']['count']++;
                    $agingSummary['days_31_60']['amount'] += $rem;
                } elseif ($days <= 90) {
                    $agingSummary['days_61_90']['count']++;
                    $agingSummary['days_61_90']['amount'] += $rem;
                } else {
                    $agingSummary['days_90_plus']['count']++;
                    $agingSummary['days_90_plus']['amount'] += $rem;
                }
            }

            return [
                'success'           => true,
                'kpis'              => [
                    'total_advanced'       => (float)($kpiRow['total_advanced'] ?? 0),
                    'total_recovered'      => (float)($kpiRow['total_recovered'] ?? 0),
                    'total_outstanding'    => (float)($kpiRow['total_outstanding'] ?? 0),
                    'total_drawer_cashout' => (float)($kpiRow['total_drawer_cashout'] ?? 0),
                    'active_count'         => (int)($kpiRow['active_count'] ?? 0),
                    'total_count'          => (int)($kpiRow['total_count'] ?? 0),
                    'cancelled_count'      => (int)($cancelRow['cancelled_count'] ?? 0),
                ],
                'branch_breakdown'   => $branchBreakdown,
                'employee_breakdown' => $employeeBreakdown,
                'drawer_cashouts'    => $drawerCashouts,
                'aging_summary'      => $agingSummary,
                'aging_items'        => $agingRows
            ];
        } catch (Throwable $e) {
            Logger::error("Payroll::getAdvanceReport failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function getPeriodById(int $id): ?array
    {
        return self::findPeriod($id);
    }

    public static function generatePayslip(int $entryId): ?array
    {
        return self::getPayslip($entryId);
    }

    public static function remitContributions(array $data, int $userId): array
    {
        return self::remitEpfEtf($data, $userId);
    }
}
