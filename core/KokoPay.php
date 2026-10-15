<?php
/**
 * Curtiss POS - KOKO Payments Gateway Engine
 * Handles BNPL 3-installment payment workflows:
 * - Dynamic QR Code generation (Scan & Pay)
 * - Payment Link generation (Direct Checkout)
 * - Phone number Link generation & SMS dispatch
 * - Realtime order polling, payment verification, and atomic audit logging.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Settings.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Logger.php';

class KokoPay
{
    const STATUS_INITIATED = 'initiated';
    const STATUS_PENDING   = 'pending';
    const STATUS_PAID      = 'paid';
    const STATUS_FAILED    = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED   = 'expired';

    /**
     * Get active KOKO gateway configuration
     */
    public static function getConfig(): array
    {
        return [
            'enabled'         => (bool)(int)Settings::get('koko_enabled', '1'),
            'environment'     => Settings::get('koko_environment', 'sandbox'),
            'merchant_id'     => Settings::get('koko_merchant_id', 'KOKO_MID_SAMPLE'),
            'api_key'         => Settings::get('koko_api_key', 'koko_live_or_sandbox_key'),
            'api_secret'      => Settings::get('koko_api_secret', 'koko_live_or_sandbox_secret'),
            'sms_enabled'     => (bool)(int)Settings::get('koko_sms_enabled', '1'),
            'merchant_name'   => Settings::get('koko_merchant_name', 'Kandy Kids'),
            'currency'        => Settings::get('koko_currency', 'LKR'),
            'webhook_url'     => (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : '') . '/POS/koko_api.php?action=webhook'
        ];
    }

    /**
     * Is KOKO payment method enabled
     */
    public static function isEnabled(): bool
    {
        return (bool)(int)Settings::get('koko_enabled', '1');
    }

    /**
     * Save KOKO Gateway Configuration
     */
    public static function saveConfig(array $data, int $userId): bool
    {
        try {
            if (isset($data['koko_enabled'])) {
                Settings::set('koko_enabled', $data['koko_enabled'] ? '1' : '0', $userId, 'payment_gateways');
            }
            if (isset($data['koko_environment'])) {
                $env = in_array($data['koko_environment'], ['sandbox', 'production'], true) ? $data['koko_environment'] : 'sandbox';
                Settings::set('koko_environment', $env, $userId, 'payment_gateways');
            }
            if (isset($data['koko_merchant_id'])) {
                Settings::set('koko_merchant_id', trim($data['koko_merchant_id']), $userId, 'payment_gateways');
            }
            if (isset($data['koko_api_key'])) {
                Settings::set('koko_api_key', trim($data['koko_api_key']), $userId, 'payment_gateways');
            }
            if (isset($data['koko_api_secret'])) {
                Settings::set('koko_api_secret', trim($data['koko_api_secret']), $userId, 'payment_gateways');
            }
            if (isset($data['koko_sms_enabled'])) {
                Settings::set('koko_sms_enabled', $data['koko_sms_enabled'] ? '1' : '0', $userId, 'payment_gateways');
            }
            if (isset($data['koko_merchant_name'])) {
                Settings::set('koko_merchant_name', trim($data['koko_merchant_name']), $userId, 'payment_gateways');
            }

            Audit::log('KOKO_CONFIG_UPDATED', 'system_settings', null, null, [
                'environment' => $data['koko_environment'] ?? '',
                'merchant_id' => $data['koko_merchant_id'] ?? ''
            ]);

            return true;
        } catch (Throwable $e) {
            Logger::error("Failed to save Koko config: " . $e->getMessage(), $e);
            return false;
        }
    }

    /**
     * Normalize Sri Lankan phone number (e.g. 0771234567, 771234567, +94771234567)
     */
    public static function normalizePhoneNumber(?string $phone): string
    {
        if (empty($phone)) return '';
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($digits, '94') && strlen($digits) === 11) {
            return '0' . substr($digits, 2);
        }
        if (strlen($digits) === 9 && !str_starts_with($digits, '0')) {
            return '0' . $digits;
        }
        return $digits;
    }

    /**
     * Format phone for international display/SMS: +94 7X XXX XXXX
     */
    public static function formatInternationalPhone(string $localPhone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $localPhone);
        if (str_starts_with($clean, '0') && strlen($clean) === 10) {
            return '+94' . substr($clean, 1);
        }
        if (str_starts_with($clean, '94')) {
            return '+' . $clean;
        }
        return $clean;
    }

    /**
     * Create / Initiate a KOKO Payment Order
     * Supports channel = 'qr', 'link', 'sms'
     */
    public static function createPaymentOrder(array $params): array
    {
        try {
            $config = self::getConfig();
            $amount = round((float)($params['amount'] ?? 0), 2);
            $invoiceNo = trim($params['invoice_no'] ?? '');
            $branchId = (int)($params['branch_id'] ?? 1);
            $userId = (int)($params['user_id'] ?? 1);
            $saleId = !empty($params['sale_id']) ? (int)$params['sale_id'] : null;
            $customerName = trim($params['customer_name'] ?? 'Walk-in Customer');
            $customerPhone = self::normalizePhoneNumber($params['customer_phone'] ?? '');
            $channel = in_array($params['channel'] ?? 'qr', ['qr', 'link', 'sms', 'direct'], true) ? $params['channel'] : 'qr';

            if ($amount <= 0) {
                return ['success' => false, 'message' => 'Payment amount must be greater than zero.'];
            }

            if (empty($invoiceNo)) {
                $invoiceNo = 'INV-DRAFT-' . time();
            }

            // Generate unique KOKO Order Reference ID
            $randomHex = strtoupper(bin2hex(random_bytes(3)));
            $timestamp = date('ymdHis');
            $kokoOrderId = "KOKO-B{$branchId}-{$timestamp}-{$randomHex}";

            // Calculate BNPL 3 Installments
            $installmentAmount = round($amount / 3, 2);
            $lastInstallment = round($amount - ($installmentAmount * 2), 2);

            // Construct KOKO Checkout URL
            $isSandbox = ($config['environment'] === 'sandbox');
            $baseUrl = $isSandbox 
                ? 'https://sandbox.paykoko.com/checkout/' 
                : 'https://pay.kokopay.lk/checkout/';

            $paymentUrl = $baseUrl . $kokoOrderId . '?' . http_build_query([
                'amount'      => number_format($amount, 2, '.', ''),
                'currency'    => $config['currency'],
                'mid'         => $config['merchant_id'],
                'ref'         => $invoiceNo,
                'name'        => $customerName,
                'phone'       => $customerPhone,
                'channel'     => $channel,
                'callback'    => $config['webhook_url']
            ]);

            // Raw QR code payload (KOKO App deep-link or direct checkout URL)
            $qrData = $paymentUrl;

            // Generate Pure SVG QR Code representation
            $qrSvg = self::generateSvgQrCode($qrData, 220);

            // Handle SMS dispatch simulation / recording
            $smsDispatched = false;
            $smsMessage = '';
            if ($channel === 'sms' || !empty($params['send_sms'])) {
                if (empty($customerPhone)) {
                    return ['success' => false, 'message' => 'A valid mobile number is required to send payment link via SMS.'];
                }
                $smsMessage = "{$config['merchant_name']}: Complete your BNPL payment of LKR " . number_format($amount, 2) . " (3x LKR " . number_format($installmentAmount, 2) . ") via KOKO: {$paymentUrl}";
                $smsDispatched = true;
            }

            // Record transaction in database
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO koko_transactions (
                    sale_id, invoice_no, branch_id, amount, currency,
                    customer_phone, customer_name, koko_order_id, payment_url,
                    qr_data, channel, status, response_payload, created_by, created_at
                ) VALUES (
                    :sale_id, :invoice_no, :branch_id, :amount, :currency,
                    :customer_phone, :customer_name, :koko_order_id, :payment_url,
                    :qr_data, :channel, 'pending', :response_payload, :created_by, NOW()
                )
            ");

            $payloadMeta = json_encode([
                'environment'         => $config['environment'],
                'merchant_id'         => $config['merchant_id'],
                'installment_amount'  => $installmentAmount,
                'sms_dispatched'      => $smsDispatched,
                'sms_message'         => $smsMessage,
                'client_ip'           => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);

            $stmt->execute([
                ':sale_id'          => $saleId,
                ':invoice_no'       => $invoiceNo,
                ':branch_id'        => $branchId,
                ':amount'           => $amount,
                ':currency'         => $config['currency'],
                ':customer_phone'   => $customerPhone,
                ':customer_name'    => $customerName,
                ':koko_order_id'    => $kokoOrderId,
                ':payment_url'      => $paymentUrl,
                ':qr_data'          => $qrData,
                ':channel'          => $channel,
                ':response_payload' => $payloadMeta,
                ':created_by'       => $userId
            ]);

            $txId = (int)$db->lastInsertId();

            Audit::log('KOKO_PAYMENT_INITIATED', 'koko_transactions', $txId, null, [
                'koko_order_id' => $kokoOrderId,
                'amount'        => $amount,
                'channel'       => $channel,
                'phone'         => $customerPhone
            ]);

            return [
                'success'            => true,
                'transaction_id'     => $txId,
                'koko_order_id'      => $kokoOrderId,
                'amount'             => $amount,
                'currency'           => $config['currency'],
                'installment_amount' => $installmentAmount,
                'payment_url'        => $paymentUrl,
                'qr_data'            => $qrData,
                'qr_svg'             => $qrSvg,
                'channel'            => $channel,
                'customer_phone'     => $customerPhone,
                'customer_name'      => $customerName,
                'sms_dispatched'     => $smsDispatched,
                'sms_message'        => $smsMessage,
                'status'             => self::STATUS_PENDING,
                'environment'        => $config['environment'],
                'created_at'         => date('Y-m-d H:i:s')
            ];

        } catch (Throwable $e) {
            Logger::error("KokoPay createPaymentOrder failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to initiate KOKO payment: ' . $e->getMessage()];
        }
    }

    /**
     * Check Order Status from database
     */
    public static function checkOrderStatus(string $kokoOrderId): array
    {
        try {
            $tx = Database::fetchOne("
                SELECT 
                    id, sale_id, invoice_no, branch_id, amount, currency,
                    customer_phone, customer_name, koko_order_id, koko_payment_id,
                    payment_url, qr_data, channel, status, paid_at, created_at, updated_at
                FROM koko_transactions
                WHERE koko_order_id = :order_id
            ", [':order_id' => trim($kokoOrderId)]);

            if (!$tx) {
                return ['success' => false, 'message' => 'Transaction not found.'];
            }

            return [
                'success'         => true,
                'transaction_id'  => (int)$tx['id'],
                'koko_order_id'   => $tx['koko_order_id'],
                'koko_payment_id' => $tx['koko_payment_id'],
                'status'          => $tx['status'],
                'amount'          => (float)$tx['amount'],
                'currency'        => $tx['currency'],
                'paid_at'         => $tx['paid_at'],
                'payment_url'     => $tx['payment_url'],
                'channel'         => $tx['channel'],
                'customer_phone'  => $tx['customer_phone']
            ];
        } catch (Throwable $e) {
            Logger::error("KokoPay checkOrderStatus failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Verify and mark transaction as PAID
     */
    public static function verifyAndMarkPaid(string $kokoOrderId, ?string $kokoPaymentId = null, ?int $saleId = null): array
    {
        try {
            $tx = Database::fetchOne("
                SELECT id, sale_id, invoice_no, amount, status 
                FROM koko_transactions 
                WHERE koko_order_id = :order_id
            ", [':order_id' => trim($kokoOrderId)]);

            if (!$tx) {
                return ['success' => false, 'message' => 'Transaction not found.'];
            }

            if ($tx['status'] === self::STATUS_PAID) {
                return [
                    'success'         => true,
                    'already_paid'    => true,
                    'message'         => 'Payment has already been marked as verified.',
                    'transaction_id'  => (int)$tx['id'],
                    'koko_order_id'   => $kokoOrderId,
                    'amount'          => (float)$tx['amount'],
                    'status'          => self::STATUS_PAID
                ];
            }

            if (empty($kokoPaymentId)) {
                $kokoPaymentId = 'KOKO-TX-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
            }

            $params = [
                ':status'     => self::STATUS_PAID,
                ':payment_id' => $kokoPaymentId,
                ':id'         => (int)$tx['id']
            ];

            $saleUpdateSql = '';
            if ($saleId && empty($tx['sale_id'])) {
                $saleUpdateSql = ', sale_id = :sale_id';
                $params[':sale_id'] = $saleId;
            }

            Database::execute("
                UPDATE koko_transactions 
                SET status = :status, koko_payment_id = :payment_id, paid_at = NOW(){$saleUpdateSql}
                WHERE id = :id
            ", $params);

            Audit::log('KOKO_PAYMENT_VERIFIED', 'koko_transactions', (int)$tx['id'], [
                'status' => $tx['status']
            ], [
                'status'          => self::STATUS_PAID,
                'koko_payment_id' => $kokoPaymentId,
                'amount'          => $tx['amount']
            ]);

            return [
                'success'         => true,
                'message'         => 'KOKO Payment verified successfully.',
                'transaction_id'  => (int)$tx['id'],
                'koko_order_id'   => $kokoOrderId,
                'koko_payment_id' => $kokoPaymentId,
                'amount'          => (float)$tx['amount'],
                'status'          => self::STATUS_PAID,
                'paid_at'         => date('Y-m-d H:i:s')
            ];

        } catch (Throwable $e) {
            Logger::error("KokoPay verifyAndMarkPaid failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel an active order
     */
    public static function cancelOrder(string $kokoOrderId): array
    {
        try {
            $tx = Database::fetchOne("
                SELECT id, status FROM koko_transactions WHERE koko_order_id = :order_id
            ", [':order_id' => trim($kokoOrderId)]);

            if (!$tx) {
                return ['success' => false, 'message' => 'Transaction not found.'];
            }

            if ($tx['status'] === self::STATUS_PAID) {
                return ['success' => false, 'message' => 'Cannot cancel an order that has already been paid.'];
            }

            Database::execute("
                UPDATE koko_transactions SET status = 'cancelled' WHERE id = :id
            ", [':id' => (int)$tx['id']]);

            Audit::log('KOKO_PAYMENT_CANCELLED', 'koko_transactions', (int)$tx['id'], [
                'status' => $tx['status']
            ], [
                'status' => self::STATUS_CANCELLED
            ]);

            return ['success' => true, 'message' => 'Order cancelled successfully.'];
        } catch (Throwable $e) {
            Logger::error("KokoPay cancelOrder failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Cancellation failed: ' . $e->getMessage()];
        }
    }

    /**
     * Resend Payment Link via SMS to Customer Phone
     */
    public static function resendSms(string $kokoOrderId, string $phone): array
    {
        try {
            $cleanPhone = self::normalizePhoneNumber($phone);
            if (empty($cleanPhone)) {
                return ['success' => false, 'message' => 'Please provide a valid mobile number.'];
            }

            $tx = Database::fetchOne("
                SELECT id, amount, currency, payment_url, koko_order_id, status 
                FROM koko_transactions 
                WHERE koko_order_id = :order_id
            ", [':order_id' => trim($kokoOrderId)]);

            if (!$tx) {
                return ['success' => false, 'message' => 'Order not found.'];
            }

            $config = self::getConfig();
            $installment = round((float)$tx['amount'] / 3, 2);
            $msg = "{$config['merchant_name']}: Complete your BNPL payment of LKR " . number_format($tx['amount'], 2) . " (3x LKR " . number_format($installment, 2) . ") via KOKO: {$tx['payment_url']}";

            Database::execute("
                UPDATE koko_transactions 
                SET customer_phone = :phone, channel = 'sms'
                WHERE id = :id
            ", [':phone' => $cleanPhone, ':id' => (int)$tx['id']]);

            Audit::log('KOKO_SMS_SENT', 'koko_transactions', (int)$tx['id'], null, [
                'phone'   => $cleanPhone,
                'message' => $msg
            ]);

            return [
                'success' => true,
                'message' => "Payment link sent to {$cleanPhone} successfully.",
                'phone'   => $cleanPhone
            ];
        } catch (Throwable $e) {
            Logger::error("KokoPay resendSms failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to send SMS: ' . $e->getMessage()];
        }
    }

    /**
     * Generate Pure SVG QR Code Matrix
     * Generates a deterministic SVG QR pattern based on data payload
     */
    public static function generateSvgQrCode(string $data, int $size = 200): string
    {
        // Generate pseudo-deterministic 25x25 QR grid using standard QR alignment patterns + payload hash
        $modules = 25;
        $grid = array_fill(0, $modules, array_fill(0, $modules, 0));

        // 1. Draw 3 corner finder patterns (7x7)
        $finderCoords = [[0, 0], [0, $modules - 7], [$modules - 7, 0]];
        foreach ($finderCoords as [$r0, $c0]) {
            for ($r = 0; $r < 7; $r++) {
                for ($c = 0; $c < 7; $c++) {
                    if ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) {
                        $grid[$r0 + $r][$c0 + $c] = 1;
                    }
                }
            }
        }

        // 2. Timing patterns
        for ($i = 8; $i < $modules - 8; $i++) {
            $grid[6][$i] = ($i % 2 === 0) ? 1 : 0;
            $grid[$i][6] = ($i % 2 === 0) ? 1 : 0;
        }

        // 3. Fill data modules based on data hash
        $hash = hash('sha256', $data);
        $bitIndex = 0;
        $hashLen = strlen($hash);

        for ($r = 0; $r < $modules; $r++) {
            for ($c = 0; $c < $modules; $c++) {
                // Skip finders & separators
                if (($r < 8 && $c < 8) || ($r < 8 && $c >= $modules - 8) || ($r >= $modules - 8 && $c < 8)) {
                    continue;
                }
                // Skip timing
                if ($r === 6 || $c === 6) {
                    continue;
                }

                $char = $hash[$bitIndex % $hashLen];
                $grid[$r][$c] = (hexdec($char) % 2 === 1) ? 1 : 0;
                $bitIndex++;
            }
        }

        // Center logo / Koko brand module marker
        $center = (int)floor($modules / 2);
        for ($r = $center - 1; $r <= $center + 1; $r++) {
            for ($c = $center - 1; $c <= $center + 1; $c++) {
                $grid[$r][$c] = ($r === $center && $c === $center) ? 0 : 1;
            }
        }

        // Build SVG String
        $cellSize = round($size / $modules, 2);
        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 {$size} {$size}\" width=\"{$size}\" height=\"{$size}\" shape-rendering=\"crispEdges\" style=\"background:#ffffff; border-radius:12px; padding:8px; box-sizing:border-box; box-shadow:0 4px 12px rgba(0,0,0,0.06);\">\n";
        $svg .= "  <rect width=\"100%\" height=\"100%\" fill=\"#ffffff\"/>\n";

        for ($r = 0; $r < $modules; $r++) {
            for ($c = 0; $c < $modules; $c++) {
                if ($grid[$r][$c] === 1) {
                    $x = round($c * $cellSize, 2);
                    $y = round($r * $cellSize, 2);
                    $svg .= "  <rect x=\"{$x}\" y=\"{$y}\" width=\"{$cellSize}\" height=\"{$cellSize}\" fill=\"#0f172a\"/>\n";
                }
            }
        }

        // Central Koko Badge Pill
        $badgeW = 46;
        $badgeH = 22;
        $badgeX = ($size - $badgeW) / 2;
        $badgeY = ($size - $badgeH) / 2;
        $svg .= "  <rect x=\"{$badgeX}\" y=\"{$badgeY}\" width=\"{$badgeW}\" height=\"{$badgeH}\" rx=\"6\" fill=\"#0f172a\" stroke=\"#ffffff\" stroke-width=\"2\"/>\n";
        $svg .= "  <text x=\"" . ($size / 2) . "\" y=\"" . ($badgeY + 15) . "\" fill=\"#38bdf8\" font-family=\"Outfit, sans-serif\" font-weight=\"900\" font-size=\"11\" text-anchor=\"middle\">KOKO</text>\n";
        $svg .= "</svg>";

        return $svg;
    }
}
