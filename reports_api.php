<?php
/**
 * Curtiss POS - Central Reports API Endpoint (Stage 16)
 * Handles report data queries and CSV/Excel export generation.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/ReportsEngine.php';
require_once __DIR__ . '/core/Logger.php';

try {
    if (!Auth::isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Session expired or unauthenticated. Please log in again.']);
        exit;
    }
    Auth::checkInactivityTimeout();
    $user = Auth::user();

    if (!Auth::hasPermission('view_reports')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied: view_reports required.']);
        exit;
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    // Standardize input filters
    $filters = [
        'branch_id'   => $_GET['branch_id'] ?? $_POST['branch_id'] ?? '',
        'date_from'   => $_GET['date_from'] ?? $_POST['date_from'] ?? '',
        'date_to'     => $_GET['date_to'] ?? $_POST['date_to'] ?? '',
        'category_id'     => $_GET['category_id'] ?? $_POST['category_id'] ?? '',
        'brand_id'        => $_GET['brand_id'] ?? $_POST['brand_id'] ?? '',
        'supplier_id'     => $_GET['supplier_id'] ?? $_POST['supplier_id'] ?? '',
        'bank_account_id' => $_GET['bank_account_id'] ?? $_POST['bank_account_id'] ?? '',
        'search'          => $_GET['search'] ?? $_POST['search'] ?? ''
    ];

    // Branch Security: restrict if user is not authorized for all branches
    $userBranches = $_SESSION['user_branches'] ?? [];
    if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN) && !empty($userBranches)) {
        $allowedBranchIds = array_column($userBranches, 'id');
        if (empty($filters['branch_id']) || $filters['branch_id'] === 'all' || !in_array((int)$filters['branch_id'], $allowedBranchIds)) {
            $filters['branch_id'] = $_SESSION['branch_id'] ?? $allowedBranchIds[0];
        }
    }

    switch ($action) {

        case 'sales_summary':
            $data = ReportsEngine::getSalesSummary($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'sales_by_product':
            $data = ReportsEngine::getSalesByProduct($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'sales_by_category':
            $data = ReportsEngine::getSalesByCategory($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'sales_by_payment':
            $data = ReportsEngine::getSalesByPaymentMethod($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'sales_history':
            $data = ReportsEngine::getSalesHistoryReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'purchase_summary':
            $data = ReportsEngine::getPurchaseSummary($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'supplier_outstanding':
            $data = ReportsEngine::getSupplierOutstandingReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'stock_valuation':
            $data = ReportsEngine::getStockValuationReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'low_stock':
            $data = ReportsEngine::getLowStockReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'trial_balance':
            if (!Auth::hasPermission('view_financial_statements') || Auth::isSpecialUser()) {
                echo json_encode(['success' => false, 'message' => 'Permission denied: view_financial_statements required.']);
                exit;
            }
            $data = ReportsEngine::getTrialBalance($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'profit_and_loss':
            if (!Auth::hasPermission('view_financial_statements') || Auth::isSpecialUser()) {
                echo json_encode(['success' => false, 'message' => 'Permission denied: view_financial_statements required.']);
                exit;
            }
            $data = ReportsEngine::getProfitAndLoss($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'balance_sheet':
            if (!Auth::hasPermission('view_financial_statements') || Auth::isSpecialUser()) {
                echo json_encode(['success' => false, 'message' => 'Permission denied: view_financial_statements required.']);
                exit;
            }
            $data = ReportsEngine::getBalanceSheet($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;


        case 'damaged_stock':
            $data = ReportsEngine::getDamagedStockReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'in_transit_stock':
            $data = ReportsEngine::getInTransitStockReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'petty_cash_register':
            $data = ReportsEngine::getPettyCashRegisterReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'expense_register':
            $data = ReportsEngine::getExpenseRegisterReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'expenses_by_category':
            $data = ReportsEngine::getExpensesByCategoryReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'bank_register':
            $data = ReportsEngine::getBankRegisterReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'supplier_cheques':
            $data = ReportsEngine::getSupplierChequeReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'kpi_performance':
            $data = ReportsEngine::getKPIPerformance($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'cashier_performance':
            $data = ReportsEngine::getCashierPerformanceReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'supplier_returns':
            $data = ReportsEngine::getSupplierReturnsReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'stock_adjustments':
            $data = ReportsEngine::getStockAdjustmentsReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'returns_adjustments':
            $data = ReportsEngine::getSupplierReturnsReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'expired_stock':
            require_once __DIR__ . '/core/ExpiryManager.php';
            $bId = (int)(!empty($filters['branch_id']) && $filters['branch_id'] !== 'all' ? $filters['branch_id'] : ($_SESSION['branch_id'] ?? 1));
            $data = ExpiryManager::getExpiredStockReport($bId, $filters, 1, 500);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'near_expiry':
            require_once __DIR__ . '/core/ExpiryManager.php';
            $bId = (int)(!empty($filters['branch_id']) && $filters['branch_id'] !== 'all' ? $filters['branch_id'] : ($_SESSION['branch_id'] ?? 1));
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 0;
            $data = ExpiryManager::getNearExpiryReport($bId, $days, $filters, 1, 500);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'batch_expiry_audit':
            require_once __DIR__ . '/core/ExpiryManager.php';
            $bId = (int)(!empty($filters['branch_id']) && $filters['branch_id'] !== 'all' ? $filters['branch_id'] : ($_SESSION['branch_id'] ?? 1));
            $data = ExpiryManager::getBatchExpiryAuditReport($bId, $filters, 1, 500);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'expired_writeoff_history':
            require_once __DIR__ . '/core/ExpiryManager.php';
            $bId = (int)(!empty($filters['branch_id']) && $filters['branch_id'] !== 'all' ? $filters['branch_id'] : ($_SESSION['branch_id'] ?? 1));
            $data = ExpiryManager::getExpiredWriteOffHistoryReport($bId, $filters, 1, 500);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'supplier_expiry_performance':
            require_once __DIR__ . '/core/ExpiryManager.php';
            $bId = (int)(!empty($filters['branch_id']) && $filters['branch_id'] !== 'all' ? $filters['branch_id'] : ($_SESSION['branch_id'] ?? 1));
            $data = ExpiryManager::getSupplierExpiryPerformanceReport($bId);
            echo json_encode(['success' => true, 'data' => ['items' => $data]]);
            break;

        case 'discount_usage':
            $page  = (int)($_GET['page'] ?? $_POST['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 25);
            $data = ReportsEngine::getDiscountUsageReport($filters, $page, $limit);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'promotion_performance':
            $data = ReportsEngine::getPromotionPerformanceReport($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'export':
            if (!Auth::hasPermission('export_reports')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied: export_reports required.']);
                exit;
            }

            $reportType = $_GET['report_type'] ?? $_POST['report_type'] ?? 'sales_summary';
            $format = strtolower($_GET['format'] ?? $_POST['format'] ?? 'csv');
            $timestamp = date('Ymd_His');

            // Collect dataset rows based on report type
            $headers = [];
            $rows = [];

            if ($reportType === 'sales_by_product') {
                $headers = ['SKU', 'Product Name', 'Category', 'Brand', 'Units Sold', 'Total Sales (LKR)', 'Total Cost (LKR)', 'Gross Profit (LKR)', 'Margin (%)'];
                $data = ReportsEngine::getSalesByProduct($filters);
                foreach ($data as $r) {
                    $rows[] = [
                        $r['sku'], $r['product_name'], $r['category_name'], $r['brand_name'],
                        $r['total_units'], number_format($r['total_sales'], 2, '.', ''),
                        number_format($r['total_cost'], 2, '.', ''), number_format($r['gross_profit'], 2, '.', ''),
                        number_format($r['profit_margin'], 2, '.', '') . '%'
                    ];
                }
            } elseif ($reportType === 'sales_by_category') {
                $headers = ['Category Name', 'Units Sold', 'Total Sales (LKR)', 'Total Cost (LKR)', 'Gross Profit (LKR)'];
                $data = ReportsEngine::getSalesByCategory($filters);
                foreach ($data as $r) {
                    $rows[] = [
                        $r['category_name'], $r['total_units'],
                        number_format($r['total_sales'], 2, '.', ''),
                        number_format($r['total_cost'], 2, '.', ''),
                        number_format($r['gross_profit'], 2, '.', '')
                    ];
                }
            } elseif ($reportType === 'sales_by_payment') {
                $headers = ['Payment Method', 'Transaction Count', 'Total Collected (LKR)'];
                $data = ReportsEngine::getSalesByPaymentMethod($filters);
                foreach ($data as $r) {
                    $rows[] = [
                        strtoupper($r['payment_method']), $r['transaction_count'],
                        number_format($r['total_amount'], 2, '.', '')
                    ];
                }
            } elseif ($reportType === 'sales_history') {
                $headers = ['Invoice #', 'Date & Time', 'Branch', 'Customer', 'Payment Method', 'Cashier', 'Subtotal (LKR)', 'Discount (LKR)', 'Net Total (LKR)', 'Status'];
                $data = ReportsEngine::getSalesHistoryReport($filters);
                foreach ($data as $r) {
                    $rows[] = [
                        $r['invoice_no'], $r['sale_date'], $r['branch_name'], $r['customer_name'],
                        $r['payment_method'], $r['cashier_name'], number_format($r['subtotal'], 2, '.', ''),
                        number_format($r['discount_amount'], 2, '.', ''), number_format($r['total_amount'], 2, '.', ''),
                        strtoupper($r['status'])
                    ];
                }
            } elseif ($reportType === 'purchase_summary') {
                $headers = ['GRN Count', 'Total GRN Amount (LKR)', 'Total Payments Paid (LKR)', 'Total Returns (LKR)', 'Outstanding Due (LKR)'];
                $summary = ReportsEngine::getPurchaseSummary($filters);
                $rows[] = [
                    $summary['grn_count'],
                    number_format($summary['total_grn_amount'], 2, '.', ''),
                    number_format($summary['total_payments_paid'], 2, '.', ''),
                    number_format($summary['total_returns'], 2, '.', ''),
                    number_format($summary['outstanding_due'], 2, '.', '')
                ];
            } elseif ($reportType === 'supplier_outstanding') {
                $headers = ['Supplier Name', 'Phone', 'Total Purchases (LKR)', 'Total Paid (LKR)', 'Outstanding Balance (LKR)'];
                $data = ReportsEngine::getSupplierOutstandingReport($filters);
                foreach ($data as $r) {
                    $rows[] = [
                        $r['supplier_name'], $r['phone'] ?? '-',
                        number_format($r['total_grn_amount'], 2, '.', ''),
                        number_format($r['total_paid'], 2, '.', ''),
                        number_format($r['outstanding_balance'], 2, '.', '')
                    ];
                }
            } elseif ($reportType === 'stock_valuation') {
                $headers = ['Lot #', 'SKU', 'Product Name', 'Category', 'Brand', 'Remaining Qty', 'Unit Cost (LKR)', 'Stock Valuation (LKR)'];
                $report = ReportsEngine::getStockValuationReport($filters);
                foreach ($report['items'] as $r) {
                    $rows[] = [
                        $r['lot_number'], $r['sku'], $r['name'], $r['category_name'], $r['brand_name'],
                        $r['remaining_qty'], number_format($r['unit_cost'], 2, '.', ''),
                        number_format($r['total_lot_valuation'], 2, '.', '')
                    ];
                }
                $rows[] = [];
                $rows[] = ['TOTAL REMAINING QTY', '', '', '', '', $report['total_qty'], '', ''];
                $rows[] = ['TOTAL STOCK VALUATION', '', '', '', '', '', '', number_format($report['total_valuation'], 2, '.', '')];
            } elseif ($reportType === 'low_stock') {
                $headers = ['SKU', 'Product Name', 'Category', 'Current Stock', 'Reorder Threshold'];
                $data = ReportsEngine::getLowStockReport($filters);
                foreach ($data as $r) {
                    $rows[] = [
                        $r['sku'], $r['name'], $r['category_name'],
                        $r['total_stock'], $r['reorder_level']
                    ];
                }
            } elseif ($reportType === 'cashier_performance') {
                $headers = ['Day #', 'Branch', 'Opened By', 'Opened At', 'Closed At', 'Opening Float (LKR)', 'Total Sales (LKR)', 'Total Deposits (LKR)', 'Closing Cash (LKR)', 'Status'];
                $data = ReportsEngine::getCashierPerformanceReport($filters);
                foreach (($data['items'] ?? []) as $r) {
                    $rows[] = [
                        '#' . $r['day_id'], $r['branch_name'], $r['opened_by'], $r['opened_at'], $r['closed_at'] ?? 'Active Open Day',
                        number_format($r['opening_cash'], 2, '.', ''), number_format($r['total_sales'], 2, '.', ''),
                        number_format($r['total_deposits'], 2, '.', ''), number_format($r['closing_cash'], 2, '.', ''),
                        strtoupper($r['status'])
                    ];
                }
            } elseif ($reportType === 'supplier_returns' || $reportType === 'returns_adjustments') {
                $headers = ['Return #', 'Return Date', 'Branch', 'Supplier', 'GRN Ref', 'Total Return (LKR)', 'AP Offset (LKR)', 'Credit Amount (LKR)', 'Notes', 'Status'];
                $data = ReportsEngine::getSupplierReturnsReport($filters);
                foreach (($data['items'] ?? []) as $r) {
                    $rows[] = [
                        $r['return_number'], $r['return_date'], $r['branch_name'], $r['supplier_name'],
                        $r['grn_number'], number_format($r['total_return_amount'], 2, '.', ''),
                        number_format($r['applied_to_payable'], 2, '.', ''), number_format($r['credit_amount'], 2, '.', ''),
                        $r['notes'] ?? '', strtoupper($r['status'] ?? 'COMPLETED')
                    ];
                }
            } elseif ($reportType === 'stock_adjustments') {
                $headers = ['Adjustment #', 'Date', 'Branch', 'SKU', 'Product Name', 'Lot #', 'Type', 'Quantity', 'Unit Cost (LKR)', 'Total Value (LKR)', 'Reason', 'Adjusted By'];
                $data = ReportsEngine::getStockAdjustmentsReport($filters);
                foreach (($data['items'] ?? []) as $r) {
                    $rows[] = [
                        $r['adjustment_number'], $r['adjustment_date'], $r['branch_name'], $r['sku'],
                        $r['product_name'], $r['lot_number'], ucfirst($r['adjustment_type']),
                        $r['quantity'], number_format($r['unit_cost'], 2, '.', ''),
                        number_format($r['total_value'], 2, '.', ''), $r['reason'], $r['adjusted_by']
                    ];
                }
            } elseif ($reportType === 'damaged_stock') {
                $headers = ['SKU', 'Product Name', 'Branch', 'Stock Type', 'Quantity', 'Cost Price (LKR)', 'Total Cost Loss (LKR)', 'Reason', 'Logged Date'];
                $data = ReportsEngine::getDamagedStockReport($filters);
                foreach (($data['items'] ?? []) as $r) {
                    $rows[] = [
                        $r['sku'], $r['product_name'], $r['branch_name'], $r['stock_type'],
                        $r['quantity'], number_format($r['cost'], 2, '.', ''),
                        number_format($r['total_cost_loss'], 2, '.', ''), $r['reason'], $r['logged_date']
                    ];
                }
            } elseif ($reportType === 'in_transit_stock') {
                $headers = ['Transfer #', 'From Branch', 'To Branch', 'SKU', 'Product Name', 'Quantity', 'Unit Cost (LKR)', 'Transfer Value (LKR)', 'Sent Date'];
                $data = ReportsEngine::getInTransitStockReport($filters);
                foreach (($data['items'] ?? []) as $r) {
                    $rows[] = [
                        $r['transfer_number'], $r['from_branch'], $r['to_branch'], $r['sku'], $r['product_name'],
                        $r['quantity'], number_format($r['unit_cost'], 2, '.', ''),
                        number_format($r['transfer_value'], 2, '.', ''), $r['sent_at']
                    ];
                }
            } elseif ($reportType === 'petty_cash_register') {
                $headers = ['ID', 'Branch', 'Movement Type', 'Reason', 'Amount (LKR)', 'Date & Time'];
                $data = ReportsEngine::getPettyCashRegisterReport($filters);
                foreach (($data['items'] ?? []) as $r) {
                    $rows[] = [
                        '#' . $r['id'], $r['branch_name'], strtoupper($r['movement_type']),
                        $r['reason'], number_format($r['amount'], 2, '.', ''), $r['created_at']
                    ];
                }
            } elseif ($reportType === 'bank_register') {
                $headers = ['Entry #', 'Date', 'Bank Account', 'Description', 'Debit (LKR)', 'Credit (LKR)', 'Running Balance (LKR)'];
                $data = ReportsEngine::getBankRegisterReport($filters);
                foreach (($data['items'] ?? []) as $r) {
                    $bankDesc = !empty($r['bank_name']) ? "{$r['bank_name']} ({$r['account_number']})" : '-';
                    $rows[] = [
                        $r['entry_number'], $r['entry_date'], $bankDesc, $r['description'],
                        number_format($r['debit'], 2, '.', ''), number_format($r['credit'], 2, '.', ''),
                        number_format($r['running_balance'], 2, '.', '')
                    ];
                }
            } elseif ($reportType === 'supplier_cheques') {
                $headers = ['Cheque #', 'Supplier Name', 'Cheque Date', 'Amount (LKR)', 'Branch', 'Status'];
                $data = ReportsEngine::getSupplierChequeReport($filters);
                foreach (($data['items'] ?? []) as $r) {
                    $rows[] = [
                        $r['cheque_number'], $r['supplier_name'], $r['cheque_date'],
                        number_format($r['amount'], 2, '.', ''), $r['branch_name'], strtoupper($r['status'])
                    ];
                }
            } elseif ($reportType === 'expired_stock') {
                require_once __DIR__ . '/core/ExpiryManager.php';
                $bId = (int)(!empty($filters['branch_id']) && $filters['branch_id'] !== 'all' ? $filters['branch_id'] : ($_SESSION['branch_id'] ?? 1));
                $headers = ['Lot #', 'SKU', 'Product Name', 'Batch #', 'Expiry Date', 'Days Expired', 'Qty', 'Unit Cost (LKR)', 'Loss Valuation (LKR)', 'Branch'];
                $data = ExpiryManager::getExpiredStockReport($bId, $filters, 1, 5000);
                foreach (($data['items'] ?? []) as $r) {
                    $rows[] = [
                        $r['lot_number'], $r['sku'], $r['product_name'], $r['batch_number'] ?? '-',
                        $r['expiry_date'], $r['days_expired'], $r['current_quantity'],
                        number_format($r['unit_cost'], 2, '.', ''), number_format($r['loss_value'], 2, '.', ''),
                        $r['branch_name']
                    ];
                }
            } elseif ($reportType === 'near_expiry') {
                require_once __DIR__ . '/core/ExpiryManager.php';
                $bId = (int)(!empty($filters['branch_id']) && $filters['branch_id'] !== 'all' ? $filters['branch_id'] : ($_SESSION['branch_id'] ?? 1));
                $days = (int)($_GET['days'] ?? 30);
                $headers = ['Lot #', 'SKU', 'Product Name', 'Batch #', 'Expiry Date', 'Days Remaining', 'Urgency', 'Qty', 'Unit Cost (LKR)', 'At-Risk Valuation (LKR)', 'Branch'];
                $data = ExpiryManager::getNearExpiryReport($bId, $days, $filters, 1, 5000);
                foreach (($data['items'] ?? []) as $r) {
                    $rows[] = [
                        $r['lot_number'], $r['sku'], $r['product_name'], $r['batch_number'] ?? '-',
                        $r['expiry_date'], $r['days_until_expiry'], strtoupper($r['urgency_level']),
                        $r['current_quantity'], number_format($r['unit_cost'], 2, '.', ''),
                        number_format($r['total_cost'], 2, '.', ''), $r['branch_name']
                    ];
                }
            } elseif ($reportType === 'batch_expiry_audit') {
                require_once __DIR__ . '/core/ExpiryManager.php';
                $bId = (int)(!empty($filters['branch_id']) && $filters['branch_id'] !== 'all' ? $filters['branch_id'] : ($_SESSION['branch_id'] ?? 1));
                $headers = ['Lot #', 'SKU', 'Product Name', 'Batch #', 'Expiry Date', 'Status', 'Qty', 'Unit Cost (LKR)', 'Valuation (LKR)', 'Source', 'Received Date', 'Branch'];
                $data = ExpiryManager::getBatchExpiryAuditReport($bId, $filters, 1, 5000);
                foreach (($data['items'] ?? []) as $r) {
                    $rows[] = [
                        $r['lot_number'], $r['sku'], $r['product_name'], $r['batch_number'] ?? '-',
                        $r['expiry_date'] ?? 'N/A', $r['status_label'], $r['current_quantity'],
                        number_format($r['unit_cost'], 2, '.', ''), number_format($r['total_valuation'], 2, '.', ''),
                        ucfirst($r['source_type'] ?? '-'), substr($r['created_at'], 0, 10), $r['branch_name']
                    ];
                }
            } elseif ($reportType === 'expired_writeoff_history') {
                require_once __DIR__ . '/core/ExpiryManager.php';
                $bId = (int)(!empty($filters['branch_id']) && $filters['branch_id'] !== 'all' ? $filters['branch_id'] : ($_SESSION['branch_id'] ?? 1));
                $headers = ['Write-Off Date', 'SKU', 'Product Name', 'Batch #', 'Expiry Date', 'Qty Written-Off', 'Unit Cost (LKR)', 'Loss (LKR)', 'Authorized By', 'Reason & Notes', 'Branch'];
                $data = ExpiryManager::getExpiredWriteOffHistoryReport($bId, $filters, 1, 5000);
                foreach (($data['items'] ?? []) as $r) {
                    $rows[] = [
                        $r['created_at'], $r['sku'], $r['product_name'], $r['batch_number'] ?? '-',
                        $r['expiry_date'] ?? '-', $r['quantity'], number_format($r['unit_cost'], 2, '.', ''),
                        number_format($r['total_loss'], 2, '.', ''), $r['user_name'] ?? 'System',
                        trim(($r['reason'] ?? '') . ' ' . ($r['notes'] ?? '')), $r['branch_name']
                    ];
                }
            } elseif ($reportType === 'supplier_expiry_performance') {
                require_once __DIR__ . '/core/ExpiryManager.php';
                $bId = (int)(!empty($filters['branch_id']) && $filters['branch_id'] !== 'all' ? $filters['branch_id'] : ($_SESSION['branch_id'] ?? 1));
                $headers = ['Supplier Name', 'Phone', 'Total Lots Received', 'Total Qty Received', 'Active Qty', 'Expired Qty', 'Expired Loss Value (LKR)', 'Expiry Rate (%)'];
                $data = ExpiryManager::getSupplierExpiryPerformanceReport($bId);
                foreach ($data as $r) {
                    $rows[] = [
                        $r['supplier_name'], $r['phone'] ?? '-', $r['total_lots_received'],
                        $r['total_qty_received'], $r['active_qty'], $r['expired_qty'],
                        number_format($r['total_loss_value'], 2, '.', ''),
                        number_format($r['expiry_rate_pct'], 2, '.', '') . '%'
                    ];
                }
                $headers = ['Metric Category', 'Metric Name', 'Value / Details'];
                $kpi = ReportsEngine::getKPIPerformance($filters);
                $rows[] = ['Sales & Revenue', 'Gross Sales Revenue', number_format($kpi['sales']['gross_sales'], 2, '.', '') . ' LKR'];
                $rows[] = ['Sales & Revenue', 'Net Sales (After Discount)', number_format($kpi['sales']['net_sales'], 2, '.', '') . ' LKR'];
                $rows[] = ['Sales & Revenue', 'Cost of Goods Sold (COGS)', number_format($kpi['sales']['cogs'], 2, '.', '') . ' LKR'];
                $rows[] = ['Sales & Revenue', 'Gross Profit', number_format($kpi['sales']['gross_profit'], 2, '.', '') . ' LKR'];
                $rows[] = ['Sales & Revenue', 'Profit Margin', number_format($kpi['sales']['profit_margin'], 2, '.', '') . '%'];
                $rows[] = ['Inventory', 'Total Stock Valuation', number_format($kpi['inventory']['total_valuation'], 2, '.', '') . ' LKR'];
                $rows[] = ['Purchases & Payables', 'Total Supplier Payables', number_format($kpi['purchases']['total_supplier_outstanding'], 2, '.', '') . ' LKR'];
            } elseif ($reportType === 'trial_balance') {
                $headers = ['Account Code', 'Account Name', 'Type', 'Debit (LKR)', 'Credit (LKR)'];
                $tb = ReportsEngine::getTrialBalance($filters);
                foreach ($tb['accounts'] as $r) {
                    $rows[] = [
                        $r['account_code'], $r['account_name'], strtoupper($r['account_type']),
                        number_format($r['debit'], 2, '.', ''), number_format($r['credit'], 2, '.', '')
                    ];
                }
                $rows[] = [];
                $rows[] = ['TOTAL DEBIT', '', '', number_format($tb['total_debit'], 2, '.', ''), ''];
                $rows[] = ['TOTAL CREDIT', '', '', '', number_format($tb['total_credit'], 2, '.', '')];
                $rows[] = ['BALANCED', '', '', $tb['is_balanced'] ? 'YES' : 'NO', ''];
            } elseif ($reportType === 'profit_and_loss') {
                $headers = ['Category / Account', 'Amount (LKR)'];
                $pnl = ReportsEngine::getProfitAndLoss($filters);
                $rows[] = ['Gross Sales Revenue (4010)', number_format($pnl['gross_sales'], 2, '.', '')];
                $rows[] = ['Sales Returns (4020)', number_format($pnl['sales_returns'], 2, '.', '')];
                $rows[] = ['Net Sales Revenue', number_format($pnl['net_revenue'], 2, '.', '')];
                $rows[] = ['Cost of Goods Sold (5010)', number_format($pnl['cogs'], 2, '.', '')];
                $rows[] = ['GROSS PROFIT', number_format($pnl['gross_profit'], 2, '.', '')];
                $rows[] = ['Bank Interest & Other Income (4050)', number_format($pnl['other_income'], 2, '.', '')];
                $rows[] = ['Operating Expenses', number_format($pnl['total_expenses'], 2, '.', '')];
                $rows[] = ['NET PROFIT / (LOSS)', number_format($pnl['net_profit'], 2, '.', '')];
            } elseif ($reportType === 'balance_sheet') {
                $headers = ['Section / Account Code', 'Account Name', 'Amount (LKR)'];
                $bs = ReportsEngine::getBalanceSheet($filters);
                $rows[] = ['ASSETS', '', ''];
                foreach ($bs['assets']['accounts'] as $r) {
                    $rows[] = [$r['account_code'], $r['account_name'], number_format($r['balance'], 2, '.', '')];
                }
                $rows[] = ['TOTAL ASSETS', '', number_format($bs['assets']['total'], 2, '.', '')];
                $rows[] = [];
                $rows[] = ['LIABILITIES', '', ''];
                foreach ($bs['liabilities']['accounts'] as $r) {
                    $rows[] = [$r['account_code'], $r['account_name'], number_format($r['balance'], 2, '.', '')];
                }
                $rows[] = ['TOTAL LIABILITIES', '', number_format($bs['liabilities']['total'], 2, '.', '')];
                $rows[] = [];
                $rows[] = ['EQUITY', '', ''];
                foreach ($bs['equity']['accounts'] as $r) {
                    $rows[] = [$r['account_code'], $r['account_name'], number_format($r['balance'], 2, '.', '')];
                }
                $rows[] = ['Retained Earnings (Current Period P&L)', 'Net Profit', number_format($bs['retained_earnings'], 2, '.', '')];
                $rows[] = ['TOTAL EQUITY', '', number_format($bs['equity']['total'], 2, '.', '')];
                $rows[] = [];
                $rows[] = ['TOTAL LIABILITIES & EQUITY', '', number_format($bs['total_liabilities_and_equity'], 2, '.', '')];
            } else {
                $headers = ['Metric', 'Value'];
                $summary = ReportsEngine::getSalesSummary($filters);
                $rows[] = ['Invoice Count', $summary['invoice_count']];
                $rows[] = ['Gross Sales (LKR)', number_format($summary['gross_sales'], 2, '.', '')];
                $rows[] = ['Discounts (LKR)', number_format($summary['total_discounts'], 2, '.', '')];
                $rows[] = ['Net Sales (LKR)', number_format($summary['net_sales'], 2, '.', '')];
                $rows[] = ['COGS (LKR)', number_format($summary['cogs'], 2, '.', '')];
                $rows[] = ['Gross Profit (LKR)', number_format($summary['gross_profit'], 2, '.', '')];
            }

            // Filter customized visible columns if specified
            $visibleCols = $_GET['visible_cols'] ?? $_POST['visible_cols'] ?? '';
            if ($visibleCols !== '') {
                $visibleIndices = array_map('intval', explode(',', $visibleCols));
                if (!empty($headers)) {
                    $filteredHeaders = [];
                    foreach ($visibleIndices as $idx) {
                        if (isset($headers[$idx])) {
                            $filteredHeaders[] = $headers[$idx];
                        }
                    }
                    $headers = $filteredHeaders;
                }
                $filteredRows = [];
                foreach ($rows as $r) {
                    if (empty($r)) {
                        $filteredRows[] = [];
                        continue;
                    }
                    $rowFiltered = [];
                    foreach ($visibleIndices as $idx) {
                        if (array_key_exists($idx, $r)) {
                            $rowFiltered[] = $r[$idx];
                        }
                    }
                    $filteredRows[] = $rowFiltered;
                }
                $rows = $filteredRows;
            }

            if ($format === 'excel') {
                $filename = "report_{$reportType}_{$timestamp}.csv";
                header('Content-Type: application/vnd.ms-excel; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                $output = fopen('php://output', 'w');
                fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM for Microsoft Excel
                if (!empty($headers)) {
                    fputcsv($output, $headers);
                }
                foreach ($rows as $r) {
                    fputcsv($output, $r);
                }
                fclose($output);
                exit;
            } elseif ($format === 'json') {
                $filename = "report_{$reportType}_{$timestamp}.json";
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo json_encode(['report' => $reportType, 'headers' => $headers, 'rows' => $rows, 'generated_at' => date('c')], JSON_PRETTY_PRINT);
                exit;
            } else {
                // Default CSV Export
                $filename = "report_{$reportType}_{$timestamp}.csv";
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');

                $output = fopen('php://output', 'w');
                fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM
                if (!empty($headers)) fputcsv($output, $headers);
                foreach ($rows as $r) {
                    fputcsv($output, $r);
                }
                fclose($output);
                exit;
            }

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid or missing action parameter.']);
            break;
    }

} catch (Throwable $e) {
    Logger::error('Reports API Error: ' . $e->getMessage(), ['exception' => $e]);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
