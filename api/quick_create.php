<?php
/**
 * Curtiss POS - Quick Create API Endpoint
 * Handles instant creation of Brands, Categories, and Suppliers from forms
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Brand.php';
require_once __DIR__ . '/../core/Category.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Customer.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER) && !Auth::hasRole(ROLE_CASHIER) && !Auth::hasPermission('pos.access') && !Auth::hasPermission('view_petty_cash')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$type = trim($input['type'] ?? '');
$name = trim($input['name'] ?? '');

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Name field is required.']);
    exit();
}

if ($type === 'brand') {
    $res = Brand::create($name, 'active');
    if ($res['success']) {
        echo json_encode(['success' => true, 'id' => $res['id'], 'name' => $name, 'message' => 'Brand created successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => $res['message']]);
    }
    exit();
}

if ($type === 'category') {
    $res = Category::create($name, 'active');
    if ($res['success']) {
        echo json_encode(['success' => true, 'id' => $res['id'], 'name' => $name, 'message' => 'Category created successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => $res['message']]);
    }
    exit();
}

if ($type === 'expense_category') {
    require_once __DIR__ . '/../core/PettyCash.php';
    require_once __DIR__ . '/../core/Accounting.php';

    $defaultAccId = (int)(Accounting::getAccountIdByCode(Accounting::CODE_EXPENSES) ?? 17);
    $autoCode = 'EXP-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 4)) . rand(100, 999);

    $userId = (int)(Auth::user()['id'] ?? 1);
    $res = PettyCash::saveCategory([
        'name'        => $name,
        'code'        => $autoCode,
        'account_id'  => $defaultAccId,
        'description' => 'Quick-created from Expense Voucher',
        'status'      => 'active'
    ], $userId);

    if ($res['success']) {
        $accInfo = Database::fetchOne("SELECT account_code, account_name FROM chart_of_accounts WHERE id = :id", [':id' => $defaultAccId]);
        $displayName = $name . ' (' . ($accInfo['account_code'] ?? '5100') . ' - ' . ($accInfo['account_name'] ?? 'General Operating Expenses') . ')';
        echo json_encode([
            'success' => true,
            'id'      => $res['id'],
            'name'    => $displayName,
            'message' => "Expense category '{$name}' created successfully."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $res['message']]);
    }
    exit();
}

if ($type === 'supplier') {
    $phone = trim($input['phone'] ?? '');
    $res = Supplier::create($name, $phone, null, null, 'active');
    if ($res['success']) {
        echo json_encode(['success' => true, 'id' => $res['id'], 'name' => $name, 'message' => 'Supplier created successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => $res['message']]);
    }
    exit();
}

if ($type === 'customer') {
    $phone = trim($input['phone'] ?? '');
    $whatsapp = trim($input['whatsapp'] ?? '');
    $cleanPhone = !empty($phone) ? substr(preg_replace('/[^0-9]/', '', $phone), 0, 10) : null;
    $cleanWhatsapp = !empty($whatsapp) ? substr(preg_replace('/[^0-9]/', '', $whatsapp), 0, 10) : null;

    $res = Customer::create([
        'name'         => $name,
        'phone'        => $cleanPhone,
        'whatsapp'     => $cleanWhatsapp,
        'credit_limit' => 0.00,
        'status'       => 'active'
    ]);
    if ($res['success']) {
        $customer = Customer::find((int)$res['id']);
        echo json_encode([
            'success'  => true,
            'id'       => $res['id'],
            'name'     => $name,
            'customer' => $customer,
            'message'  => "Customer '{$name}' created successfully."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $res['message']]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid entity type requested.']);
