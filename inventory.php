<?php
/**
 * Curtiss POS - Branch Inventory Controller (Stage 3 & 17 Special User Support)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Inventory.php';
require_once __DIR__ . '/core/Brand.php';
require_once __DIR__ . '/core/Category.php';
require_once __DIR__ . '/core/Product.php';
require_once __DIR__ . '/core/MaskingHelper.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);

$successMsg = null;
$errorMsg = null;

// POST Action to Add Opening Stock Lot (Restricted for Special User)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_lot') {
    if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER) || Auth::isSpecialUser()) {
        $errorMsg = 'Unauthorized action. Special User is strictly view-only.';
    } else {
        $productId = (int)($_POST['product_id'] ?? 0);
        $cost = (float)($_POST['cost'] ?? 0);
        $sellingPrice = (float)($_POST['selling_price'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        $lotNumber = trim($_POST['lot_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $res = Inventory::addStockLot(
            $activeBranchId,
            $productId,
            $cost,
            $sellingPrice,
            $quantity,
            $lotNumber ?: null,
            'INIT-STOCK-BR' . $activeBranchId,
            $notes ?: 'Manual opening stock lot addition'
        );

        if ($res['success']) {
            $successMsg = $res['message'];

            // Chart of Accounts Double-Entry Journal Posting for Manual Opening Stock Lot
            $totalVal = $quantity * $cost;
            if ($quantity > 0 && $totalVal > 0) {
                try {
                    require_once __DIR__ . '/core/Accounting.php';
                    $invAccId = Accounting::getAccountIdByCode(Accounting::CODE_INVENTORY_ASSET); // 1200
                    $equityAccId = Accounting::getAccountIdByCode(Accounting::CODE_EQUITY); // 3010

                    $prod = Database::fetchOne("SELECT name, sku FROM products WHERE id = :id", [':id' => $productId]);
                    $prodName = $prod['name'] ?? "Product #$productId";
                    $prodSku = $prod['sku'] ?? 'N/A';

                    $jeHeader = [
                        'branch_id'   => $activeBranchId,
                        'entry_date'  => date('Y-m-d'),
                        'source_type' => 'opening_balance',
                        'source_id'   => (int)$res['lot_id'],
                        'description' => "Opening Stock for {$prodName} ({$prodSku}) - Qty: {$quantity} @ LKR " . number_format($cost, 2),
                        'created_by'  => (int)($_SESSION['user_id'] ?? 1)
                    ];

                    $jeLines = [
                        [
                            'account_id' => $invAccId,
                            'debit'      => $totalVal,
                            'credit'     => 0.00,
                            'memo'       => "Opening Stock asset addition for {$prodName}"
                        ],
                        [
                            'account_id' => $equityAccId,
                            'debit'      => 0.00,
                            'credit'     => $totalVal,
                            'memo'       => "Opening Stock equity offset for {$prodName}"
                        ]
                    ];

                    Accounting::postJournalEntry($jeHeader, $jeLines);
                } catch (Throwable $jeEx) {
                    Logger::error("Failed to post opening stock journal entry for lot #" . ($res['lot_id'] ?? 0) . ": " . $jeEx->getMessage(), $jeEx);
                }
            }
        } else {
            $errorMsg = $res['message'];
        }
    }
}

$filters = [
    'search'       => trim($_GET['search'] ?? ''),
    'brand_id'     => (int)($_GET['brand_id'] ?? 0),
    'category_id'  => (int)($_GET['category_id'] ?? 0),
    'stock_status' => trim($_GET['stock_status'] ?? '')
];

$page = max(1, (int)($_GET['page'] ?? 1));
$rawLimit = (int)($_GET['limit'] ?? 25);
$limit = in_array($rawLimit, [25, 50, 75, 100, 500], true) ? $rawLimit : 25;

$result = Inventory::getInventoryList($activeBranchId, $filters, $page, $limit);
$summary = Inventory::getInventorySummary($activeBranchId);

if (Auth::isSpecialUser()) {
    foreach ($result['items'] as &$it) {
        $it['available_quantity'] = MaskingHelper::maskQuantity((float)$it['available_quantity']);
        $it['default_cost']       = MaskingHelper::maskValue((float)$it['default_cost']);
    }
    unset($it);
    $summary['total_sellable_qty']     = MaskingHelper::maskQuantity($summary['total_sellable_qty']);
    $summary['total_cost_valuation']   = MaskingHelper::maskValue($summary['total_cost_valuation']);
    $summary['total_retail_valuation'] = MaskingHelper::maskValue($summary['total_retail_valuation']);
}

$brands = Brand::all(false);
$categories = Category::all(false);
$products = Product::getList([], 1, 1000)['items'];

View::render('inventory/index', [
    'inventory'        => $result['items'],
    'summary'          => $summary,
    'totalItems'       => $result['totalItems'],
    'page'             => $result['page'],
    'limit'            => $limit,
    'totalPages'       => $result['totalPages'],
    'filters'          => $filters,
    'brands'           => $brands,
    'categories'       => $categories,
    'products'         => $products,
    'activeBranchName' => $user['active_branch_name'],
    'successMsg'       => $successMsg,
    'errorMsg'         => $errorMsg
], 'Branch Inventory - Curtiss POS');
