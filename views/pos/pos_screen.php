<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($businessName) ?> - Modern POS Billing Terminal</title>
    <?php
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $posDir = preg_replace('/\/[^\/]+\.php$/', '', $scriptName);
    if (!str_ends_with($posDir, '/')) {
        $posDir .= '/';
    }
    $baseUrl = $protocol . $host . $posDir;
    $companyLogo = $companyLogo ?? Settings::get('company_logo', '');
    ?>
    <base href="<?= htmlspecialchars($baseUrl) ?>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/pos_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --pos-bg: #f8fafc;
            --pos-card-bg: rgba(255, 255, 255, 0.94);
            --pos-border: #e2e8f0;
            --pos-border-subtle: #f1f5f9;
            --pos-text: #0f172a;
            --pos-text-muted: #64748b;
            --pos-primary: #0f172a;
            --pos-primary-hover: #1e293b;
            --pos-primary-light: #f1f5f9;
            --pos-success: #16a34a;
            --pos-success-hover: #15803d;
            --pos-warning: #d97706;
            --pos-danger: #dc2626;
            --pos-accent: #0f172a;
        }

        *, *::before, *::after { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            transition-duration: 0s !important;
            animation-duration: 0.001s !important;
        }
        .fa-spin {
            animation: fa-spin 1s infinite linear !important;
        }
        button, a, input, select, .btn-qty, .btn-pos-pay-hero, .pos-side-btn, .cust-item-card, .lot-card {
            touch-action: manipulation;
        }
        body, html { 
            height: 100%; 
            width: 100%; 
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--pos-bg);
            color: var(--pos-text);
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            font-variant-numeric: tabular-nums;
        }

        .pos-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            width: 100vw;
            background-color: #f8fafc;
        }

        /* ── Top Header - Sleek Obsidian Enterprise Theme ── */
        .pos-header {
            background: #090d16;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 0 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 54px;
            min-height: 54px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
            z-index: 20;
            color: #ffffff;
        }

        .header-brand-lockup {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-curtiss-logo {
            height: 24px;
            width: auto;
            object-fit: contain;
            display: block;
            vertical-align: middle;
        }

        .header-collab-x {
            color: #475569;
            font-size: 0.8rem;
            font-weight: 800;
            line-height: 1;
            user-select: none;
            opacity: 0.65;
        }

        .header-company-logo {
            height: 28px;
            max-width: 160px;
            width: auto;
            object-fit: contain;
            display: block;
            vertical-align: middle;
        }

        .header-company-name {
            font-family: 'Outfit', -apple-system, sans-serif;
            font-size: 1.05rem;
            font-weight: 800;
            color: #f8fafc;
            letter-spacing: -0.01em;
        }

        .pos-info-bar {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-size: 0.82rem;
        }

        .header-divider {
            width: 1px;
            height: 18px;
            background: rgba(255, 255, 255, 0.12);
            margin: 0 2px;
        }


        .header-ghost-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            padding: 5px 8px;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .header-ghost-btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.06);
        }

        .header-pending-pill {
            background: #f59e0b;
            color: #ffffff;
            border-radius: 10px;
            padding: 0.05rem 0.4rem;
            font-size: 0.7rem;
            font-weight: 800;
        }

        .header-returns-btn {
            background: rgba(244, 63, 94, 0.12);
            border: 1px solid rgba(244, 63, 94, 0.28);
            color: #fda4af;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            cursor: pointer;
            padding: 4px 9px;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .header-returns-btn:hover {
            background: rgba(244, 63, 94, 0.22);
            color: #ffffff;
            border-color: rgba(244, 63, 94, 0.45);
        }

        .header-shortcut-key {
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(244, 63, 94, 0.3);
            color: #fecdd3;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 1px 5px;
            border-radius: 4px;
            font-family: monospace;
        }

        .header-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.8rem;
            color: #94a3b8;
            font-weight: 600;
            padding: 4px 6px;
            border-radius: 6px;
            user-select: none;
        }

        .header-meta-item span {
            color: #e2e8f0;
            font-weight: 700;
        }

        .header-meta-item i {
            color: #64748b;
            font-size: 0.82rem;
        }

        .header-clock {
            font-family: 'JetBrains Mono', 'SF Mono', monospace;
            font-size: 0.78rem;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.02em;
            padding: 0 4px;
        }

        .header-exit-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
            padding: 5px 11px;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .header-exit-btn:hover {
            background: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        /* ── POS Workspace Layout ── */
        .pos-body {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        /* ── POS Quick Action Left Sidebar ── */
        .pos-sidebar {
            width: 88px;
            min-width: 88px;
            max-width: 88px;
            background: #ffffff;
            border-right: 1px solid var(--pos-border);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 0.75rem 0.5rem;
            gap: 0.65rem;
            z-index: 105;
            flex-shrink: 0;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.02);
        }

        .pos-sidebar-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.65rem;
            width: 100%;
        }

        .pos-side-btn {
            width: 72px;
            height: 72px;
            border-radius: 14px;
            border: 1.5px solid transparent;
            background: #ffffff;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 5px 3px;
            transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            user-select: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            font-family: inherit;
            position: relative;
        }

        .pos-side-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.09);
        }

        .pos-side-btn:active {
            transform: translateY(0);
        }

        .pos-side-icon-box {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            transition: all 0.18s ease;
        }

        .pos-side-label {
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: -0.01em;
            text-align: center;
            line-height: 1.12;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .pos-side-label small {
            font-size: 8.5px;
            font-weight: 700;
            opacity: 0.85;
            margin-top: 1px;
            font-family: monospace;
        }

        /* Side Button 0: Returns & Exchanges (F1) - Emerald Teal Luxury */
        .btn-side-returns {
            background: linear-gradient(145deg, #f0fdf4, #dcfce7);
            border-color: #bbf7d0;
            color: #166534;
        }
        .btn-side-returns .pos-side-icon-box {
            background: rgba(22, 163, 74, 0.18);
            color: #16a34a;
        }
        .btn-side-returns:hover {
            background: #bbf7d0;
            border-color: #16a34a;
            box-shadow: 0 8px 18px rgba(22, 163, 74, 0.28);
        }

        /* Side Button 1: Hold Invoice (F6) - Warm Amber Gold */
        .btn-side-hold {
            background: linear-gradient(145deg, #fffdf5, #fef3c7);
            border-color: #fde68a;
            color: #92400e;
        }
        .btn-side-hold .pos-side-icon-box {
            background: rgba(245, 158, 11, 0.18);
            color: #d97706;
        }
        .btn-side-hold:hover {
            background: #fde68a;
            border-color: #f59e0b;
            box-shadow: 0 8px 18px rgba(245, 158, 11, 0.28);
        }

        /* Side Button 2: Held List (F7) - Royal Indigo / Purple */
        .btn-side-held-list {
            background: linear-gradient(145deg, #faf5ff, #ede9fe);
            border-color: #ddd6fe;
            color: #5b21b6;
        }
        .btn-side-held-list .pos-side-icon-box {
            background: rgba(124, 58, 237, 0.18);
            color: #7c3aed;
        }
        .btn-side-held-list:hover {
            background: #ddd6fe;
            border-color: #7c3aed;
            box-shadow: 0 8px 18px rgba(124, 58, 237, 0.28);
        }

        /* Side Button: Sales History - Ocean Blue Luxury */
        .btn-side-history {
            background: linear-gradient(145deg, #f0f9ff, #e0f2fe);
            border-color: #bae6fd;
            color: #0369a1;
        }
        .btn-side-history .pos-side-icon-box {
            background: rgba(2, 132, 199, 0.18);
            color: #0284c7;
        }
        .btn-side-history:hover {
            background: #bae6fd;
            border-color: #0284c7;
            box-shadow: 0 8px 18px rgba(2, 132, 199, 0.28);
        }

        /* Sales History Modal & Table Styles */
        .hist-preset-btn {
            height: 32px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1.5px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .hist-preset-btn:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }
        .hist-preset-btn.active {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.2);
        }
        .sales-hist-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.65); }
        .sales-hist-table tbody tr:nth-child(odd)  { background: #ffffff; }
        .sales-hist-table tbody tr:hover            { background: #f1f5f9 !important; }
        .sales-hist-table tbody tr { height: 48px; min-height: 48px; cursor: pointer; }
        .sales-hist-table tbody td { height: 48px; padding: 0 14px; font-size: 12.5px; color: #1e293b; vertical-align: middle; box-sizing: border-box; }
        .btn-hist-print {
            height: 30px;
            padding: 0 12px;
            border-radius: 8px;
            border: 1.5px solid #86efac;
            background: #f0fdf4;
            color: #166534;
            font-size: 0.78rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease;
        }
        .btn-hist-print:hover {
            background: #16a34a;
            border-color: #16a34a;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(22, 163, 74, 0.25);
        }
        .btn-hist-view {
            height: 30px;
            padding: 0 12px;
            border-radius: 8px;
            border: 1.5px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease;
        }
        .btn-hist-view:hover {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.18);
        }

        /* Side Button 3: Clear Sale (F8) - Cool Sky Slate */
        .btn-side-clear {
            background: linear-gradient(145deg, #f0fdfa, #e0f2fe);
            border-color: #bae6fd;
            color: #0369a1;
        }
        .btn-side-clear .pos-side-icon-box {
            background: rgba(2, 132, 199, 0.18);
            color: #0284c7;
        }
        .btn-side-clear:hover {
            background: #bae6fd;
            border-color: #0284c7;
            box-shadow: 0 8px 18px rgba(2, 132, 199, 0.28);
        }

        /* Side Button 4: Cancel Sale (F9) - Coral Crimson */
        .btn-side-cancel {
            background: linear-gradient(145deg, #fff1f2, #fee2e2);
            border-color: #fecaca;
            color: #9f1239;
        }
        .btn-side-cancel .pos-side-icon-box {
            background: rgba(225, 29, 72, 0.18);
            color: #e11d48;
        }
        .btn-side-cancel:hover {
            background: #fecaca;
            border-color: #e11d48;
            box-shadow: 0 8px 18px rgba(225, 29, 72, 0.28);
        }

        .pos-left {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--pos-border);
            background-color: #f8fafc;
            padding: 0.75rem;
            gap: 0.75rem;
            position: relative;
            z-index: 100;
        }

        .pos-right {
            width: clamp(340px, 28vw, 410px);
            min-width: 320px;
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
            padding: 0.75rem;
            gap: 0.65rem;
            border-left: 1px solid var(--pos-border);
            z-index: 10;
            overflow-y: auto;
        }

        /* ── Search Barcode Input & Dropdown ── */
        .search-box-container {
            position: relative;
            z-index: 9999;
        }

        .search-input {
            width: 100%;
            background-color: #ffffff;
            border: 1.5px solid #cbd5e1;
            color: var(--pos-text);
            padding: 0.85rem 1rem 0.85rem 3.2rem;
            font-size: 1.05rem;
            font-weight: 700;
            border-radius: 14px;
            outline: none;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
            transition: all 0.18s ease;
        }

        .search-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .search-icon {
            position: absolute;
            left: 1.15rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1.25rem;
        }

        .search-dropdown {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 16px;
            max-height: 420px;
            overflow-y: auto;
            z-index: 99999;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.15);
            display: none;
        }

        .search-item {
            padding: 0.85rem 1.15rem;
            border-bottom: 1px solid var(--pos-border-subtle);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-item:hover, .search-item.selected {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            padding-left: calc(1.15rem - 3px);
        }

        .search-item.selected {
            font-weight: 800;
        }

        /* ── Cart Container & Clean Table ── */
        .cart-container {
            flex: 1;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--pos-border);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            position: relative;
            z-index: 1;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        .cart-table th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.07em;
            text-align: left;
            padding: 0.85rem 0.8rem;
            position: sticky;
            top: 0;
            z-index: 5;
            border-bottom: 1px solid var(--pos-border);
        }

        .cart-table td {
            padding: 0.75rem 0.8rem;
            border-bottom: 1px solid var(--pos-border-subtle);
            vertical-align: middle;
        }

        .cart-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
        .cart-table tbody tr:nth-child(odd)  { background: #ffffff; }
        .cart-table tbody tr:hover            { background: #f1f5f9 !important; }

        .held-table tbody tr:nth-child(even), .customer-table tbody tr:nth-child(even), .sales-rep-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
        .held-table tbody tr:nth-child(odd), .customer-table tbody tr:nth-child(odd), .sales-rep-table tbody tr:nth-child(odd)  { background: #ffffff; }
        .held-table tbody tr:hover, .customer-table tbody tr:hover, .sales-rep-table tbody tr:hover            { background: #f1f5f9 !important; }

        .qty-controls {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-qty {
            background: #f1f5f9;
            color: #0f172a;
            border: 1.5px solid #cbd5e1;
            width: 28px;
            height: 28px;
            border-radius: 7px;
            font-weight: 800;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
            user-select: none;
        }

        .btn-qty:hover { 
            background: #0f172a; 
            color: #ffffff;
            border-color: #0f172a;
        }

        .qty-input {
            width: 52px;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            color: #0f172a;
            text-align: center;
            padding: 0.25rem 0.3rem;
            border-radius: 7px;
            font-weight: 800;
            font-size: 0.9rem;
            font-family: 'Outfit', -apple-system, sans-serif;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .qty-input:focus {
            border-color: #0f172a;
            outline: none;
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
        }

        .cart-avail-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 1px 7px;
            border-radius: 999px;
            line-height: 1.35;
            white-space: nowrap;
            margin-top: 3px;
        }

        .cart-avail-badge .avail-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #10b981;
            display: inline-block;
        }

        .price-override-input, .item-discount-input {
            width: 86px;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            color: #0f172a;
            font-family: 'Outfit', -apple-system, sans-serif;
            font-weight: 700;
            text-align: right;
            padding: 0.32rem 0.5rem;
            border-radius: 7px;
            font-size: 0.88rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .price-override-input:focus, .item-discount-input:focus {
            border-color: #0f172a;
            outline: none;
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
        }

        .price-override-input:disabled, .item-discount-input:disabled {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #64748b;
            cursor: not-allowed;
        }

        .lot-badge {
            background: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            font-size: 0.75rem;
            padding: 0.25rem 0.55rem;
            border-radius: 9999px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-weight: 700;
            transition: all 0.15s ease;
        }

        .lot-badge:hover {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }

        .lot-badge-inline {
            background: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            font-size: 0.72rem;
            padding: 0.18rem 0.55rem;
            border-radius: 9999px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-weight: 700;
            transition: all 0.15s ease;
        }

        .lot-badge-inline:hover {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }

        .cart-barcode-pill {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e2e8f0;
            font-size: 0.72rem;
            font-family: monospace;
            padding: 0.18rem 0.5rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-weight: 700;
        }

        .cart-row-idx {
            font-weight: 700;
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .btn-cart-delete {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #ef4444;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.88rem;
            transition: all 0.15s ease;
        }

        .btn-cart-delete:hover {
            background: #ef4444;
            color: #ffffff;
            border-color: #ef4444;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.25);
        }

        /* ── Right Panel Dark Luxury Cards System ── */
        /* 1. TOP: Total Payable Card - Dark Emerald Green Luxury */
        .pos-payable-hero-card {
            background: linear-gradient(135deg, #042614 0%, #083c21 50%, #0d4a2b 100%);
            border: 1.5px solid rgba(52, 211, 153, 0.35);
            border-radius: 18px;
            padding: 1rem 1.25rem;
            box-shadow: 0 14px 30px -8px rgba(4, 38, 20, 0.6), 0 0 0 1px rgba(16, 185, 129, 0.15) inset;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            position: relative;
            overflow: hidden;
        }
        .pos-payable-hero-card::after {
            content: '';
            position: absolute;
            top: -40%;
            right: -25%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.2) 0%, transparent 70%);
            pointer-events: none;
        }
        .payable-hero-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .payable-hero-badge {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6ee7b7;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }
        .payable-hero-badge i {
            color: #34d399;
            font-size: 0.85rem;
        }
        .payable-hero-amount {
            font-family: 'Outfit', 'SF Pro Display', sans-serif;
            font-size: 2.75rem;
            font-weight: 900;
            color: #ffffffff;
            letter-spacing: -0.025em;
            line-height: 1.1;
            text-shadow: 0 4px 25px rgba(52, 211, 153, 0.45);
            margin: 0.15rem 0;
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }
        .payable-hero-savings {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(52, 211, 153, 0.35);
            color: #a7f3d0;
            font-size: 0.75rem;
            font-weight: 800;
            border-radius: 9999px;
            padding: 0.2rem 0.65rem;
            width: fit-content;
            margin-top: 2px;
        }

        /* 2. Total Discount Card - Dark Luxury Rose / Ruby Card */
        .pos-discount-card {
            background: linear-gradient(135deg, #1f0712 0%, #350c1e 50%, #4d102c 100%);
            border: 1.5px solid rgba(244, 63, 94, 0.35);
            border-radius: 18px;
            padding: 0.9rem 1.25rem;
            box-shadow: 0 12px 26px -6px rgba(31, 7, 18, 0.55), 0 0 0 1px rgba(244, 63, 94, 0.15) inset;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            position: relative;
            overflow: hidden;
        }
        .pos-discount-card::after {
            content: '';
            position: absolute;
            top: -40%;
            right: -25%;
            width: 160px;
            height: 160px;
            background: radial-gradient(circle, rgba(244, 63, 94, 0.2) 0%, transparent 70%);
            pointer-events: none;
        }
        .discount-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .discount-card-badge {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #fda4af;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }
        .discount-card-badge i {
            color: #fb7185;
            font-size: 0.85rem;
        }
        .discount-card-pill {
            font-size: 0.68rem;
            color: #fecdd3;
            font-weight: 900;
            background: rgba(244, 63, 94, 0.22);
            border: 1px solid rgba(251, 113, 133, 0.35);
            padding: 2px 7.5px;
            border-radius: 999px;
            letter-spacing: 0.04em;
        }
        .discount-card-amount {
            font-family: 'Outfit', 'SF Pro Display', sans-serif;
            font-size: 2.15rem;
            font-weight: 900;
            color: #fb7185;
            letter-spacing: -0.02em;
            line-height: 1.15;
            text-shadow: 0 2px 18px rgba(244, 63, 94, 0.45);
            margin: 0.1rem 0 0 0;
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }

        /* 3. Order Breakdown Card - Deep Midnight Luxury */
        .pos-breakdown-card {
            background: linear-gradient(135deg, #0b1320 0%, #111d33 100%);
            border: 1.5px solid rgba(255, 255, 255, 0.09);
            border-radius: 16px;
            padding: 0.85rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            box-shadow: 0 8px 22px -6px rgba(0, 0, 0, 0.4);
            color: #f8fafc;
        }
        .breakdown-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 0.4rem;
            margin-bottom: 0.15rem;
        }
        .breakdown-card-header .breakdown-title {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }
        .pos-breakdown-card .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.88rem;
            color: #cbd5e1;
        }
        .pos-breakdown-card .summary-row span:last-child {
            color: #f8fafc;
        }
        .pos-breakdown-card .item-discount-input {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-radius: 8px;
            padding: 0.3rem 0.5rem;
        }
        .pos-breakdown-card .item-discount-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
            outline: none;
        }
        .pos-breakdown-card .btn-qty {
            background: #1e293b;
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .pos-breakdown-card .btn-qty:hover {
            background: #3b82f6;
            color: #ffffff;
            border-color: #3b82f6;
        }

        /* 3. Customer Selection Card - Dark Blue Luxury */
        .customer-card-blue {
            background: linear-gradient(135deg, #071529 0%, #0d2344 100%);
            border: 1.5px solid rgba(56, 189, 248, 0.28);
            border-radius: 16px;
            padding: 0.75rem 0.95rem;
            box-shadow: 0 8px 22px -6px rgba(7, 21, 41, 0.5);
            cursor: pointer;
            transition: all 0.2s ease;
            color: #ffffff;
        }
        .customer-card-blue:hover {
            border-color: #38bdf8;
            box-shadow: 0 10px 25px -4px rgba(56, 189, 248, 0.35);
            transform: translateY(-1px);
        }
        .card-header-clean {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.45rem;
        }
        .card-header-clean .card-tag {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .btn-clean-action {
            border: none;
            padding: 0.28rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
            user-select: none;
        }
        .btn-clean-blue {
            background: rgba(56, 189, 248, 0.18);
            color: #7dd3fc !important;
            border: 1px solid rgba(56, 189, 248, 0.35);
        }
        .btn-clean-blue:hover {
            background: #0284c7;
            color: #ffffff !important;
            border-color: #0284c7;
        }
        .card-body-clean {
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        .card-avatar-box {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .avatar-blue {
            background: rgba(56, 189, 248, 0.16);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.25);
        }
        .card-chevron-clean {
            color: #64748b;
            font-size: 0.8rem;
            transition: transform 0.15s ease;
        }
        .customer-card-blue:hover .card-chevron-clean {
            color: #38bdf8;
            transform: translateX(2px);
        }

        /* 4. Sales Rep Selection Card - Cherry Red Luxury */
        .salesrep-card-cherry {
            background: linear-gradient(135deg, #270611 0%, #400b1d 100%);
            border: 1.5px solid rgba(251, 113, 133, 0.28);
            border-radius: 16px;
            padding: 0.75rem 0.95rem;
            box-shadow: 0 8px 22px -6px rgba(39, 6, 17, 0.5);
            cursor: pointer;
            transition: all 0.2s ease;
            color: #ffffff;
        }
        .salesrep-card-cherry:hover {
            border-color: #fb7185;
            box-shadow: 0 10px 25px -4px rgba(251, 113, 133, 0.35);
            transform: translateY(-1px);
        }
        .btn-clean-cherry {
            background: rgba(251, 113, 133, 0.18);
            color: #fca5a5 !important;
            border: 1px solid rgba(251, 113, 133, 0.35);
        }
        .btn-clean-cherry:hover {
            background: #e11d48;
            color: #ffffff !important;
            border-color: #e11d48;
        }
        .avatar-cherry {
            background: rgba(251, 113, 133, 0.16);
            color: #fb7185;
            border: 1px solid rgba(251, 113, 133, 0.25);
        }
        .salesrep-card-cherry:hover .card-chevron-clean {
            color: #fb7185;
            transform: translateX(2px);
        }

        /* Fallback classes for legacy/compatibility */
        .customer-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid var(--pos-border);
            border-radius: 16px;
            padding: 0.95rem;
            cursor: pointer;
        }
        .summary-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid var(--pos-border);
            border-radius: 16px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
        }
        .summary-row-total {
            border-top: 2px dashed var(--pos-border);
            padding-top: 0.85rem;
            font-family: 'SF Pro Display', sans-serif;
            font-size: 1.85rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }

        .roundup-pill {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            font-size: 0.8rem;
            padding: 0.45rem 0.85rem;
            border-radius: 9999px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            margin-top: 0.2rem;
            transition: all 0.18s ease;
            font-weight: 700;
        }

        .roundup-pill:hover { 
            background: #dbeafe; 
            border-color: #93c5fd; 
        }

        .action-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem;
            margin-top: auto;
        }

        .btn-pos-action {
            padding: 0.85rem;
            border-radius: 12px;
            border: 1px solid transparent;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            font-size: 0.85rem;
            transition: all 0.18s ease;
            font-family: inherit;
        }

        .btn-pos-action:hover {
            transform: translateY(-1px);
        }

        .btn-pos-hold { 
            background: #f1f5f9; 
            color: #334155; 
            border-color: #cbd5e1;
        }
        .btn-pos-hold:hover { 
            background: #e2e8f0; 
            color: #0f172a; 
        }

        .btn-pos-held-list { 
            background: #f1f5f9; 
            color: #0f172a; 
            border-color: #cbd5e1;
        }
        .btn-pos-held-list:hover { 
            background: #e2e8f0; 
        }

        .btn-pos-clear { 
            background: #fef2f2; 
            color: #dc2626; 
            border-color: #fca5a5;
        }
        .btn-pos-clear:hover { 
            background: #fee2e2; 
        }

        .btn-pos-cancel { 
            background: #ffffff; 
            border: 1px solid #cbd5e1; 
            color: #475569; 
        }
        .btn-pos-cancel:hover { 
            background: #f1f5f9; 
            color: #0f172a; 
        }

        .btn-pos-pay {
            grid-column: span 2;
            background: #0f172a;
            color: #ffffff;
            padding: 1.15rem;
            font-family: 'SF Pro Display', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.25);
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            transition: all 0.18s ease;
            border: none;
        }
        .btn-pos-pay:hover { 
            background: #1e293b;
            box-shadow: 0 6px 22px rgba(15, 23, 42, 0.35);
            transform: translateY(-1px);
        }

        /* ── Modern Hero Payable Amount Card ── */
        .summary-payable-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 14px;
            padding: 0.85rem 1.15rem;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.15);
            margin-top: 4px;
        }
        .summary-payable-hero .hero-label {
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #94a3b8;
        }
        .summary-payable-hero .hero-amount {
            font-family: 'Outfit', 'SF Pro Display', sans-serif;
            font-size: 1.85rem;
            font-weight: 900;
            color: #ffffffff;
            letter-spacing: -0.02em;
            text-shadow: 0 0 20px rgba(52, 211, 153, 0.25);
        }

        /* ── Modern Checkout Hero Action Button ── */
        .checkout-action-area {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .btn-pos-pay-hero {
            width: 100%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 0.95rem 1.25rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }

        .btn-pos-pay-hero:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.45);
        }

        .btn-pos-pay-hero:active {
            transform: translateY(0);
        }

        .pay-hero-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pay-hero-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .pay-hero-text {
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        .pay-hero-title {
            font-family: 'Outfit', 'SF Pro Display', sans-serif;
            font-size: 1.25rem;
            font-weight: 900;
            letter-spacing: 0.02em;
            line-height: 1.1;
        }

        .pay-hero-sub {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            opacity: 0.9;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .pay-hero-arrow {
            font-size: 1.15rem;
            opacity: 0.8;
            transition: transform 0.18s ease;
        }

        .btn-pos-pay-hero:hover .pay-hero-arrow {
            transform: translateX(4px);
            opacity: 1;
        }

        /* ── Dark Minimalist Shortcut Bar ── */
        .shortcut-bar {
            background-color: #0f172a;
            color: #94a3b8;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1.25rem;
            display: flex;
            gap: 1.25rem;
            font-size: 0.78rem;
            overflow-x: auto;
            z-index: 10;
        }

        .sc-tag {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            padding: 0.15rem 0.45rem;
            border-radius: 6px;
            font-weight: 700;
            font-family: monospace;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* ── Comprehensive Responsive Screen-Fit Rules ── */
        @media (max-height: 800px) {
            .pos-header { height: 50px; min-height: 50px; padding: 0 1rem; }
            .header-curtiss-logo { height: 20px; }
            .header-company-logo { height: 24px; }
            .pos-sidebar { width: 78px; min-width: 78px; max-width: 78px; padding: 0.5rem 0.35rem; gap: 0.5rem; }
            .pos-side-btn { width: 64px; height: 64px; border-radius: 11px; }
            .pos-side-icon-box { width: 28px; height: 28px; font-size: 1rem; }
            .pos-side-label { font-size: 9.5px; }
            .pos-left { padding: 0.5rem; gap: 0.5rem; }
            .pos-right { padding: 0.5rem; gap: 0.5rem; }
            .customer-card { padding: 0.7rem; }
            .summary-card { padding: 0.75rem; gap: 0.5rem; }
            .summary-row { font-size: 0.88rem; }
            .summary-payable-hero { padding: 0.65rem 0.95rem; }
            .summary-payable-hero .hero-amount { font-size: 1.55rem; }
            .btn-pos-pay-hero { padding: 0.75rem 1rem; }
            .pay-hero-title { font-size: 1.1rem; }
            .cart-table th { padding: 0.6rem 0.7rem; }
            .cart-table td { padding: 0.5rem 0.7rem; }
            .shortcut-bar { padding: 0.35rem 1rem; font-size: 0.74rem; }
        }

        @media (max-width: 1024px) {
            .pos-sidebar { width: 78px; min-width: 78px; max-width: 78px; }
            .pos-side-btn { width: 64px; height: 64px; }
            .pos-right { width: 320px; min-width: 300px; }
            .pay-hero-sub { display: none; }
        }

        @media (max-width: 768px) {
            .pos-container { height: 100vh; overflow-y: auto; }
            .pos-header { flex-wrap: wrap; height: auto; padding: 0.65rem; gap: 0.5rem; }
            .pos-body { flex-direction: column; overflow-y: visible; flex: none; }
            .pos-sidebar {
                width: 100%;
                min-width: 100%;
                max-width: 100%;
                height: auto;
                border-right: none;
                border-bottom: 1px solid var(--pos-border);
                flex-direction: row;
                justify-content: center;
                padding: 0.65rem;
                overflow-x: auto;
            }
            .pos-sidebar-actions {
                flex-direction: row;
                justify-content: center;
                gap: 0.65rem;
                width: auto;
            }
            .pos-side-btn { width: 68px; height: 64px; }
            .pos-left { border-right: none; border-bottom: 1px solid var(--pos-border); min-height: 420px; }
            .pos-right { width: 100%; min-width: 100%; border-left: none; }
            .shortcut-bar { display: none; }
        }

        /* ── High-Performance Fast Modals & Dialogs ── */
        .pos-modal {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100000;
            opacity: 1;
            pointer-events: auto;
        }

        .pos-modal.active {
            display: flex;
        }

        .pos-modal-content {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 20px;
            width: 92%;
            max-width: 630px;
            max-height: 88vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            color: var(--pos-text);
            transform: none !important;
            transition: none !important;
        }

        .pos-modal.active .pos-modal-content { transform: none !important; }

        .pos-modal-header {
            padding: 1.15rem 1.4rem;
            border-bottom: 1px solid var(--pos-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'SF Pro Display', sans-serif;
            font-weight: 800;
            font-size: 1.15rem;
            color: #0f172a;
        }

        .pos-modal-body {
            padding: 1.4rem;
            overflow-y: auto;
            flex: 1;
        }

        .pos-modal-footer {
            padding: 1rem 1.4rem;
            border-top: 1px solid var(--pos-border);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            background: #f8fafc;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .lot-card {
            background: #f8fafc;
            border: 1px solid var(--pos-border);
            border-radius: 12px;
            padding: 0.9rem 1.15rem;
            margin-bottom: 0.65rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.15s ease;
        }

        .lot-card:hover, .lot-card.selected {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }

        /* ── Modern Modal UI: Customer & Sales Rep Selectors ── */
        .modal-shell-clean {
            max-width: 650px !important;
            padding: 0 !important;
            border-radius: 20px !important;
            overflow: hidden !important;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.28) !important;
            border: 1px solid rgba(226, 232, 240, 0.95) !important;
        }

        .modal-header-dark {
            background: #0f172a;
            padding: 1.1rem 1.4rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .modal-header-icon-box {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .modal-header-icon-box.icon-customer {
            background: rgba(56, 189, 248, 0.16);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.25);
        }

        .modal-header-icon-box.icon-salesrep {
            background: rgba(251, 113, 133, 0.16);
            color: #fb7185;
            border: 1px solid rgba(251, 113, 133, 0.25);
        }

        .modal-header-title-wrap {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .modal-header-sub {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
        }

        .modal-header-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-sc-pill {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: #f8fafc;
            padding: 1px 7px;
            border-radius: 6px;
            font-size: 0.74rem;
            font-weight: 800;
            font-family: monospace;
        }

        .modal-close-btn-round {
            background: rgba(255, 255, 255, 0.12);
            border: none;
            color: #f8fafc;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            transition: all 0.15s ease;
        }

        .modal-close-btn-round:hover {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
            transform: scale(1.05);
        }

        .modal-body-clean {
            padding: 1.25rem 1.4rem;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .modal-search-wrapper {
            position: relative;
            flex: 1;
        }

        .modal-search-wrapper .modal-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.95rem;
            pointer-events: none;
        }

        .modal-search-input {
            width: 100%;
            height: 46px;
            border-radius: 12px;
            padding: 0 14px 0 42px;
            font-size: 0.92rem;
            font-weight: 600;
            color: #0f172a;
            border: 1.5px solid #cbd5e1;
            background: #f8fafc;
            transition: all 0.18s ease;
            box-sizing: border-box;
            outline: none;
        }

        .modal-search-input:focus {
            border-color: #0284c7;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }

        .btn-modal-action-pill {
            height: 46px;
            padding: 0 18px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            transition: all 0.18s ease;
            border: none;
            user-select: none;
        }

        .btn-modal-walkin {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.28);
        }

        .btn-modal-walkin:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.38);
        }

        .btn-modal-direct-rep {
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.28);
        }

        .btn-modal-direct-rep:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(225, 29, 72, 0.38);
        }

        /* Results List Styling */
        .modal-results-scroll {
            max-height: 390px;
            overflow-y: auto;
            padding-right: 4px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .cust-item-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 10px 14px;
            margin-bottom: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            transition: all 0.16s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            box-sizing: border-box;
        }

        .cust-item-card:hover, .cust-item-card.selected {
            border-color: #0284c7;
            background: #f0f9ff;
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.12);
            transform: translateY(-1px);
        }

        .cust-item-card.rep-card:hover, .cust-item-card.rep-card.selected {
            border-color: #e11d48;
            background: #fff1f2;
            box-shadow: 0 6px 18px rgba(225, 29, 72, 0.12);
        }

        .cust-item-card.quick-create-card {
            border: 1.5px dashed #0284c7 !important;
            background: #f0f9ff !important;
        }

        .cust-item-card.quick-create-card:hover,
        .cust-item-card.quick-create-card.selected {
            border-color: #0369a1 !important;
            background: #e0f2fe !important;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.25) !important;
            transform: translateY(-1px);
        }

        .cust-card-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }

        .cust-avatar-box {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #f1f5f9;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
            border: 1px solid #e2e8f0;
            transition: all 0.15s ease;
        }

        .cust-avatar-box.avatar-current {
            background: #dcfce7;
            color: #15803d;
            border-color: #bbf7d0;
        }

        .cust-avatar-box.avatar-loyalty {
            background: #ede9fe;
            color: #7c3aed;
            border-color: #ddd6fe;
        }

        .cust-avatar-box.avatar-rep {
            background: #ffe4e6;
            color: #e11d48;
            border-color: #fecdd3;
        }

        .cust-info-main {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
            flex: 1;
        }

        .cust-primary-line {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .cust-name {
            font-weight: 800;
            color: #0f172a;
            font-size: 0.98rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 290px;
        }

        .cust-code-badge {
            font-size: 0.74rem;
            font-weight: 700;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 1px 7px;
            border-radius: 6px;
            font-family: monospace;
        }

        .cust-loyalty-badge {
            font-size: 0.68rem;
            background: #f5f3ff;
            color: #7c3aed;
            border: 1px solid #ddd6fe;
            padding: 2px 8px;
            border-radius: 9999px;
            font-weight: 800;
        }

        .cust-current-badge {
            font-size: 0.68rem;
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
            padding: 2px 8px;
            border-radius: 9999px;
            font-weight: 800;
        }

        .cust-meta-line {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.78rem;
            color: #64748b;
            flex-wrap: wrap;
        }

        .cust-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .cust-meta-item strong {
            color: #334155;
            font-weight: 700;
        }

        .cust-outstanding-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 1px 7px;
            border-radius: 6px;
            font-size: 0.74rem;
            font-weight: 800;
        }

        .btn-customer-select {
            height: 34px;
            padding: 0 16px;
            border-radius: 9999px;
            font-weight: 800;
            font-size: 0.82rem;
            background: #f8fafc;
            color: #334155;
            border: 1.5px solid #cbd5e1;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
            flex-shrink: 0;
            user-select: none;
        }

        .cust-item-card:hover .btn-customer-select,
        .cust-item-card.selected .btn-customer-select {
            background: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.3);
        }

        .cust-item-card.rep-card:hover .btn-customer-select,
        .cust-item-card.rep-card.selected .btn-customer-select {
            background: #e11d48;
            color: #ffffff;
            border-color: #e11d48;
            box-shadow: 0 4px 10px rgba(225, 29, 72, 0.3);
        }

        .btn-customer-select.btn-customer-current {
            background: #dcfce7;
            color: #15803d;
            border-color: #86efac;
        }

        .modal-helper-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            padding: 0.65rem 1rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 0.73rem;
            color: #64748b;
            font-weight: 600;
        }

        .modal-helper-bar kbd {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 1px 6px;
            color: #334155;
            font-weight: 700;
            font-size: 0.7rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        /* ── Pop-Up Payment Settlement Modal ── */
        #modal-payment.pos-modal {
            background: rgba(11, 19, 32, 0.75);
            padding: 1rem !important;
            display: none;
            align-items: center !important;
            justify-content: center !important;
        }

        #modal-payment.pos-modal.active {
            display: flex !important;
        }

        #modal-payment .pos-modal-content {
            width: 95% !important;
            max-width: 890px !important;
            max-height: 90vh !important;
            border-radius: 20px !important;
            border: 1.5px solid rgba(255, 255, 255, 0.14) !important;
            background: #f8fafc !important;
            display: flex !important;
            flex-direction: column !important;
            margin: auto !important;
            padding: 0 !important;
            box-shadow: 0 25px 70px -12px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(0, 0, 0, 0.08) !important;
            overflow: hidden !important;
            transform: none !important;
            transition: none !important;
        }

        #modal-payment.active .pos-modal-content {
            transform: none !important;
        }

        .pay-popup-header {
            background: linear-gradient(135deg, #0b1320 0%, #111d33 100%);
            border-bottom: 1.5px solid rgba(255, 255, 255, 0.08);
            padding: 0.85rem 1.4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            color: #ffffff;
        }

        .pay-header-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #34d399;
            font-size: 1.2rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }

        .pay-badge-pill {
            background: rgba(255, 255, 255, 0.15);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.74rem;
            font-weight: 700;
            padding: 2px 9px;
            border-radius: 999px;
        }

        .pay-header-balance-wrap {
            text-align: right;
            padding-right: 12px;
        }

        .pay-header-balance-val {
            font-family: 'Outfit', sans-serif;
            font-size: 1.35rem;
            font-weight: 900;
            color: #34d399;
            line-height: 1.1;
            letter-spacing: -0.01em;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
        }

        .btn-payment-close {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #cbd5e1;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-payment-close:hover {
            background: #ef4444;
            color: #ffffff;
            border-color: #ef4444;
            transform: scale(1.05);
        }

        .pay-popup-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 1rem 1.4rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            box-sizing: border-box;
            background: #f8fafc;
        }

        .pay-popup-footer {
            background: #ffffff;
            border-top: 1.5px solid #e2e8f0;
            padding: 0.75rem 1.4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            box-shadow: 0 -4px 18px rgba(0, 0, 0, 0.04);
            z-index: 50;
        }

        /* ── Payment Methods Grid: 4 Method Cards with Dark Luxury Gradients (POS Theme) ── */
        .pm-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 0.6rem;
            margin-bottom: 0.25rem;
        }

        .payment-method-btn {
            padding: 0.55rem 0.25rem;
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            min-height: 74px;
            height: 74px;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: inherit;
            user-select: none;
            position: relative;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.25);
            border: 2px solid transparent;
            opacity: 0.62;
            filter: brightness(0.82) grayscale(0.25);
        }

        .payment-method-btn i {
            font-size: 1.45rem !important;
            color: #ffffff !important;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.5));
            transition: transform 0.18s ease;
        }

        .payment-method-btn span {
            font-size: 0.82rem !important;
            font-weight: 800 !important;
            color: #ffffff !important;
            letter-spacing: 0.02em;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
        }

        .payment-method-btn .pm-badge-pill {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff !important;
            font-size: 8px !important;
            font-weight: 900 !important;
            padding: 1px 4px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .payment-method-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
            opacity: 0.95;
            filter: brightness(1.08) grayscale(0);
        }

        .payment-method-btn:hover i {
            transform: scale(1.08);
        }

        /* Dark Luxury Gradients for each method (Exact Match to POS Sidebar) */
        .payment-method-btn[data-method="Cash"] {
            background: linear-gradient(135deg, #042614 0%, #083c21 50%, #0d4a2b 100%);
            border-color: rgba(16, 185, 129, 0.35);
        }
        .payment-method-btn[data-method="Card"] {
            background: linear-gradient(135deg, #071529 0%, #0d2344 50%, #163765 100%);
            border-color: rgba(56, 189, 248, 0.35);
        }
        .payment-method-btn[data-method="Bank"] {
            background: linear-gradient(135deg, #190933 0%, #2b1354 50%, #3e1b78 100%);
            border-color: rgba(168, 85, 247, 0.35);
        }
        .payment-method-btn[data-method="Credit"] {
            background: linear-gradient(135deg, #270611 0%, #400b1d 50%, #591029 100%);
            border-color: rgba(244, 63, 94, 0.35);
        }
        .payment-method-btn[data-method="Split"] {
            background: linear-gradient(135deg, #1a0826 0%, #2e0f43 50%, #471766 100%);
            border-color: rgba(192, 132, 252, 0.35);
        }

        /* Active Luxury Highlight - State-of-the-Art High-Contrast Glow & Halo */
        .payment-method-btn.active {
            opacity: 1 !important;
            filter: brightness(1.25) grayscale(0) !important;
            transform: translateY(-6px) scale(1.06) !important;
            z-index: 20 !important;
        }

        /* Top luminous indicator bar on active card */
        .payment-method-btn.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 20%;
            right: 20%;
            height: 3.5px;
            background: #ffffff;
            border-radius: 0 0 5px 5px;
            box-shadow: 0 0 10px #ffffff, 0 0 4px #ffffff;
            z-index: 2;
        }

        /* Bottom floating active pill badge */
        .payment-method-btn.active::after {
            content: 'ACTIVE';
            position: absolute;
            bottom: -9px;
            left: 50%;
            transform: translateX(-50%);
            background: #0f172a;
            font-size: 7.5px;
            font-weight: 900;
            letter-spacing: 0.1em;
            padding: 2px 8px;
            border-radius: 999px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
            border: 1.5px solid currentColor;
            white-space: nowrap;
            pointer-events: none;
            z-index: 3;
        }

        /* Method-Tailored Active Glowing Borders, Rings & Badges (Ultra-Visible on Any Screen) */
        .payment-method-btn[data-method="Cash"].active {
            border: 2.5px solid #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.45), 0 0 24px rgba(16, 185, 129, 0.75), 0 14px 28px rgba(0, 0, 0, 0.5) !important;
        }
        .payment-method-btn[data-method="Cash"].active::after { color: #34d399; border-color: #10b981; }

        .payment-method-btn[data-method="Card"].active {
            border: 2.5px solid #0ea5e9 !important;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.45), 0 0 24px rgba(14, 165, 233, 0.75), 0 14px 28px rgba(0, 0, 0, 0.5) !important;
        }
        .payment-method-btn[data-method="Card"].active::after { color: #38bdf8; border-color: #0ea5e9; }

        .payment-method-btn[data-method="Bank"].active {
            border: 2.5px solid #a855f7 !important;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.45), 0 0 24px rgba(168, 85, 247, 0.75), 0 14px 28px rgba(0, 0, 0, 0.5) !important;
        }
        .payment-method-btn[data-method="Bank"].active::after { color: #c084fc; border-color: #a855f7; }

        .payment-method-btn[data-method="Credit"].active {
            border: 2.5px solid #f43f5e !important;
            box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.45), 0 0 24px rgba(244, 63, 94, 0.75), 0 14px 28px rgba(0, 0, 0, 0.5) !important;
        }
        .payment-method-btn[data-method="Credit"].active::after { color: #fb7185; border-color: #f43f5e; }

        .payment-method-btn[data-method="Split"].active {
            border: 2.5px solid #d946ef !important;
            box-shadow: 0 0 0 3px rgba(217, 70, 239, 0.45), 0 0 24px rgba(217, 70, 239, 0.75), 0 14px 28px rgba(0, 0, 0, 0.5) !important;
        }
        .payment-method-btn[data-method="Split"].active::after { color: #f0abfc; border-color: #d946ef; }

        /* Credit Customer Action Buttons Focus Highlight (Keyboard Navigation) */
        .btn-credit-cust-action:focus,
        #btn-credit-change-cust:focus,
        #btn-credit-select-cust:focus,
        #btn-credit-change-cust-warn:focus,
        #split-change-cust-btn:focus {
            outline: none !important;
            border-color: #b45309 !important;
            background: #fef3c7 !important;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.45), 0 2px 8px rgba(180, 83, 9, 0.25) !important;
            transform: translateY(-1px) scale(1.03);
        }

        /* Centered & Clean Single Method Form Container (Constrained Width) */
        .single-method-content-wrap {
            max-width: 520px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .pay-amount-box {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 0.85rem 1.15rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }

        /* Split Cards Grid */
        .split-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.55rem 0.75rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            transition: all 0.18s ease;
        }

        .split-card:hover, .split-card:focus-within {
            border-color: #cbd5e1;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
        }

        .split-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.15rem;
        }

        .split-card-title {
            font-size: 0.8rem;
            font-weight: 800;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-fill-exact {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-fill-exact:hover {
            background: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
        }

        .split-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .split-input-wrapper .currency-prefix {
            position: absolute;
            left: 12px;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 0.95rem;
            color: #94a3b8;
            pointer-events: none;
        }

        .split-amount-input {
            width: 100%;
            height: 44px;
            border-radius: 10px;
            border: 1.5px solid #cbd5e1;
            padding: 0 12px 0 52px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.45rem;
            font-weight: 900;
            text-align: right;
            color: #0f172a;
            box-sizing: border-box;
            background: #ffffff;
            transition: all 0.15s ease;
            outline: none;
        }

        .split-amount-input:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }

        .split-select-input {
            border-radius: 8px;
            border: 1.5px solid #cbd5e1;
            padding: 0 10px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #0f172a;
            box-sizing: border-box;
            background: #ffffff;
            outline: none;
        }

        .split-select-input:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }

        /* Ultra-Clean Modern Footer Action Buttons */
        .btn-pay-action {
            height: 44px;
            border-radius: 12px;
            padding: 0 22px;
            font-weight: 800;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            letter-spacing: 0.01em;
        }

        .btn-pay-action:hover {
            transform: translateY(-2px);
        }

        .btn-pay-action:active {
            transform: translateY(0);
        }

        .btn-pay-save {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(15, 118, 110, 0.28);
        }

        .btn-pay-save:hover {
            box-shadow: 0 6px 20px rgba(15, 118, 110, 0.42);
            filter: brightness(1.08);
        }

        .btn-pay-whatsapp {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.28);
        }

        .btn-pay-whatsapp:hover {
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.42);
            filter: brightness(1.08);
        }

        .btn-pay-print {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            color: #ffffff;
            padding: 0 26px;
            box-shadow: 0 4px 14px rgba(29, 78, 216, 0.28);
        }

        .btn-pay-print:hover {
            box-shadow: 0 6px 20px rgba(29, 78, 216, 0.42);
            filter: brightness(1.08);
        }

        .btn-payment-cancel {
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            color: #475569;
            height: 44px;
            padding: 0 18px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.86rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.15s ease;
        }

        .btn-payment-cancel:hover {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fca5a5;
        }

        /* ── Balance Banner ── */
        .balance-banner {
            background: linear-gradient(135deg, rgba(239, 246, 255, 0.95) 0%, rgba(255, 255, 255, 0.95) 100%);
            border: 1.5px solid #bfdbfe;
            border-radius: 16px;
            padding: 1.1rem;
            margin-top: 0.9rem;
            text-align: center;
        }

        .balance-banner-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .balance-banner-amount {
            font-family: 'SF Pro Display', sans-serif;
            font-size: 2.3rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }

        .quick-cash-btns {
            display: flex;
            gap: 0.45rem;
            margin-top: 0.65rem;
            flex-wrap: wrap;
        }

        .btn-quick-cash {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: var(--pos-text);
            font-weight: 700;
            font-size: 0.8rem;
            padding: 0.45rem 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-quick-cash:hover {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }

        /* ── Loyalty Prompt UI Modal Styles ── */
        .btn-loyalty-choice {
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.15s ease;
            outline: none;
            user-select: none;
            font-family: inherit;
        }
        .btn-loyalty-choice.btn-loyalty-cancel {
            background: #f1f5f9;
            color: #475569;
            border: 1.5px solid #cbd5e1;
        }
        .btn-loyalty-choice.btn-loyalty-cancel:hover,
        .btn-loyalty-choice.btn-loyalty-cancel.focused {
            background: #e2e8f0;
            color: #0f172a;
            border-color: #94a3b8;
            box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.45);
        }
        .btn-loyalty-choice.btn-loyalty-apply {
            background: #7c3aed;
            color: #ffffff;
            border: 1.5px solid #6d28d9;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.25);
        }
        .btn-loyalty-choice.btn-loyalty-apply:hover,
        .btn-loyalty-choice.btn-loyalty-apply.focused {
            background: #6d28d9;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.45), 0 6px 18px rgba(124, 58, 237, 0.35);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="pos-container">
        <!-- Top Navigation Header -->
        <header class="pos-header">
            <div class="header-brand-lockup">
                <img src="assets/CurtissPOS Text Logo.png" alt="CurtissPOS" class="header-curtiss-logo">
                <span class="header-collab-x">✕</span>
                <?php if (!empty($companyLogo) && file_exists(dirname(__DIR__, 2) . '/' . $companyLogo)): ?>
                    <img src="<?= htmlspecialchars($companyLogo) ?>" alt="<?= htmlspecialchars($businessName) ?>" class="header-company-logo">
                <?php else: ?>
                    <span class="header-company-name"><?= htmlspecialchars($businessName) ?></span>
                <?php endif; ?>
            </div>

            <div class="pos-info-bar">


                <?php if (!empty($allActiveBranches) && count($allActiveBranches) > 1): ?>
                <div class="header-meta-item" title="Active Branch">
                    <i class="fa-solid fa-store"></i>
                    <span><?= htmlspecialchars($currentBranch['name']) ?></span>
                </div>
                <?php endif; ?>

                <div class="header-meta-item" title="Active Cashier / User">
                    <i class="fa-solid fa-circle-user"></i>
                    <span><?= htmlspecialchars($currentUser['full_name']) ?></span>
                </div>

                <span class="header-divider"></span>

                <div id="pos-clock" class="header-clock"></div>

                <a href="dashboard.php" class="header-exit-btn" title="Back to Main ERP Dashboard">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Exit POS
                </a>
            </div>
        </header>

        <!-- POS Workspace Body -->
        <div class="pos-body">
            <!-- Left Side Bar / Quick Action Panel -->
            <aside class="pos-sidebar" id="pos-sidebar" aria-label="POS Quick Actions">
                <div class="pos-sidebar-actions">
                    <button type="button" class="pos-side-btn btn-side-returns" id="btn-pos-returns" onclick="openReturnsModal()" title="Sales Returns & Exchanges (F1)">
                        <div class="pos-side-icon-box">
                            <i class="fa-solid fa-arrow-rotate-left"></i>
                        </div>
                        <span class="pos-side-label">Returns<small>F1</small></span>
                    </button>

                    <button type="button" class="pos-side-btn btn-side-hold" id="btn-hold-sale" title="Park current invoice line items (F6)">
                        <div class="pos-side-icon-box">
                            <i class="fa-solid fa-pause"></i>
                        </div>
                        <span class="pos-side-label">Hold<small>F6</small></span>
                    </button>

                    <button type="button" class="pos-side-btn btn-side-held-list" id="btn-view-held" title="View list of parked invoices (F7)">
                        <div class="pos-side-icon-box">
                            <i class="fa-solid fa-folder-open"></i>
                        </div>
                        <span class="pos-side-label">Held List<small>F7</small></span>
                    </button>

                    <button type="button" class="pos-side-btn btn-side-history" id="btn-sales-history" onclick="openSalesHistoryModal()" title="View Sales History & Print Receipts (F5)">
                        <div class="pos-side-icon-box">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <span class="pos-side-label">History<small>Sales</small></span>
                    </button>

                    <button type="button" class="pos-side-btn btn-side-clear" id="btn-clear-sale" title="Reset cart items (F8)">
                        <div class="pos-side-icon-box">
                            <i class="fa-solid fa-rotate-right"></i>
                        </div>
                        <span class="pos-side-label">Clear<small>F8</small></span>
                    </button>

                    <button type="button" class="pos-side-btn btn-side-cancel" id="btn-cancel-sale" title="Cancel active sale (F9)">
                        <div class="pos-side-icon-box">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                        <span class="pos-side-label">Cancel<small>F9</small></span>
                    </button>
                </div>
            </aside>

            <!-- Center: Barcode Search & Cart Table -->
            <div class="pos-left">
                <!-- Barcode & Product Search Box -->
                <div class="search-box-container">
                    <i class="fa-solid fa-barcode search-icon"></i>
                    <input type="text" id="pos-search-input" class="search-input" placeholder="Scan Barcode or Type Product Name/Code (Ctrl+S / F2)" autocomplete="off" autofocus>
                    <div id="pos-search-dropdown" class="search-dropdown"></div>
                </div>

                <!-- Shopping Cart Table -->
                <div class="cart-container">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th style="width: 3.5%; text-align: center;">#</th>
                                <th style="width: 38.5%; text-align: left;">Product Item</th>
                                <th style="width: 15%; text-align: center;">Qty</th>
                                <th style="width: 13%; text-align: right;">Unit Price</th>
                                <th style="width: 14%; text-align: right;">Discount</th>
                                <th style="width: 12%; text-align: right;">Subtotal</th>
                                <th style="width: 4%; text-align: center;"></th>
                            </tr>
                        </thead>
                        <tbody id="cart-table-body">
                            <!-- JS Populated Cart Lines -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Side: Customer & Checkout Panel -->
            <div class="pos-right">
                <!-- 1. TOP: Total Payable Amount (Bigger Card - Dark Green Luxury) -->
                <div class="pos-payable-hero-card" id="card-payable-amount">
                    <div class="payable-hero-top">
                        <span class="payable-hero-badge"><i class="fa-solid fa-receipt"></i> Total Payable Amount</span>
                        <span style="font-size: 0.72rem; color: #a7f3d0; font-weight: 800; background: rgba(52, 211, 153, 0.18); padding: 2px 8px; border-radius: 999px; letter-spacing: 0.04em;">DUE NOW</span>
                    </div>
                    <div class="payable-hero-amount" id="summary-payable">LKR 0.00</div>
                    <!-- Customer Savings Line inside Top Hero -->
                    <div id="summary-savings-row" style="display: none;">
                        <span class="payable-hero-savings">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> TOTAL SAVINGS: <b id="summary-savings-val">LKR 0.00</b>
                        </span>
                    </div>
                </div>

                <!-- 2. Total Discount Card (Dark Luxury Rose / Ruby Card) -->
                <div class="pos-discount-card" id="card-total-discount">
                    <div class="discount-card-top">
                        <span class="discount-card-badge"><i class="fa-solid fa-tags"></i> Total Discount</span>
                        <span class="discount-card-pill">SAVINGS</span>
                    </div>
                    <div class="discount-card-amount" id="summary-discount-val">- LKR 0.00</div>
                </div>

                <!-- 3. Breakdown Card (Dark Luxury Midnight Card) -->
                <div class="pos-breakdown-card" id="card-order-breakdown">
                    <div class="breakdown-card-header">
                        <span class="breakdown-title"><i class="fa-solid fa-list-check"></i> Order Breakdown</span>
                    </div>

                    <div class="summary-row">
                        <span>Cart Subtotal</span>
                        <span id="summary-subtotal" style="font-weight: 800; color: #ffffff;">LKR 0.00</span>
                    </div>

                    <!-- Promo / Coupon Code Input -->
                    <div class="summary-row" style="margin-top: 2px;">
                        <span>Promo / Coupon</span>
                        <div style="display: flex; gap: 4px; align-items: center;">
                            <input type="text" id="summary-coupon-input" class="item-discount-input" placeholder="Coupon Code" style="width: 90px; text-transform: uppercase; text-align: right;" autocomplete="off" title="Enter Promo / Coupon Code" onkeydown="if(event.key==='Enter'){event.preventDefault();applyCouponCode();}">
                            <button type="button" id="btn-apply-coupon" onclick="applyCouponCode()" class="btn-qty" style="height: 28px; width: auto; padding: 0 8px; font-size: 11px; font-weight: 700; border-radius: 6px; cursor: pointer;" title="Apply Coupon">Apply</button>
                        </div>
                    </div>

                    <!-- Applied Promotions Badges Container -->
                    <div id="applied-promos-container" style="display: none; padding: 2px 0;">
                        <div id="applied-promos-badges" style="display: flex; flex-wrap: wrap; gap: 4px;"></div>
                    </div>

                    <!-- Eligible Promotion Available Banner -->
                    <div class="summary-row" id="summary-promo-available-row" style="display: none; background: rgba(22, 163, 74, 0.15); border: 1px dashed rgba(74, 222, 128, 0.4); border-radius: 8px; padding: 6px 10px; margin-bottom: 4px; align-items: center; justify-content: space-between;">
                        <span style="color: #4ade80; font-weight: 700; font-size: 0.8rem;" id="summary-promo-available-text"><i class="fa-solid fa-gift"></i> Promo Available</span>
                        <button type="button" onclick="promptPromotionModal()" class="btn-qty" style="height: 24px; width: auto; padding: 0 10px; font-size: 11px; font-weight: 800; border-radius: 6px; background: #16a34a; color: #ffffff; border: none; cursor: pointer;">Apply</button>
                    </div>

                    <!-- Promotional Discounts Line -->
                    <div class="summary-row" id="summary-promo-row" style="display: none; align-items: center; justify-content: space-between;">
                        <span style="color: #4ade80; font-weight: 700;"><i class="fa-solid fa-tag fa-xs"></i> Promotion Applied</span>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span id="summary-promo-val" style="color: #4ade80; font-weight: 800;">- LKR 0.00</span>
                            <span onclick="cancelAppliedPromotion()" style="cursor: pointer; color: #f87171; font-size: 14px; font-weight: 800;" title="Remove Promotion">&times;</span>
                        </div>
                    </div>

                    <div class="summary-row">
                        <span>Invoice Discount <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; font-family: monospace;">(Ctrl+D)</span></span>
                        <input type="text" id="summary-discount-input" class="item-discount-input" placeholder="0 or 10%" style="width: 100px; text-align: right;" autocomplete="off" title="Invoice Discount (Ctrl+D)">
                    </div>

                    <!-- Round-up Suggestion Button -->
                    <div id="roundup-container" style="display: none;">
                        <div class="roundup-pill" id="btn-apply-roundup">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span id="roundup-text">Add LKR 0.00 to Round total to LKR 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- POS Action Button Area -->
                <div class="checkout-action-area">
                    <button type="button" class="btn-pos-pay-hero" id="btn-proceed-pay" title="Proceed to payment view (Enter / F10)">
                        <div class="pay-hero-left">
                            <div class="pay-hero-icon">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div class="pay-hero-text">
                                <span class="pay-hero-title">COMPLETE SALE</span>
                                <span class="pay-hero-sub">Enter / F10</span>
                            </div>
                        </div>
                        <i class="fa-solid fa-arrow-right pay-hero-arrow"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Keyboard Shortcut Footer -->
        <footer class="shortcut-bar">
            <span><span class="sc-tag" style="background: #fee2e2; color: #b91c1c; border-color: #fca5a5;">F1</span> Returns</span>
            <span><span class="sc-tag">Ctrl+S / F2</span> Search</span>
            <span><span class="sc-tag">Ctrl+D</span> Bill Discount</span>
            <span><span class="sc-tag">Tab / +/-</span> Qty Adjust</span>
            <span><span class="sc-tag">F6</span> Hold</span>
            <span><span class="sc-tag">F7</span> Held List</span>
            <span><span class="sc-tag">F8</span> Clear</span>
            <span><span class="sc-tag">F9</span> Cancel</span>
            <span><span class="sc-tag">F10 / Enter</span> Pay</span>
        </footer>
    </div>

    <!-- Full-Screen Sales Returns & Exchanges Modal -->
    <div class="pos-modal" id="modal-pos-returns" style="z-index: 100080; padding: 0;">
        <div style="width: 100vw; height: 100vh; max-width: 100vw; max-height: 100vh; background: #ffffff; display: flex; flex-direction: column; overflow: hidden; border-radius: 0;">
            <!-- Modal Header Bar -->
            <div style="background: #0f172a; color: #ffffff; height: 48px; min-height: 48px; padding: 0 1.25rem; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.15);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(239, 68, 68, 0.2); color: #f87171; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                    </div>
                    <span style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1rem; color: #ffffff;">Sales Returns & Exchanges</span>
                    <span style="font-size: 0.72rem; background: rgba(255, 255, 255, 0.12); color: #cbd5e1; padding: 2px 8px; border-radius: 999px; font-weight: 700;">POS Integration</span>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 0.75rem; color: #94a3b8;"><kbd style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; padding: 1px 6px;">Esc</kbd> Close</span>
                    <button type="button" onclick="closeReturnsModal()" style="background: rgba(255, 255, 255, 0.15); border: none; color: #ffffff; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: background 0.15s;" title="Close Returns Modal (Esc)">&times;</button>
                </div>
            </div>
            <!-- Iframe loading the exact existing returns page -->
            <div style="flex: 1; min-height: 0; position: relative; background: #f8fafc;">
                <iframe id="pos-returns-iframe" src="" style="width: 100%; height: 100%; border: none;" allow="clipboard-read; clipboard-write"></iframe>
            </div>
        </div>
    </div>

    <!-- Lot Selector Modal -->
    <div class="pos-modal" id="modal-lot-selector">
        <div class="pos-modal-content">
            <div class="pos-modal-header">
                <span id="lot-modal-title"><i class="fa-solid fa-boxes-stacked me-2"></i> Select Stock Lot</span>
                <button type="button" style="background: none; border: none; color: var(--pos-text); cursor: pointer; font-size: 1.2rem;" onclick="closeModal('modal-lot-selector')">&times;</button>
            </div>
            <div class="pos-modal-body" id="lot-modal-body">
                <!-- JS Populated -->
            </div>
        </div>
    </div>

    <!-- Held Invoices Modal -->
    <div class="pos-modal" id="modal-held-invoices">
        <div class="pos-modal-content" style="max-width: 700px;">
            <div class="pos-modal-header">
                <span><i class="fa-solid fa-pause-circle me-2"></i> Parked / Held Invoices</span>
                <button type="button" style="background: none; border: none; color: var(--pos-text); cursor: pointer; font-size: 1.2rem;" onclick="closeModal('modal-held-invoices')">&times;</button>
            </div>
            <div class="pos-modal-body" id="held-modal-body">
                <!-- JS Populated -->
            </div>
        </div>
    </div>

    <!-- Sales History Modal -->
    <div class="pos-modal" id="modal-sales-history" style="z-index: 100060;">
        <div class="pos-modal-content" style="max-width: 1040px; width: 95vw; height: 86vh; max-height: 86vh; padding: 0; border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25); border: 1px solid rgba(226, 232, 240, 0.95);">
            <!-- Modal Header -->
            <div style="background: #0f172a; padding: 1.1rem 1.4rem; color: #ffffff; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(56, 189, 248, 0.2); display: flex; align-items: center; justify-content: center; font-size: 1.05rem; color: #38bdf8;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">POS Operations</div>
                        <div style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 800; color: #ffffff;">Sales History & Receipts</div>
                    </div>
                </div>
                <button type="button" onclick="closeSalesHistoryModal()" style="background: rgba(255, 255, 255, 0.12); border: none; color: #fff; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: background 0.15s;">&times;</button>
            </div>

            <!-- Toolbar: Date Presets & Real-time Search -->
            <div style="padding: 0.8rem 1.4rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; flex-shrink: 0;">
                <!-- Date Presets -->
                <div style="display: flex; align-items: center; gap: 6px;">
                    <button type="button" class="hist-preset-btn active" id="btn-hist-preset-today" onclick="setSalesHistoryPreset('today')">Today</button>
                    <button type="button" class="hist-preset-btn" id="btn-hist-preset-yesterday" onclick="setSalesHistoryPreset('yesterday')">Yesterday</button>
                    <button type="button" class="hist-preset-btn" id="btn-hist-preset-week" onclick="setSalesHistoryPreset('week')">Last 7 Days</button>
                    <button type="button" class="hist-preset-btn" id="btn-hist-preset-all" onclick="setSalesHistoryPreset('all')">All Sales</button>
                </div>

                <!-- Search Input & Refresh -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 0.75rem; color: #94a3b8; pointer-events: none;"></i>
                        <input type="text" id="hist-search-input" placeholder="Search invoice, customer, phone, cashier, payment..." oninput="debounceHistorySearch()" style="height: 34px; border-radius: 999px; border: 1.5px solid #cbd5e1; padding: 0 14px 0 32px; font-size: 0.82rem; font-weight: 600; width: 310px; outline: none; background: #ffffff;">
                    </div>
                    <button type="button" onclick="loadSalesHistory(1)" title="Refresh History" style="height: 34px; width: 34px; border-radius: 50%; border: 1.5px solid #cbd5e1; background: #ffffff; color: #475569; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s ease;">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                </div>
            </div>

            <!-- Table Container with Internal Scroll & Sticky Header -->
            <div style="flex: 1; min-height: 0; overflow-y: auto; background: #ffffff;">
                <table class="sales-hist-table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 10; text-align: left;">Invoice #</th>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 10; text-align: left;">Date & Time</th>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 10; text-align: left;">Customer</th>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 10; text-align: center;">Items</th>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 10; text-align: right;">Total (LKR)</th>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 10; text-align: center;">Payment</th>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 10; text-align: center;">Status</th>
                            <th style="padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 10; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sales-history-tbody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>

            <!-- Footer Pagination -->
            <div style="padding: 0.75rem 1.4rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <div id="hist-page-info" style="font-size: 0.82rem; font-weight: 600; color: #64748b;">
                    Showing 0 to 0 of 0 sales
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" id="btn-hist-prev" onclick="prevSalesHistoryPage()" style="height: 32px; padding: 0 14px; border-radius: 8px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #475569; font-size: 0.78rem; font-weight: 700; cursor: pointer;">
                        <i class="fa-solid fa-chevron-left me-1"></i> Prev
                    </button>
                    <span id="hist-page-num" style="font-size: 0.82rem; font-weight: 800; color: #0f172a; padding: 0 6px;">1</span>
                    <button type="button" id="btn-hist-next" onclick="nextSalesHistoryPage()" style="height: 32px; padding: 0 14px; border-radius: 8px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #475569; font-size: 0.78rem; font-weight: 700; cursor: pointer;">
                        Next <i class="fa-solid fa-chevron-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sale Details Modal / Drawer -->
    <div class="pos-modal" id="modal-sale-details" style="z-index: 100070;">
        <div class="pos-modal-content" style="max-width: 720px; width: 92vw; max-height: 86vh; padding: 0; border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25); border: 1px solid rgba(226, 232, 240, 0.95);">
            <div style="background: #0f172a; padding: 1.1rem 1.4rem; color: #ffffff; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(56, 189, 248, 0.2); display: flex; align-items: center; justify-content: center; font-size: 1.05rem; color: #38bdf8;">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">Sale Overview</div>
                        <div style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 800; color: #ffffff;" id="detail-invoice-title">Invoice #</div>
                    </div>
                </div>
                <button type="button" onclick="closeSaleDetailsModal()" style="background: rgba(255, 255, 255, 0.12); border: none; color: #fff; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: background 0.15s;">&times;</button>
            </div>

            <div style="flex: 1; min-height: 0; overflow-y: auto; padding: 1.25rem 1.4rem; background: #ffffff;" id="sale-details-content">
                <!-- JS populated -->
            </div>

            <div style="padding: 0.85rem 1.4rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <button type="button" onclick="closeSaleDetailsModal()" style="height: 38px; padding: 0 16px; border-radius: 10px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 700; cursor: pointer;">
                    Close
                </button>
                <button type="button" id="btn-detail-print" onclick="" class="btn-hist-print" style="height: 38px; padding: 0 18px; font-size: 0.88rem;">
                    <i class="fa-solid fa-print me-1"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>






    <!-- Loyalty Customer Detected Prompt Modal -->
    <div class="pos-modal" id="modal-loyalty-prompt" style="z-index: 100050;">
        <div class="pos-modal-content" style="max-width: 480px; padding: 0; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.22); border: 1px solid rgba(226, 232, 240, 0.95);">
            <div style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); padding: 1.25rem 1.5rem; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fde047;">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.9;">Membership Loyalty Detected</div>
                        <div style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 800;">Loyalty Discount Offer</div>
                    </div>
                </div>
                <button type="button" onclick="cancelLoyaltyPrompt()" style="background: rgba(255, 255, 255, 0.15); border: none; color: #fff; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: background 0.15s;">&times;</button>
            </div>

            <div style="padding: 1.5rem 1.5rem 1.25rem 1.5rem; background: #ffffff;">
                <div style="background: #f5f3ff; border: 1.5px solid #ddd6fe; border-radius: 14px; padding: 1rem 1.15rem; margin-bottom: 1.15rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem;">
                        <span style="font-weight: 800; color: #4c1d95; font-size: 1.05rem;" id="loyalty-modal-cust-name">-</span>
                        <span id="loyalty-modal-pct-badge" style="background: #7c3aed; color: #ffffff; font-weight: 900; font-size: 0.8rem; padding: 3px 10px; border-radius: 999px;">10% OFF</span>
                    </div>
                    <div style="font-size: 0.82rem; color: #6d28d9; line-height: 1.45;" id="loyalty-modal-desc">
                        Customer is an active Loyalty Member eligible for a discount.
                    </div>
                </div>

                <div style="font-size: 0.92rem; color: #334155; font-weight: 600; text-align: center; margin-bottom: 1.25rem;">
                    Would you like to apply this <strong id="loyalty-modal-pct-text" style="color: #7c3aed;">10%</strong> Loyalty Discount to the bill?
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 0.75rem;">
                    <button type="button" id="btn-loyalty-cancel" onclick="cancelLoyaltyPrompt()" class="btn-loyalty-choice btn-loyalty-cancel">
                        <i class="fa-solid fa-xmark"></i> Skip (Cancel)
                    </button>
                    <button type="button" id="btn-loyalty-apply" onclick="confirmApplyLoyaltyDiscount()" class="btn-loyalty-choice btn-loyalty-apply focused">
                        <i class="fa-solid fa-check"></i> Apply Discount (Enter)
                    </button>
                </div>

                <div style="display: flex; justify-content: center; gap: 12px; margin-top: 0.85rem; font-size: 0.72rem; color: #94a3b8; font-weight: 600;">
                    <span><kbd style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1px 5px; color: #475569;">←</kbd> <kbd style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1px 5px; color: #475569;">→</kbd> Navigate</span>
                    <span><kbd style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1px 5px; color: #475569;">Enter</kbd> Select</span>
                    <span><kbd style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1px 5px; color: #475569;">Esc</kbd> Skip</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Promotion Available Confirmation Popup Modal -->
    <div class="pos-modal" id="modal-promotion-confirm" style="z-index: 100060;">
        <div class="pos-modal-content" style="max-width: 480px; padding: 0; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.22); border: 1px solid rgba(226, 232, 240, 0.95);">
            <div style="background: #0f172a; padding: 1.25rem 1.5rem; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(255, 255, 255, 0.12); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; color: #38bdf8;">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">Promotion Available</div>
                        <div style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 800; color: #ffffff;">Eligible Discount Offer</div>
                    </div>
                </div>
                <button type="button" onclick="dismissPromotionPrompt()" style="background: rgba(255, 255, 255, 0.15); border: none; color: #fff; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: background 0.15s;">&times;</button>
            </div>

            <div style="padding: 1.5rem 1.5rem 1.25rem 1.5rem; background: #ffffff;">
                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 1.15rem; margin-bottom: 1.15rem;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 0.5rem;">
                        <div>
                            <div style="font-weight: 800; color: #0f172a; font-size: 1.05rem;" id="promo-modal-title">Special Promotion</div>
                            <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;" id="promo-modal-scope">Eligible items in cart</div>
                        </div>
                        <span id="promo-modal-savings-badge" style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; font-weight: 900; font-size: 0.82rem; padding: 3px 10px; border-radius: 999px; white-space: nowrap;">Save LKR 0.00</span>
                    </div>
                    <div style="font-size: 0.82rem; color: #475569; line-height: 1.45;" id="promo-modal-desc">
                        A qualifying promotional discount was detected for this bill.
                    </div>
                </div>

                <div style="font-size: 0.92rem; color: #334155; font-weight: 600; text-align: center; margin-bottom: 1.25rem;">
                    Would you like to apply this promotional discount to this bill?
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 0.75rem;">
                    <button type="button" id="btn-promo-cancel" onclick="dismissPromotionPrompt()" onkeydown="handlePromoButtonKey(event)" style="height: 44px; border-radius: 12px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 800; font-size: 0.88rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.15s;">
                        <i class="fa-solid fa-xmark"></i> Cancel (Don't Apply)
                    </button>
                    <button type="button" id="btn-promo-apply" onclick="confirmApplyPromotion()" onkeydown="handlePromoButtonKey(event)" style="height: 44px; border-radius: 12px; border: none; background: #0f172a; color: #ffffff; font-weight: 800; font-size: 0.88rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2); transition: all 0.15s;">
                        <i class="fa-solid fa-check"></i> Apply Discount (Enter)
                    </button>
                </div>

                <div style="display: flex; justify-content: center; gap: 12px; margin-top: 0.85rem; font-size: 0.72rem; color: #94a3b8; font-weight: 600;">
                    <span><kbd style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1px 5px; color: #475569;">Enter</kbd> Apply</span>
                    <span><kbd style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1px 5px; color: #475569;">Esc</kbd> Cancel</span>
                </div>
            </div>
        </div>
    </div>



    <!-- Enhanced Dedicated Payment Settlement View / Popup Modal Dialog -->
    <div class="pos-modal" id="modal-payment">
        <div class="pos-modal-content">
            <!-- Luxury Header -->
            <div class="pay-popup-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="pay-header-icon">
                        <i class="fa-solid fa-cash-register"></i>
                    </span>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <h2 style="font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 1.35rem; color: #ffffff; margin: 0; line-height: 1.1;">Payment Settlement</h2>
                        </div>
                        <div style="font-size: 0.74rem; color: #94a3b8; margin-top: 2px;">
                            Select payment method &bull; Enter amount to finalize sale
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 14px;">
                    <div class="pay-header-balance-wrap">
                        <div style="font-size: 0.65rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em;">Remaining Due</div>
                        <div id="pay-modal-remaining" class="pay-header-balance-val">LKR 0.00</div>
                    </div>
                    <button type="button" class="btn-payment-close" onclick="closeModal('modal-payment')" title="Return to cart (Esc)">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <!-- Legacy Hidden Elements for Seamless JS Compatibility (User: No need to show total) -->
            <div style="display: none;">
                <span id="pay-header-customer"></span>
                <span id="pay-header-salesrep"></span>
                <div id="pay-modal-total">LKR 0.00</div>
                <div id="pay-modal-discount">- LKR 0.00</div>
                <div id="lbl-total-allocated">LKR 0.00</div>
                <div id="metric-card-remaining"></div>
                <div id="lbl-remaining-title"></div>
                <input type="hidden" id="split-cash-tendered">
                <span id="split-cash-change-badge"></span>
            </div>

            <!-- Popup Scrollable Body -->
            <div class="pay-popup-body">
                <!-- Payment Method Cards (Dark Luxury Styling) -->
                <div>
                    <div style="margin-bottom: 0.45rem;">
                        <span style="font-size: 0.74rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.07em;">
                            Select Payment Method
                        </span>
                    </div>
                    <div class="pm-grid">
                        <button type="button" class="payment-method-btn active" data-method="Cash" onclick="switchPaymentMethod('Cash')">
                            <i class="fa-solid fa-money-bill-wave"></i>
                            <span>Cash</span>
                        </button>
                        <button type="button" class="payment-method-btn" data-method="Card" onclick="switchPaymentMethod('Card')">
                            <i class="fa-solid fa-credit-card"></i>
                            <span>Card</span>
                        </button>
                        <button type="button" class="payment-method-btn" data-method="Bank" onclick="switchPaymentMethod('Bank')">
                            <i class="fa-solid fa-building-columns"></i>
                            <span>Bank</span>
                        </button>
                        <button type="button" class="payment-method-btn" data-method="Split" onclick="switchPaymentMethod('Split')">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>Split</span>
                            <span class="pm-badge-pill">MULTI</span>
                        </button>
                    </div>
                </div>

                <!-- Single Payment Method Container (Cash / Card / Bank / Credit) -->
                <div id="single-payment-method-container">
                    <div class="single-method-content-wrap">
                        <!-- Centered Amount Input Box -->
                        <div class="pay-amount-box">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                <label id="lbl-split-amount" for="pay-split-amount-input" style="font-weight: 800; font-size: 0.88rem; color: #0f172a; margin: 0;">
                                    Amount to Pay with <span id="active-method-label" style="color: #0284c7;">Cash</span> (LKR)
                                </label>
                                <button type="button" class="btn-fill-exact" onclick="setSplitExactRemaining()">
                                    <i class="fa-solid fa-bolt me-1"></i> Exact Due (<span id="lbl-btn-exact-due">LKR 0.00</span>)
                                </button>
                            </div>
                            <div class="split-input-wrapper">
                                <span class="currency-prefix">LKR</span>
                                <input type="number" id="pay-split-amount-input" class="split-amount-input" placeholder="0.00" step="0.01">
                            </div>
                        </div>

                        <!-- Cash Specific Method Details -->
                        <div id="cash-payment-section">
                            <div class="pay-amount-box" style="margin-bottom: 0.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                    <label for="pay-cash-given-input" style="font-weight: 800; font-size: 0.82rem; color: #475569; margin: 0;">
                                        Cash Tendered by Customer (LKR)
                                    </label>
                                    <span style="font-size: 0.72rem; color: #64748b;">(For calculating balance return)</span>
                                </div>
                                <div class="split-input-wrapper">
                                    <span class="currency-prefix">LKR</span>
                                    <input type="number" id="pay-cash-given-input" class="split-amount-input" placeholder="Enter Tendered Cash..." step="0.01" style="height: 40px; font-size: 1.3rem;">
                                </div>
                                
                                <div class="quick-cash-btns" style="margin-top: 0.45rem;">
                                    <button type="button" class="btn-quick-cash" onclick="setCashTendered('exact')">Exact Amount</button>
                                    <button type="button" class="btn-quick-cash" onclick="setCashTendered(500)">LKR 500</button>
                                    <button type="button" class="btn-quick-cash" onclick="setCashTendered(1000)">LKR 1,000</button>
                                    <button type="button" class="btn-quick-cash" onclick="setCashTendered(2000)">LKR 2,000</button>
                                    <button type="button" class="btn-quick-cash" onclick="setCashTendered(5000)">LKR 5,000</button>
                                </div>
                            </div>

                            <!-- Balance Return Banner -->
                            <div class="balance-banner" style="margin-top: 0; padding: 0.65rem 1rem;">
                                <div class="balance-banner-label" style="font-size: 0.74rem;">Cash Balance to Return</div>
                                <div class="balance-banner-amount" id="pay-change-amount" style="font-size: 1.6rem; color: #15803d;">LKR 0.00</div>
                            </div>
                        </div>

                        <!-- Card Specific Method Details -->
                        <div id="card-payment-section" style="display: none;">
                            <div style="display: flex; align-items: center; gap: 12px; padding: 0.85rem 1.15rem; background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 14px;">
                                <div style="width: 40px; height: 40px; border-radius: 10px; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                                    <i class="fa-solid fa-credit-card"></i>
                                </div>
                                <div>
                                    <div style="font-size: 0.88rem; font-weight: 800; color: #1e3a8a;">Card Payment Terminal</div>
                                    <div style="font-size: 0.78rem; color: #2563eb; line-height: 1.4;">Process the allocated amount on the merchant bank card terminal, then finalize.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Specific Method Details -->
                        <div id="bank-payment-section" style="display: none;">
                            <div class="pay-amount-box">
                                <div style="margin-bottom: 0.55rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                        <label for="pay-bank-account-select" style="font-weight: 800; font-size: 0.82rem; color: #475569; margin: 0;">
                                            Destination Bank Account <span style="color: #dc2626;">*</span>
                                        </label>
                                        <span style="font-size: 0.72rem; color: #dc2626; font-weight: 700;">Required</span>
                                    </div>
                                    <select id="pay-bank-account-select" class="split-select-input" style="width: 100%; height: 38px; font-weight: 700; cursor: pointer; font-size: 0.84rem;">
                                        <option value="">-- Choose Active Bank Account --</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="pay-bank-ref-input" style="font-weight: 800; font-size: 0.82rem; color: #475569; display: block; margin-bottom: 0.25rem;">
                                        Transfer Reference / Slip No <span style="font-weight: normal; font-size: 0.72rem; color: #64748b;">(Optional)</span>
                                    </label>
                                    <input type="text" id="pay-bank-ref-input" class="split-select-input" placeholder="e.g. Slip #49120 or Tx Ref" style="width: 100%; height: 38px; font-size: 0.84rem;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dedicated Multi-Method Split Payment Section -->
                <div id="split-multi-payment-section" style="display: none;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.65rem;">
                        <!-- Card 1: Cash (Ultra-Clean - Text & Buttons Removed on Split) -->
                        <div class="split-card" style="border-top: 4px solid #10b981;">
                            <div class="split-card-header">
                                <span class="split-card-title"><i class="fa-solid fa-money-bill-wave" style="color: #10b981;"></i> Cash Amount</span>
                                <button type="button" class="btn-fill-exact" onclick="fillSplitField('split-cash-amount')">Fill Remaining</button>
                            </div>
                            <div class="split-input-wrapper">
                                <span class="currency-prefix">LKR</span>
                                <input type="number" id="split-cash-amount" class="split-amount-input" placeholder="0.00" step="0.01" style="height: 38px; font-size: 1.25rem;">
                            </div>
                        </div>

                        <!-- Card 2: Card (Ultra-Clean - Prompt Text Removed on Split) -->
                        <div class="split-card" style="border-top: 4px solid #3b82f6;">
                            <div class="split-card-header">
                                <span class="split-card-title"><i class="fa-solid fa-credit-card" style="color: #3b82f6;"></i> Card Amount</span>
                                <button type="button" class="btn-fill-exact" onclick="fillSplitField('split-card-amount')">Fill Remaining</button>
                            </div>
                            <div class="split-input-wrapper">
                                <span class="currency-prefix">LKR</span>
                                <input type="number" id="split-card-amount" class="split-amount-input" placeholder="0.00" step="0.01" style="height: 38px; font-size: 1.25rem;">
                            </div>
                        </div>

                        <!-- Card 3: Bank Transfer -->
                        <div class="split-card" style="border-top: 4px solid #6366f1;">
                            <div class="split-card-header">
                                <span class="split-card-title"><i class="fa-solid fa-building-columns" style="color: #6366f1;"></i> Bank Transfer</span>
                                <button type="button" class="btn-fill-exact" onclick="fillSplitField('split-bank-amount')">Fill Remaining</button>
                            </div>
                            <div class="split-input-wrapper">
                                <span class="currency-prefix">LKR</span>
                                <input type="number" id="split-bank-amount" class="split-amount-input" placeholder="0.00" step="0.01" style="height: 38px; font-size: 1.25rem;">
                            </div>
                            <!-- Dynamic bank details: Shown ONLY when split-bank-amount > 0 -->
                            <div id="split-bank-details" style="display: none; margin-top: 0.35rem; padding: 0.45rem 0.65rem; background: #eef2ff; border: 1.5px solid #c7d2fe; border-radius: 10px;">
                                <label for="split-bank-account-select" style="font-size: 0.72rem; font-weight: 800; color: #3730a3; display: block; margin-bottom: 0.2rem;">
                                    Destination Bank Account <span style="color: #dc2626;">*</span>
                                </label>
                                <select id="split-bank-account-select" class="split-select-input" style="width: 100%; height: 32px; margin-bottom: 0.35rem; font-weight: 700; font-size: 0.78rem;">
                                    <option value="">-- Choose Active Bank Account --</option>
                                </select>
                                <input type="text" id="split-bank-ref-input" class="split-select-input" placeholder="Transfer Ref / Slip No (Optional)" style="width: 100%; height: 30px; font-size: 0.78rem;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fixed Bottom Control Bar (Ultra-Clean Layout) -->
            <div class="pay-popup-footer">
                <button type="button" class="btn-payment-cancel" onclick="closeModal('modal-payment')">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Cart
                </button>

                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="btn-pay-action btn-pay-save" id="btn-save-sale-only" onclick="finalizeSale('none')" title="Save Bill without print (Enter)">
                        <i class="fa-solid fa-floppy-disk" style="font-size: 1.15rem;"></i>
                        <span>Save (Enter)</span>
                    </button>

                    <button type="button" class="btn-pay-action btn-pay-whatsapp" id="btn-save-whatsapp-sale" onclick="finalizeSale('whatsapp')" title="Save Bill and send via WhatsApp (Alt + Enter)">
                        <i class="fa-brands fa-whatsapp" style="font-size: 1.2rem;"></i>
                        <span>Save & WhatsApp</span>
                    </button>

                    <button type="button" class="btn-pay-action btn-pay-print" id="btn-save-print-sale" onclick="finalizeSale('print')" title="Save Bill and print receipt (Ctrl + Enter)">
                        <i class="fa-solid fa-print" style="font-size: 1.15rem;"></i>
                        <span>Save & Print (Ctrl+Enter)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Complete Success Modal -->
    <div class="pos-modal" id="modal-invoice-complete">
        <div class="pos-modal-content" style="max-width: 520px;">
            <div class="pos-modal-header" style="background: #ecfdf5; border-bottom-color: #a7f3d0; color: #047857;">
                <span><i class="fa-solid fa-circle-check me-2" style="font-size: 1.4rem;"></i> Sale Finalized Successfully!</span>
            </div>
            <div class="pos-modal-body" style="padding: 1.6rem; text-align: center;">
                <div style="font-size: 0.85rem; color: var(--pos-text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Invoice Number</div>
                <div id="complete-inv-no" style="font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 900; color: var(--pos-primary); margin-bottom: 1rem;">INV-000000</div>

                <div style="background: #f8fafc; border: 1px solid var(--pos-border); border-radius: 12px; padding: 1.1rem; margin-bottom: 1.25rem; display: flex; justify-content: space-around;">
                    <div>
                        <div style="font-size: 0.8rem; color: var(--pos-text-muted); font-weight: 700;">Total Paid</div>
                        <div id="complete-total" style="font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 900; color: var(--pos-text);">LKR 0.00</div>
                    </div>
                    <div style="border-left: 1px solid var(--pos-border);"></div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--pos-text-muted); font-weight: 700;">Payment Method</div>
                        <div id="complete-payment" style="font-size: 1.2rem; font-weight: 800; color: var(--pos-text);">Cash</div>
                    </div>
                </div>

                <div style="background: #dcfce7; border: 2px solid #86efac; border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem;">
                    <div style="font-size: 0.85rem; font-weight: 800; color: #15803d; text-transform: uppercase;">Balance Given</div>
                    <div id="complete-balance" style="font-family: 'Outfit', sans-serif; font-size: 2.5rem; font-weight: 900; color: #166534;">LKR 0.00</div>
                </div>

                <!-- Actions -->
                <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                    <button type="button" id="btn-complete-newsale" class="btn-pos-action" style="background: #000000; color: #fff; padding: 0.95rem; font-size: 1.15rem; flex-direction: row; border-radius: 10px;">
                        <i class="fa-solid fa-plus-circle me-2"></i> NEW INVOICE (Enter)
                    </button>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem;">
                        <button type="button" id="btn-complete-print" class="btn-pos-action" style="background: #10b981; color: #fff; padding: 0.8rem; flex-direction: row; border-radius: 10px;">
                            <i class="fa-solid fa-print me-2"></i> Print Invoice (P)
                        </button>
                        <button type="button" id="btn-complete-whatsapp" class="btn-pos-action" style="background: #25d366; color: #fff; padding: 0.8rem; flex-direction: row; border-radius: 10px;">
                            <i class="fa-brands fa-whatsapp me-2"></i> Send via WhatsApp (W)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JS POS Billing Engine -->
    <script src="assets/js/qrcode.min.js"></script>
    <script>
        // Ensure no stale Service Workers or browser cache intercept realtime requests
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(registrations => {
                for (const reg of registrations) {
                    reg.unregister();
                }
            }).catch(() => {});
        }
        if ('caches' in window) {
            caches.keys().then(names => {
                for (const name of names) {
                    caches.delete(name);
                }
            }).catch(() => {});
        }

        const DEFAULT_LOYALTY_DISCOUNT_PERCENTAGE = <?= json_encode((float)Settings::get('loyalty_discount_percentage', '5.00')) ?>;

        // State variables
        let cart = [];
        let currentCustomer = { id: 1, name: 'Walk-in Customer', customer_code: 'CUST-WALKIN', is_walkin: 1 };
        let activeSaleId = null;
        let pendingLotItemIndex = null;
        let paymentAllocations = [];
        let activePaymentMethod = 'Cash';
        let lastCompletedSaleData = null;
        
        let currentSearchProducts = [];
        let selectedSearchIndex = -1;

        let currentSearchCustomers = [];
        let selectedCustIndex = 0;
        let selectedLotIndex = 0;
        let lastLotModalOpenTime = 0;
        let activeBankAccounts = [];

        let currentSalesRep = null;
        let currentSearchReps = [];
        let selectedRepIndex = 0;
        let repSearchTimer = null;

        // Permissions flags passed from PHP session
        const CAN_OVERRIDE_PRICE = <?= Auth::hasPermission('sales.override_price') ? 'true' : 'false' ?>;
        const CAN_DISCOUNT = <?= Auth::hasPermission('sales.discount') ? 'true' : 'false' ?>;
        const CURRENT_USER_ID = <?= (int)$currentUser['id'] ?>;
        const CURRENT_BRANCH_ID = <?= (int)$currentBranch['id'] ?>;
        const IS_SPECIAL_USER = <?= Auth::isSpecialUser() ? 'true' : 'false' ?>;

        // DOM Elements
        let lastModalClosedTime = 0;
        const searchInput = document.getElementById('pos-search-input');
        const searchDropdown = document.getElementById('pos-search-dropdown');
        const cartTableBody = document.getElementById('cart-table-body');
        const summarySubtotal = document.getElementById('summary-subtotal');
        const summaryDiscountInput = document.getElementById('summary-discount-input');
        const summaryDiscountVal = document.getElementById('summary-discount-val');
        const summaryPayable = document.getElementById('summary-payable');
        const roundupContainer = document.getElementById('roundup-container');
        const roundupText = document.getElementById('roundup-text');
        const btnApplyRoundup = document.getElementById('btn-apply-roundup');

        // Clock initialization
        setInterval(() => {
            const now = new Date();
            document.getElementById('pos-clock').innerText = now.toLocaleDateString() + ' ' + now.toLocaleTimeString();
        }, 1000);

        // Fetch Walk-in Default Customer on load
        fetch('pos_api.php?action=get_walkin_customer')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    currentCustomer = data.data;
                    updateCustomerDisplay();
                }
            })
            .catch(err => console.error("Error fetching walk-in customer", err));

        function updateCustomerDisplay() {
            const nameEl = document.getElementById('cust-disp-name');
            const codeEl = document.getElementById('cust-disp-code');
            const isLoyalty = (currentCustomer.is_loyalty == 1 || currentCustomer.is_loyalty === '1');
            const loyaltyBadge = isLoyalty ? '<span style="background: #7c3aed; color: #ffffff; font-size: 0.65rem; padding: 2px 7px; border-radius: 999px; font-weight: 800; margin-left: 6px; letter-spacing: 0.5px;">★ LOYALTY</span>' : '';
            if (nameEl) nameEl.innerHTML = escapeHtml(currentCustomer.name) + loyaltyBadge;
            if (codeEl) codeEl.innerText = currentCustomer.card_barcode ? `Card: ${currentCustomer.card_barcode}` : (currentCustomer.phone ? `Phone: ${currentCustomer.phone}` : currentCustomer.customer_code);
        }

        function updateSalesRepDisplay() {}
        function clearSalesRep() { currentSalesRep = null; }
        function selectSalesRep(rep) { currentSalesRep = null; }
        function selectSalesRepByIndex(idx) {}
        function openSalesRepModal() {}
        function performSalesRepSearch(q) {}
        function updateSalesRepHighlight() {}
        function renderSalesRepSearchResults(reps) {}

        // --- REAL-TIME SERVER PRODUCT SEARCH ENGINE ---
        let productSearchTimer = null;
        let activeProductSearchId = 0;

        async function performProductSearch(val) {
            val = val.trim();
            if (!val) {
                searchDropdown.style.display = 'none';
                currentSearchProducts = [];
                selectedSearchIndex = -1;
                return;
            }

            const searchId = ++activeProductSearchId;

            try {
                const res = await fetch(`pos_api.php?action=search_products&q=${encodeURIComponent(val)}`);
                const data = await res.json();
                if (searchId !== activeProductSearchId) return; // Stale response discarded

                let products = [];
                if (data.success && Array.isArray(data.data)) {
                    products = data.data;
                }

                currentSearchProducts = products;
                selectedSearchIndex = products.length > 0 ? 0 : -1;
                renderSearchResults(currentSearchProducts);
            } catch (err) {
                console.error("Product search execution error:", err);
            }
        }

        let hasNavigatedSearchDropdown = false;

        function findExactBarcodeProduct(products, query) {
            if (!products || !Array.isArray(products) || !query) return null;
            const q = String(query).trim().toLowerCase();
            if (!q) return null;

            return products.find(p => {
                const bc = String(p.barcode || '').trim().toLowerCase();
                const sku = String(p.sku || '').trim().toLowerCase();
                return (bc && bc === q) || (sku && sku === q);
            });
        }

        // Paste event listener for direct instant barcode pasting
        searchInput.addEventListener('paste', (e) => {
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            if (pastedText && pastedText.trim().length > 0) {
                e.preventDefault();
                searchInput.value = pastedText.trim();
                handleBarcodeOrSearchSubmit(pastedText.trim());
            }
        });

        searchInput.addEventListener('input', (e) => {
            clearTimeout(productSearchTimer);
            hasNavigatedSearchDropdown = false;
            const val = e.target.value;
            const trimmed = val.trim();
            if (!trimmed) {
                searchDropdown.style.display = 'none';
                currentSearchProducts = [];
                return;
            }

            productSearchTimer = setTimeout(async () => {
                await performProductSearch(trimmed);
                // If scanned or typed value is an exact match for a product barcode or SKU, automatically add immediately!
                if (currentSearchProducts.length > 0) {
                    const exact = findExactBarcodeProduct(currentSearchProducts, trimmed);
                    if (exact) {
                        addToCart(exact);
                        searchInput.value = '';
                        searchDropdown.style.display = 'none';
                        currentSearchProducts = [];
                        selectedSearchIndex = -1;
                        searchInput.focus();
                    }
                }
            }, 40);
        });

        searchInput.addEventListener('focus', () => {
            if (searchInput.value.trim().length > 0) {
                performProductSearch(searchInput.value);
            }
        });

        searchInput.addEventListener('keydown', async (e) => {
            // Guard: If any overlay modal is open, delegate or ignore searchInput key navigation
            const anyActiveModal = document.querySelector('.pos-modal.active');
            if (anyActiveModal) {
                const promoModal = document.getElementById('modal-promotion-confirm');
                if (promoModal && promoModal.classList.contains('active')) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        e.stopPropagation();
                        if (promoChoiceIndex === 1) confirmApplyPromotion();
                        else dismissPromotionPrompt();
                        return;
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        e.stopPropagation();
                        dismissPromotionPrompt();
                        return;
                    } else if (e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'Tab') {
                        e.preventDefault();
                        e.stopPropagation();
                        promoChoiceIndex = (promoChoiceIndex === 1) ? 0 : 1;
                        updatePromoChoiceHighlight();
                        return;
                    }
                }
                return;
            }

            // Tab key navigation from Search Bar to latest item's QTY field (row 0)
            if (e.key === 'Tab' && !e.shiftKey) {
                if (cart.length > 0) {
                    e.preventDefault();
                    const topQty = document.getElementById('cart-item-qty-0');
                    if (topQty) {
                        topQty.focus();
                        topQty.select();
                        return;
                    }
                }
            }

            // + / - shortcuts to adjust latest item's quantity (top item in cart) when search input is empty
            if (cart.length > 0 && searchInput.value.trim() === '') {
                if (e.key === '+' || e.code === 'NumpadAdd' || (e.key === '=' && !e.ctrlKey && !e.altKey)) {
                    e.preventDefault();
                    updateItemQty(0, 1);
                    if (searchInput) searchInput.focus();
                    return;
                }
                if (e.key === '-' || e.code === 'NumpadSubtract') {
                    e.preventDefault();
                    updateItemQty(0, -1);
                    if (searchInput) searchInput.focus();
                    return;
                }
            }

            if (searchDropdown.style.display === 'block' && currentSearchProducts.length > 0) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    e.stopPropagation();
                    hasNavigatedSearchDropdown = true;
                    selectedSearchIndex = (selectedSearchIndex + 1) % currentSearchProducts.length;
                    updateProductSearchHighlight();
                    return;
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    e.stopPropagation();
                    hasNavigatedSearchDropdown = true;
                    selectedSearchIndex = (selectedSearchIndex - 1 + currentSearchProducts.length) % currentSearchProducts.length;
                    updateProductSearchHighlight();
                    return;
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    const idxToSelect = selectedSearchIndex >= 0 ? selectedSearchIndex : 0;
                    if (currentSearchProducts[idxToSelect]) {
                        selectProductFromSearchByIndex(idxToSelect);
                        hasNavigatedSearchDropdown = false;
                        return;
                    }
                }
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                if (Date.now() - lastModalClosedTime < 450) {
                    return;
                }
                clearTimeout(productSearchTimer);
                const val = searchInput.value.trim();
                if (!val) {
                    // Tap Enter on POS screen with empty search input triggers Complete Sale!
                    document.getElementById('btn-proceed-pay').click();
                    return;
                }
                hasNavigatedSearchDropdown = false;
                await handleBarcodeOrSearchSubmit(val);
            }
        });

        async function handleBarcodeOrSearchSubmit(val) {
            val = String(val || '').trim();
            if (!val) return;

            try {
                let products = [];
                const res = await fetch(`pos_api.php?action=search_products&q=${encodeURIComponent(val)}`);
                const data = await res.json();
                if (data.success && Array.isArray(data.data)) {
                    products = data.data;
                }

                if (!products || products.length === 0) {
                    alert(`No product found matching barcode or search term: "${val}"`);
                    searchInput.value = '';
                    searchDropdown.style.display = 'none';
                    currentSearchProducts = [];
                    selectedSearchIndex = -1;
                    searchInput.focus();
                    return;
                }

                // Check for exact barcode or SKU match first
                const exactMatch = findExactBarcodeProduct(products, val);

                if (exactMatch) {
                    addToCart(exactMatch);
                    searchInput.value = '';
                    searchDropdown.style.display = 'none';
                    currentSearchProducts = [];
                    selectedSearchIndex = -1;
                    searchInput.focus();
                } else if (products.length === 1) {
                    addToCart(products[0]);
                    searchInput.value = '';
                    searchDropdown.style.display = 'none';
                    currentSearchProducts = [];
                    selectedSearchIndex = -1;
                    searchInput.focus();
                } else {
                    currentSearchProducts = products;
                    selectedSearchIndex = 0;
                    renderSearchResults(currentSearchProducts);
                }
            } catch (err) {
                console.error("Barcode submit error:", err);
            }
        }

        function updateProductSearchHighlight() {
            const items = searchDropdown.querySelectorAll('.search-item');
            items.forEach((item, idx) => {
                if (idx === selectedSearchIndex) {
                    item.classList.add('selected');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('selected');
                }
            });
        }

        function renderSearchResults(products) {
            if (!products || products.length === 0) {
                searchDropdown.innerHTML = '<div class="search-item" style="color: var(--pos-text-muted); cursor: default;">No active products found matching search.</div>';
                searchDropdown.style.display = 'block';
                return;
            }

            let html = '';
            products.forEach((p, idx) => {
                const totalStock = (p.lots && p.lots.length > 0)
                    ? p.lots.reduce((sum, l) => sum + parseFloat(l.remaining_quantity || 0), 0)
                    : parseFloat(p.total_branch_stock || 0);

                const stockText = totalStock > 0 ? `<span style="color: #047857; font-weight:800;">Stock: ${totalStock}</span>` : `<span style="color: #dc2626; font-weight:800;">Out of Stock</span>`;
                const isSelected = idx === selectedSearchIndex ? 'selected' : '';
                html += `
                    <div class="search-item ${isSelected}" data-index="${idx}" onclick="selectProductFromSearchByIndex(${idx})">
                        <div>
                            <div style="font-weight: 800; color: var(--pos-text); font-size: 1.05rem;">${escapeHtml(p.product_name)}</div>
                            <div style="font-size: 0.8rem; color: var(--pos-text-muted);">BC: ${escapeHtml(p.barcode)} | SKU: ${escapeHtml(p.sku)} | Brand: ${escapeHtml(p.brand_name || 'N/A')}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-family: 'Outfit', sans-serif; font-weight: 900; color: var(--pos-primary); font-size: 1.15rem;">LKR ${parseFloat(p.default_price).toFixed(2)}</div>
                            <div style="font-size: 0.8rem;">${stockText}</div>
                        </div>
                    </div>
                `;
            });

            searchDropdown.innerHTML = html;
            searchDropdown.style.display = 'block';
        }

        function selectProductFromSearchByIndex(index) {
            const product = currentSearchProducts[index];
            if (!product) return;

            searchInput.value = '';
            searchDropdown.style.display = 'none';
            currentSearchProducts = [];
            selectedSearchIndex = -1;

            addToCart(product);

            // Only refocus searchInput if lot selection modal did not open
            const isLotModalOpen = document.getElementById('modal-lot-selector').classList.contains('active');
            if (!isLotModalOpen) {
                searchInput.focus();
            } else {
                searchInput.blur();
            }
        }

        // Hide search dropdown on click outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                searchDropdown.style.display = 'none';
            }
        });

        // Cart Engine
        function addToCart(product) {
            if (!product) return;

            // Filter available lots on branch with positive remaining stock
            const availableLots = (product.lots || []).filter(l => parseFloat(l.remaining_quantity) > 0);

            if (availableLots.length > 1) {
                showAutomaticLotModal(product, availableLots);
                return;
            } else if (availableLots.length === 1) {
                addItemToCartWithLot(product, availableLots[0]);
                return;
            }

            // Fallback for zero-stock product: use attached fallback lot or auto-selected lot
            if (product.lots && product.lots.length > 0) {
                addItemToCartWithLot(product, product.lots[0]);
                return;
            }

            if (product.auto_selected_lot) {
                addItemToCartWithLot(product, product.auto_selected_lot);
                return;
            }

            // Fetch lots from server (backend returns fallback lot for 0-stock products)
            fetch(`pos_api.php?action=get_product_lots&product_id=${product.product_id}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.data && res.data.length > 0) {
                        product.lots = res.data;
                        const posLots = res.data.filter(l => parseFloat(l.remaining_quantity) > 0);
                        if (posLots.length > 1) {
                            showAutomaticLotModal(product, posLots);
                        } else {
                            addItemToCartWithLot(product, res.data[0]);
                        }
                    } else {
                        const fallbackLot = {
                            lot_id: 0,
                            lot_number: 'LOT-AUTO',
                            remaining_quantity: 0,
                            selling_price: parseFloat(product.default_price || 0)
                        };
                        addItemToCartWithLot(product, fallbackLot);
                    }
                })
                .catch(err => {
                    console.error("Error fetching product lots:", err);
                    const fallbackLot = {
                        lot_id: 0,
                        lot_number: 'LOT-AUTO',
                        remaining_quantity: 0,
                        selling_price: parseFloat(product.default_price || 0)
                    };
                    addItemToCartWithLot(product, fallbackLot);
                });
        }

        function showAutomaticLotModal(product, lots) {
            // Strictly show lots that have available stock on branch
            lots = (lots || []).filter(l => parseFloat(l.remaining_quantity) > 0);
            if (lots.length === 0) {
                alert(`Product "${product.product_name}" has no available stock lots in this branch.`);
                return;
            }
            if (lots.length === 1) {
                addItemToCartWithLot(product, lots[0]);
                return;
            }

            selectedLotIndex = 0;
            document.getElementById('lot-modal-title').innerText = `Select Available Lot for "${product.product_name}"`;
            const container = document.getElementById('lot-modal-body');
            
            let html = `
                <div style="font-size:0.85rem; color:var(--pos-text-muted); margin-bottom:1rem; font-weight:600;">
                    Product <strong>${escapeHtml(product.product_name)}</strong> has <strong>${lots.length} available stock lots</strong> in this branch. Please select a lot to add to cart:
                </div>
            `;

            lots.forEach((lot, idx) => {
                const remaining = parseFloat(lot.remaining_quantity);
                const price = parseFloat(lot.selling_price).toFixed(2);
                const lotNum = escapeHtml(lot.lot_number);
                const isSelected = idx === 0 ? 'selected' : '';

                let expBadge = '';
                if (lot.expiry_date) {
                    expBadge = `<div style="margin-top: 4px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <span style="display:inline-flex; align-items:center; gap:4px; font-size:0.75rem; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; padding:2px 8px; border-radius:6px; font-weight:700;"><i class="fa-regular fa-clock"></i> Exp: ${lot.expiry_date}</span>
                        ${lot.batch_number ? `<span style="font-size:0.75rem; background:#f8fafc; color:#475569; border:1px solid #cbd5e1; padding:2px 8px; border-radius:6px; font-weight:700;">Batch: ${escapeHtml(lot.batch_number)}</span>` : ''}
                    </div>`;
                } else if (lot.batch_number) {
                    expBadge = `<div style="margin-top: 4px;"><span style="font-size:0.75rem; background:#f8fafc; color:#475569; border:1px solid #cbd5e1; padding:2px 8px; border-radius:6px; font-weight:700;">Batch: ${escapeHtml(lot.batch_number)}</span></div>`;
                }

                html += `
                    <div class="lot-card ${isSelected}" data-index="${idx}" onclick="confirmLotSelection(${product.product_id}, ${lot.lot_id})" style="border-radius:14px; padding:1.1rem; margin-bottom:0.75rem; border:1px solid #e4e4e7; transition:all 0.2s ease;">
                        <div style="display:flex; align-items:center; gap:0.9rem;">
                            <div style="width:42px; height:42px; border-radius:12px; background:#000000; color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:1.15rem; font-weight:800;">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <div>
                                <div style="font-weight: 800; font-size: 1.05rem; color: var(--pos-text);">${lotNum}</div>
                                <div style="font-size: 0.8rem; color: var(--pos-text-muted); font-weight:600;">
                                    Available Stock: <strong style="color:#047857;">${remaining} units</strong>
                                </div>
                                ${expBadge}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-family: 'Outfit', sans-serif; font-size: 1.35rem; font-weight: 900; color: #000000;">
                                LKR ${price}
                            </div>
                            <span style="display:inline-block; font-size:0.75rem; background:#000000; color:#ffffff; padding:3px 12px; border-radius:9999px; font-weight:800; margin-top:2px;">Select Lot →</span>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
            container.currentProduct = product;
            container.currentLots = lots;

            lastLotModalOpenTime = Date.now();
            openModal('modal-lot-selector');
            if (searchInput) searchInput.blur();
            updateLotCardHighlight();
        }

        function confirmLotSelection(productId, lotId) {
            lastModalClosedTime = Date.now();
            const container = document.getElementById('lot-modal-body');
            const product = container.currentProduct;
            const lots = container.currentLots;

            if (product && lots) {
                const chosenLot = lots.find(l => l.lot_id == lotId);
                if (chosenLot) {
                    addItemToCartWithLot(product, chosenLot);
                }
            }
            closeModal('modal-lot-selector');
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
        }

        function addItemToCartWithLot(product, selectedLot) {
            const maxStock = parseFloat(selectedLot.remaining_quantity) || 0;
            const isZeroStock = (maxStock <= 0);

            const existingIndex = cart.findIndex(i => i.product_id === product.product_id && i.lot_id === selectedLot.lot_id);
            if (existingIndex > -1) {
                const newQty = cart[existingIndex].quantity + 1;
                const existingItem = cart.splice(existingIndex, 1)[0];
                existingItem.quantity = newQty;
                cart.unshift(existingItem);
            } else {
                const itemPrice = (product.lots && product.lots.length > 1 && parseFloat(selectedLot.selling_price) > 0)
                    ? parseFloat(selectedLot.selling_price)
                    : parseFloat(product.default_price || selectedLot.selling_price || 0);

                cart.unshift({
                    product_id: product.product_id,
                    product_name: product.product_name,
                    sku: product.sku || product.barcode,
                    barcode: product.barcode,
                    lot_id: selectedLot.lot_id,
                    lot_number: selectedLot.lot_number,
                    lot_remaining_stock: maxStock,
                    is_zero_stock: isZeroStock,
                    all_lots: product.lots || [selectedLot],
                    quantity: 1.00,
                    original_price: itemPrice,
                    unit_price: itemPrice,
                    discount_value: ''
                });
            }

            renderCart();
            if (searchInput) searchInput.focus();
        }

        function parseDiscountJS(discountStr, baseAmount) {
            discountStr = String(discountStr || '').trim();
            baseAmount = parseFloat(baseAmount) || 0;
            if (!discountStr || baseAmount <= 0) {
                return {
                    type: 'none',
                    raw_value: '',
                    discount_amount: 0.00,
                    roundup_amount: 0.00,
                    total_discount: 0.00,
                    customer_facing_text: ''
                };
            }

            // Check round-up / added amount format e.g. "10%+25" or "10+25"
            if (discountStr.includes('+')) {
                const parts = discountStr.split('+');
                const basePart = parts[0].trim();
                const addedVal = parseFloat(parts[1].trim()) || 0;

                const baseParsed = parseDiscountJS(basePart, baseAmount);
                // 10%+25 means 10% discount and ADD 25 rupees to the bill/item.
                // 25 is not the discount; it adds a rupee value to the bill.
                // Effective net discount deduction = discount_amount - addedVal.
                const netDiscount = Math.round((baseParsed.discount_amount - addedVal) * 100) / 100;

                return {
                    type: 'roundup',
                    raw_value: discountStr,
                    discount_amount: Math.max(0, netDiscount),
                    roundup_amount: addedVal,
                    total_discount: netDiscount,
                    customer_facing_text: baseParsed.customer_facing_text
                };
            }

            // Percentage discount e.g. "10%"
            if (discountStr.endsWith('%')) {
                const pct = parseFloat(discountStr) || 0;
                const discAmount = Math.round((baseAmount * (pct / 100)) * 100) / 100;
                const pctFormatted = (Math.floor(pct) === pct) ? String(pct) : String(pct);
                return {
                    type: 'percentage',
                    raw_value: discountStr,
                    discount_amount: discAmount,
                    roundup_amount: 0.00,
                    total_discount: discAmount,
                    customer_facing_text: `${pctFormatted}%`
                };
            }

            // Fixed numeric discount e.g. "10"
            const fixedVal = parseFloat(discountStr) || 0;
            const discAmount = Math.round(Math.min(fixedVal, baseAmount) * 100) / 100;
            return {
                type: 'fixed',
                raw_value: discountStr,
                discount_amount: discAmount,
                roundup_amount: 0.00,
                total_discount: discAmount,
                customer_facing_text: fixedVal.toFixed(2)
            };
        }

        function buildCartRowHtml(item, index) {

            const itemSubtotal = item.unit_price * item.quantity;
            const parsedItemDisc = parseDiscountJS(item.discount_value, itemSubtotal);
            const itemPromo = parseFloat(item.promo_discount_amount) || 0.00;
            const totalLineDisc = itemPromo + parsedItemDisc.total_discount;
            const lineTotalNum = Math.max(0, itemSubtotal - totalLineDisc);
            const lineTotal = lineTotalNum.toFixed(2);
            item.discount_amount = parsedItemDisc.total_discount;
            const isMultiLot = item.all_lots && item.all_lots.length > 1;

            const promoDiscountBadge = itemPromo > 0 ? `
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 2px; margin-top: 3px;">
                    <span style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; font-size: 10px; font-weight: 800; border-radius: 999px; padding: 1.5px 7px; display: inline-flex; align-items: center; gap: 3px; white-space: nowrap;" title="${escapeHtml(item.promo_name || 'Promo Discount')}">
                        <i class="fa-solid fa-tag fa-2xs"></i> -LKR ${itemPromo.toFixed(2)}
                    </span>
                    ${item.promo_name ? `<span style="font-size: 9.5px; color: #16a34a; font-weight: 700; max-width: 120px; text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(item.promo_name)}">${escapeHtml(item.promo_name)}</span>` : ''}
                </div>
            ` : '';

            const isLowStock = parseFloat(item.lot_remaining_stock) <= 5;

            return `
                <tr>
                    <td style="text-align: center; vertical-align: middle;">
                        <span class="cart-row-idx">${index + 1}</span>
                    </td>
                    <td style="vertical-align: middle;">
                        <div style="font-weight: 800; color: #0f172a; font-size: 0.95rem; line-height: 1.35; letter-spacing: -0.01em;">
                            ${escapeHtml(item.product_name)}
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px; margin-top: 4px; flex-wrap: wrap;">
                            ${item.sku && item.sku !== item.barcode ? `
                                <span class="cart-barcode-pill" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-family: monospace; font-weight: 700;" title="SKU">
                                    <i class="fa-solid fa-tag" style="font-size: 0.68rem; opacity: 0.7;"></i> ${escapeHtml(item.sku)}
                                </span>
                            ` : ''}
                            <span class="cart-barcode-pill" title="Barcode">
                                <i class="fa-solid fa-barcode" style="font-size: 0.68rem; opacity: 0.7;"></i> ${escapeHtml(item.barcode)}
                            </span>
                            <span class="lot-badge-inline" onclick="openLotSelectorModal(${index})" title="${isMultiLot ? 'Click to change stock lot' : 'Active stock lot'}">
                                <i class="fa-solid fa-layer-group"></i> ${escapeHtml(item.lot_number)}
                                ${isMultiLot ? '<i class="fa-solid fa-chevron-down" style="font-size: 0.58rem; margin-left: 2px;"></i>' : ''}
                            </span>
                            ${(item.is_zero_stock || parseFloat(item.lot_remaining_stock) <= 0) ? `
                                <span style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-size: 10px; font-weight: 800; border-radius: 4px; padding: 1.5px 6px; display: inline-flex; align-items: center; gap: 3px;" title="This item has 0 inventory stock and will be automatically replenished upon sale finalization">
                                    <i class="fa-solid fa-bolt"></i> Auto-Stock
                                </span>
                            ` : ''}
                        </div>
                    </td>
                    <td style="text-align: center; vertical-align: middle;">
                        <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 3px;">
                            <div class="qty-controls">
                                <button type="button" class="btn-qty" onclick="updateItemQty(${index}, -1)" title="Decrease Qty">-</button>
                                <input type="number" id="cart-item-qty-${index}" class="qty-input item-qty-input" value="${item.quantity}" min="1" ${(item.is_zero_stock || parseFloat(item.lot_remaining_stock) <= 0) ? '' : `max="${item.lot_remaining_stock}"`} step="1" oninput="validateQtyRealtime(this, ${index})" onchange="setItemQty(${index}, this.value)">
                                <button type="button" class="btn-qty" onclick="updateItemQty(${index}, 1)" title="Increase Qty">+</button>
                            </div>
                            ${(item.is_zero_stock || parseFloat(item.lot_remaining_stock) <= 0) ? `
                                <div class="cart-avail-badge" style="background: #fff1f2; color: #e11d48; border-color: #fecdd3;" title="Stock will be automatically adjusted on invoice save">
                                    <span class="avail-dot" style="background: #e11d48;"></span>
                                    <span>0 avail (Auto)</span>
                                </div>
                            ` : `
                                <div class="cart-avail-badge" title="Available stock in current lot">
                                    <span class="avail-dot" ${isLowStock ? 'style="background: #f59e0b;"' : ''}></span>
                                    <span>${item.lot_remaining_stock} avail</span>
                                </div>
                            `}
                        </div>
                    </td>
                    <td style="text-align: right; vertical-align: middle;">
                        <div style="display: flex; justify-content: flex-end;">
                            <input type="number" class="price-override-input" value="${item.unit_price.toFixed(2)}" step="0.50" 
                                   ${!CAN_OVERRIDE_PRICE ? 'disabled' : ''} onchange="setItemPrice(${index}, this.value)">
                        </div>
                    </td>
                    <td style="text-align: right; vertical-align: middle;">
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 2px;">
                            <input type="text" id="cart-item-disc-${index}" class="item-discount-input" value="${escapeHtml(item.discount_value)}" placeholder="${itemPromo > 0 ? '-' + itemPromo.toFixed(2) : '0 / %'}" 
                                   ${!CAN_DISCOUNT ? 'disabled' : ''} onchange="setItemDiscount(${index}, this.value)">
                            ${promoDiscountBadge}
                        </div>
                    </td>
                    <td style="text-align: right; vertical-align: middle;">
                        <div style="font-family: 'Outfit', sans-serif; font-weight: 900; color: #0f172a; font-size: 1.05rem; white-space: nowrap; letter-spacing: -0.01em;">
                            <span style="font-size: 0.72rem; font-weight: 800; color: #64748b; margin-right: 2px;">LKR</span> ${lineTotal}
                        </div>
                    </td>
                    <td style="text-align: center; vertical-align: middle;">
                        <button type="button" class="btn-cart-delete" onclick="removeFromCart(${index})" title="Remove ${escapeHtml(item.product_name)}">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </td>
                </tr>
            `;
        }

        function renderCart() {
            if (cart.length === 0) {
                cartTableBody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 4rem 1rem; color: var(--pos-text-muted);">
                            <i class="fa-solid fa-cart-shopping fa-3x" style="margin-bottom: 1rem; opacity: 0.2;"></i>
                            <p style="font-size: 1.1rem; font-weight: 800; color:#0f172a;">Your Cart is Empty</p>
                            <p style="font-size: 0.85rem; margin-top: 4px;">Scan a barcode or type a product name to begin billing.</p>
                        </td>
                    </tr>
                `;
                calculateTotals();
                return;
            }

            let html = '';
            cart.forEach((item, index) => {
                html += buildCartRowHtml(item, index);
            });

            cartTableBody.innerHTML = html;
            calculateTotals();
        }

        function renderCartRowsOnly() {
            if (!cartTableBody || cart.length === 0) return;
            let html = '';
            cart.forEach((item, index) => {
                html += buildCartRowHtml(item, index);
            });
            cartTableBody.innerHTML = html;
        }

        function validateQtyRealtime(inputEl, index) {
            const item = cart[index];
            if (!item) return;
            const entered = parseFloat(inputEl.value);
            if (!isNaN(entered) && entered > 0) {
                item.quantity = entered;
                calculateTotals();
            }
        }

        function updateItemQty(index, delta) {
            const item = cart[index];
            if (!item) return;
            const newQty = item.quantity + delta;
            if (newQty <= 0) {
                removeFromCart(index);
                return;
            }
            item.quantity = newQty;
            renderCart();
        }

        function setItemQty(index, value) {
            const item = cart[index];
            if (!item) return;
            const qty = parseFloat(value);
            if (isNaN(qty) || qty <= 0) {
                removeFromCart(index);
                return;
            }
            item.quantity = qty;
            renderCart();
        }

        function setItemPrice(index, value) {
            const price = parseFloat(value);
            if (isNaN(price) || price < 0) {
                alert("Invalid unit price.");
                return;
            }
            cart[index].unit_price = price;
            renderCart();
        }

        function setItemDiscount(index, value) {
            cart[index].discount_value = value.trim();
            renderCart();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            renderCart();
        }

        let currentPayableTotal = 0.00;
        let currentSubtotal = 0.00;
        let currentTotalDiscount = 0.00;
        let currentPromoDiscount = 0.00;
        let activeCouponCode = '';
        let currentAppliedPromotions = [];
        let promoEvalTimer = null;

        // Promotion Confirmation & Dismissal State
        let confirmedPromoKey = null;
        let declinedPromoKey = null;
        let pendingPromotionData = null;
        let pendingPromoKey = null;
        let promoChoiceIndex = 1; // 0 = Cancel, 1 = Apply
        let lastPromoModalOpenTime = 0;

        function updatePromoChoiceHighlight() {
            const btnApply = document.getElementById('btn-promo-apply');
            const btnCancel = document.getElementById('btn-promo-cancel');
            if (promoChoiceIndex === 1) {
                if (btnApply) {
                    btnApply.style.border = '2px solid #38bdf8';
                    btnApply.style.boxShadow = '0 0 0 3px rgba(56, 189, 248, 0.35)';
                    try { btnApply.focus(); } catch (e) {}
                }
                if (btnCancel) {
                    btnCancel.style.border = '1.5px solid #cbd5e1';
                    btnCancel.style.boxShadow = 'none';
                }
            } else {
                if (btnCancel) {
                    btnCancel.style.border = '2px solid #dc2626';
                    btnCancel.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.2)';
                    try { btnCancel.focus(); } catch (e) {}
                }
                if (btnApply) {
                    btnApply.style.border = 'none';
                    btnApply.style.boxShadow = '0 4px 12px rgba(15, 23, 42, 0.2)';
                }
            }
        }

        function handlePromoButtonKey(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                if (promoChoiceIndex === 1) {
                    confirmApplyPromotion();
                } else {
                    dismissPromotionPrompt();
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                dismissPromotionPrompt();
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'Tab') {
                e.preventDefault();
                e.stopPropagation();
                promoChoiceIndex = (promoChoiceIndex === 1) ? 0 : 1;
                updatePromoChoiceHighlight();
            }
        }

        function updatePromoSummaryUI(promoDiscount, totalSavings, promotions, isAvailableNotApplied = false) {
            const promoRow = document.getElementById('summary-promo-row');
            const promoVal = document.getElementById('summary-promo-val');
            const promoAvailableRow = document.getElementById('summary-promo-available-row');
            const promoAvailableText = document.getElementById('summary-promo-available-text');
            const savingsRow = document.getElementById('summary-savings-row');
            const savingsVal = document.getElementById('summary-savings-val');
            const badgeContainer = document.getElementById('applied-promos-container');
            const badgeList = document.getElementById('applied-promos-badges');

            if (promoDiscount > 0) {
                if (promoRow) promoRow.style.display = 'flex';
                if (promoVal) promoVal.innerText = `- LKR ${promoDiscount.toFixed(2)}`;
                if (promoAvailableRow) promoAvailableRow.style.display = 'none';
            } else {
                if (promoRow) promoRow.style.display = 'none';
                if (isAvailableNotApplied && totalSavings > 0) {
                    if (promoAvailableRow) promoAvailableRow.style.display = 'flex';
                    if (promoAvailableText) promoAvailableText.innerHTML = `<i class="fa-solid fa-gift"></i> Promo Available (Save LKR ${totalSavings.toFixed(2)})`;
                } else {
                    if (promoAvailableRow) promoAvailableRow.style.display = 'none';
                }
            }

            if (totalSavings > 0 && promoDiscount > 0) {
                if (savingsRow) savingsRow.style.display = 'flex';
                if (savingsVal) savingsVal.innerText = `LKR ${totalSavings.toFixed(2)}`;
            } else {
                if (savingsRow) savingsRow.style.display = 'none';
            }

            if (badgeContainer && badgeList) {
                if (promoDiscount > 0 && promotions && promotions.length > 0) {
                    badgeContainer.style.display = 'block';
                    badgeList.innerHTML = promotions.map(p => `
                        <span style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #0f172a; font-size: 10px; font-weight: 700; border-radius: 999px; padding: 2px 8px; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fa-solid fa-sparkles fa-xs" style="color: #0284c7;"></i> ${escapeHtml(p.name)}: -LKR ${parseFloat(p.total_savings).toFixed(2)}
                        </span>
                    `).join('');
                } else {
                    badgeContainer.style.display = 'none';
                    badgeList.innerHTML = '';
                }
            }
        }

        function applyCouponCode() {
            const input = document.getElementById('summary-coupon-input');
            const code = input ? input.value.trim().toUpperCase() : '';
            if (!code) {
                activeCouponCode = '';
                calculateTotals();
                return;
            }
            fetch(`pos_api.php?action=validate_coupon&coupon_code=${encodeURIComponent(code)}&customer_id=${currentCustomer ? currentCustomer.id : 1}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data && res.data.valid) {
                        activeCouponCode = code;
                        calculateTotals();
                    } else {
                        alert(res.message || (res.data ? res.data.message : 'Invalid or expired coupon code.'));
                        if (input) input.value = activeCouponCode;
                    }
                })
                .catch(err => {
                    alert("Coupon validation error: " + err.message);
                });
        }

        function evaluatePromotionsRealtime() {
            if (cart.length === 0) {
                currentAppliedPromotions = [];
                currentPromoDiscount = 0;
                pendingPromotionData = null;
                confirmedPromoKey = null;
                declinedPromoKey = null;
                cart.forEach(it => {
                    it.promo_discount_amount = 0;
                    it.applied_rule_id = null;
                    it.promo_name = null;
                });
                closeModal('modal-promotion-confirm');
                updatePromoSummaryUI(0, 0, []);
                calculateTotalsOnly();
                return;
            }

            const payload = {
                action: 'evaluate_discounts',
                items: cart.map(it => ({
                    product_id: it.product_id,
                    lot_id: it.lot_id,
                    quantity: it.quantity,
                    original_price: it.original_price || it.unit_price,
                    unit_price: it.unit_price,
                    discount_value: it.discount_value
                })),
                customer_id: currentCustomer ? currentCustomer.id : 1,
                coupon_code: activeCouponCode,
                manual_invoice_discount: summaryDiscountInput.value.trim()
            };

            fetch('pos_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    const data = res.data;
                    const promos = data.applied_promotions || [];
                    const promoDiscount = parseFloat(data.total_promo_discount) || 0.00;

                    if (promos.length === 0 || promoDiscount <= 0) {
                        currentAppliedPromotions = [];
                        currentPromoDiscount = 0;
                        pendingPromotionData = null;
                        let itemsUpdated = false;
                        cart.forEach(it => {
                            if (it.promo_discount_amount > 0 || it.applied_rule_id) {
                                it.promo_discount_amount = 0;
                                it.applied_rule_id = null;
                                it.promo_name = null;
                                itemsUpdated = true;
                            }
                        });
                        if (itemsUpdated) renderCartRowsOnly();
                        updatePromoSummaryUI(0, 0, []);
                        calculateTotalsOnly();
                        return;
                    }

                    // Key promotions by rule identity AND the set of eligible products/lots
                    // Adding another product eligible for promotion discount prompts the cashier again
                    const promoItemSignatures = [];
                    let hasInvoicePromo = false;
                    if (data.cart_items && Array.isArray(data.cart_items)) {
                        data.cart_items.forEach(ci => {
                            if ((ci.promo_discount && parseFloat(ci.promo_discount) > 0.001) || ci.applied_rule_id) {
                                promoItemSignatures.push(`${ci.product_id}_${ci.lot_id || 0}`);
                            }
                        });
                    }
                    if (promos.some(p => p.rule_type === 'invoice')) {
                        hasInvoicePromo = true;
                    }
                    promoItemSignatures.sort();
                    const promoRulesKey = promos.map(p => String(p.rule_id || p.rule_code || p.name)).sort().join('|');
                    const promoKey = `${promoRulesKey}::items[${promoItemSignatures.join(',')}]${hasInvoicePromo ? '::inv' : ''}`;

                    if (confirmedPromoKey === promoKey) {
                        // Confirmed by cashier: automatically update and apply to cart for increased/decreased QTY
                        pendingPromotionData = data;
                        pendingPromoKey = promoKey;
                        applyPromotionDataToCart(data);
                    } else if (declinedPromoKey === promoKey) {
                        // Declined by cashier: DO NOT re-prompt, DO NOT overwrite previously confirmed discounts on items
                        pendingPromotionData = data;
                        pendingPromoKey = promoKey;
                        let activePromoTotal = 0.00;
                        cart.forEach(it => {
                            activePromoTotal += (parseFloat(it.promo_discount_amount) || 0.00);
                        });
                        currentPromoDiscount = activePromoTotal;
                        const totalAvailableSavings = parseFloat(data.total_savings) || 0;
                        updatePromoSummaryUI(currentPromoDiscount, totalAvailableSavings, currentAppliedPromotions, currentPromoDiscount < totalAvailableSavings);
                        calculateTotalsOnly();
                    } else {
                        // NEW ELIGIBLE PROMOTION: Prompt cashier once to confirm or cancel
                        pendingPromotionData = data;
                        pendingPromoKey = promoKey;
                        showPromotionPromptModal(data);
                    }
                }
            })
            .catch(err => console.error("Promotion evaluation error", err));
        }

        function showPromotionPromptModal(data) {
            const promos = data.applied_promotions || [];
            if (promos.length === 0) return;

            const primaryPromo = promos[0];
            const titleEl = document.getElementById('promo-modal-title');
            const savingsEl = document.getElementById('promo-modal-savings-badge');
            const scopeEl = document.getElementById('promo-modal-scope');
            const descEl = document.getElementById('promo-modal-desc');

            const totalSavings = parseFloat(data.total_savings || data.total_promo_discount || 0);

            if (titleEl) {
                if (promos.length === 1) {
                    titleEl.textContent = primaryPromo.name || 'Special Promotion';
                } else {
                    titleEl.textContent = `${promos.length} Promotions Available`;
                }
            }

            if (savingsEl) {
                savingsEl.textContent = `Save LKR ${totalSavings.toFixed(2)}`;
            }

            if (scopeEl) {
                scopeEl.textContent = promos.map(p => p.name).join(' + ');
            }

            if (descEl) {
                const count = promos.reduce((sum, p) => sum + (p.items_count || 1), 0);
                descEl.textContent = `Eligible for ${count} item(s) in cart. Applying will save LKR ${totalSavings.toFixed(2)}.`;
            }

            const modalEl = document.getElementById('modal-promotion-confirm');
            const isAlreadyOpen = modalEl && modalEl.classList.contains('active');
            if (!isAlreadyOpen) {
                promoChoiceIndex = 1; // Default to Apply
                lastPromoModalOpenTime = Date.now();
                openModal('modal-promotion-confirm');
                if (searchInput) searchInput.blur();
                setTimeout(() => {
                    updatePromoChoiceHighlight();
                }, 60);
            }
        }

        function confirmApplyPromotion() {
            if (!pendingPromotionData) {
                closeModal('modal-promotion-confirm');
                lastModalClosedTime = Date.now();
                return;
            }
            lastModalClosedTime = Date.now();
            confirmedPromoKey = pendingPromoKey;
            declinedPromoKey = null;
            applyPromotionDataToCart(pendingPromotionData);
            closeModal('modal-promotion-confirm');
            setTimeout(() => {
                if (searchInput && !document.querySelector('.pos-modal.active')) {
                    searchInput.focus();
                }
            }, 200);
        }

        function dismissPromotionPrompt() {
            lastModalClosedTime = Date.now();
            declinedPromoKey = pendingPromoKey;
            closeModal('modal-promotion-confirm');

            // Retain discounts on items that already had confirmed promotional discounts applied
            let activePromoTotal = 0.00;
            cart.forEach(it => {
                activePromoTotal += (parseFloat(it.promo_discount_amount) || 0.00);
            });
            currentPromoDiscount = activePromoTotal;

            if (currentPromoDiscount <= 0.001) {
                confirmedPromoKey = null;
                currentAppliedPromotions = [];
            }

            renderCartRowsOnly();
            const totalAvailableSavings = pendingPromotionData ? (parseFloat(pendingPromotionData.total_savings) || 0) : 0;
            updatePromoSummaryUI(currentPromoDiscount, totalAvailableSavings, currentAppliedPromotions, currentPromoDiscount < totalAvailableSavings);
            calculateTotalsOnly();
            setTimeout(() => {
                if (searchInput && !document.querySelector('.pos-modal.active')) {
                    searchInput.focus();
                }
            }, 200);
        }

        function promptPromotionModal() {
            if (pendingPromotionData) {
                showPromotionPromptModal(pendingPromotionData);
            }
        }

        function cancelAppliedPromotion() {
            if (!confirm("Remove promotional discount from this bill?")) return;
            declinedPromoKey = confirmedPromoKey;
            confirmedPromoKey = null;
            currentAppliedPromotions = [];
            currentPromoDiscount = 0;
            cart.forEach(it => {
                it.promo_discount_amount = 0;
                it.applied_rule_id = null;
                it.promo_name = null;
            });
            renderCartRowsOnly();
            updatePromoSummaryUI(0, pendingPromotionData ? (parseFloat(pendingPromotionData.total_savings) || 0) : 0, [], true);
            calculateTotalsOnly();
            searchInput.focus();
        }

        function applyPromotionDataToCart(data) {
            currentAppliedPromotions = data.applied_promotions || [];
            currentPromoDiscount = parseFloat(data.total_promo_discount) || 0.00;

            let itemsUpdated = false;
            if (data.cart_items && Array.isArray(data.cart_items)) {
                data.cart_items.forEach((evItem, idx) => {
                    if (cart[idx]) {
                        const promoAmt = parseFloat(evItem.promo_discount) || 0;
                        const rId = evItem.applied_rule_id || null;
                        const pName = evItem.applied_promo_name || null;
                        if (cart[idx].promo_discount_amount !== promoAmt || cart[idx].applied_rule_id !== rId) {
                            cart[idx].promo_discount_amount = promoAmt;
                            cart[idx].applied_rule_id = rId;
                            cart[idx].promo_name = pName;
                            itemsUpdated = true;
                        }
                    }
                });
            }

            if (itemsUpdated) {
                renderCartRowsOnly();
            }

            updatePromoSummaryUI(currentPromoDiscount, parseFloat(data.total_savings) || 0, currentAppliedPromotions, false);

            if (data.final_payable !== undefined) {
                currentPayableTotal = parseFloat(data.final_payable);
                summaryPayable.innerText = `LKR ${currentPayableTotal.toFixed(2)}`;
            }

            const allDiscounts = currentPromoDiscount + currentTotalDiscount;
            summaryDiscountVal.innerText = `- LKR ${allDiscounts.toFixed(2)}`;
        }

        function calculateTotalsOnly() {
            let subtotal = 0.00;
            let eligibleSubtotal = 0.00;
            cart.forEach(item => {
                const itemSubtotal = item.unit_price * item.quantity;
                const parsedItemDisc = parseDiscountJS(item.discount_value, itemSubtotal);
                const itemPromo = parseFloat(item.promo_discount_amount) || 0.00;
                const lineTotal = Math.max(0, itemSubtotal - parsedItemDisc.total_discount - itemPromo);
                subtotal += lineTotal;

                // Rule: If item wise discount added inline from cart, do not include total bill discount for that item!
                const hasItemDisc = (parsedItemDisc.total_discount > 0 || (String(item.discount_value || '').trim() !== ''));
                if (!hasItemDisc) {
                    eligibleSubtotal += lineTotal;
                }
            });

            currentSubtotal = subtotal;
            summarySubtotal.innerText = `LKR ${subtotal.toFixed(2)}`;

            const invDiscountStr = summaryDiscountInput.value.trim();
            const parsedInvDisc = parseDiscountJS(invDiscountStr, eligibleSubtotal);
            currentTotalDiscount = parsedInvDisc.total_discount;
            currentPayableTotal = Math.max(0, subtotal - currentTotalDiscount);

            const allDiscounts = currentPromoDiscount + currentTotalDiscount;
            summaryDiscountVal.innerText = (allDiscounts >= 0 ? `- LKR ` : `+ LKR `) + Math.abs(allDiscounts).toFixed(2);
            summaryPayable.innerText = `LKR ${currentPayableTotal.toFixed(2)}`;
        }

        function calculateTotals() {
            let subtotal = 0.00;
            let eligibleSubtotal = 0.00;
            cart.forEach(item => {
                const itemSubtotal = item.unit_price * item.quantity;
                const parsedItemDisc = parseDiscountJS(item.discount_value, itemSubtotal);
                const itemPromo = parseFloat(item.promo_discount_amount) || 0.00;
                const lineTotal = Math.max(0, itemSubtotal - parsedItemDisc.total_discount - itemPromo);
                subtotal += lineTotal;

                // Rule: If item wise discount added inline from cart, do not include total bill discount for that item!
                const hasItemDisc = (parsedItemDisc.total_discount > 0 || (String(item.discount_value || '').trim() !== ''));
                if (!hasItemDisc) {
                    eligibleSubtotal += lineTotal;
                }
            });

            currentSubtotal = subtotal;
            summarySubtotal.innerText = `LKR ${subtotal.toFixed(2)}`;

            const invDiscountStr = summaryDiscountInput.value.trim();
            const parsedInvDisc = parseDiscountJS(invDiscountStr, eligibleSubtotal);
            currentTotalDiscount = parsedInvDisc.total_discount;
            currentPayableTotal = Math.max(0, subtotal - currentTotalDiscount);

            const allDiscounts = currentPromoDiscount + currentTotalDiscount;
            summaryDiscountVal.innerText = (allDiscounts >= 0 ? `- LKR ` : `+ LKR `) + Math.abs(allDiscounts).toFixed(2);
            summaryPayable.innerText = `LKR ${currentPayableTotal.toFixed(2)}`;

            if (promoEvalTimer) clearTimeout(promoEvalTimer);
            promoEvalTimer = setTimeout(evaluatePromotionsRealtime, 200);

            if (typeof discountParseTimer !== 'undefined' && discountParseTimer) clearTimeout(discountParseTimer);
            discountParseTimer = setTimeout(() => {
                fetch(`pos_api.php?action=parse_discount&discount=${encodeURIComponent(invDiscountStr)}&subtotal=${subtotal}&eligible_subtotal=${eligibleSubtotal}`)
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            currentTotalDiscount = res.parsed.total_discount;
                            currentPayableTotal = Math.max(0, subtotal - currentTotalDiscount);
                            
                            const allDiscs = currentPromoDiscount + currentTotalDiscount;
                            summaryDiscountVal.innerText = (allDiscs >= 0 ? `- LKR ` : `+ LKR `) + Math.abs(allDiscs).toFixed(2);
                            summaryPayable.innerText = `LKR ${currentPayableTotal.toFixed(2)}`;

                            if (res.suggestion) {
                                roundupContainer.style.display = 'block';
                                roundupText.innerText = `Add +LKR ${res.suggestion.suggested_roundup.toFixed(2)} to round total to LKR ${res.suggestion.suggested_total.toFixed(2)}`;
                                btnApplyRoundup.onclick = () => {
                                    const currentBase = invDiscountStr.includes('+') ? invDiscountStr.split('+')[0] : (invDiscountStr || '0');
                                    summaryDiscountInput.value = `${currentBase}+${res.suggestion.suggested_roundup.toFixed(2)}`;
                                    calculateTotals();
                                };
                            } else {
                                roundupContainer.style.display = 'none';
                            }
                        }
                    })
                    .catch(err => console.error("Error calculating discount", err));
            }, 150);
        }

        summaryDiscountInput.addEventListener('input', () => {
            calculateTotals();
        });

        // Lot Selector Modal Logic
        function openLotSelectorModal(index) {
            selectedLotIndex = 0;
            pendingLotItemIndex = index;
            const item = cart[index];
            document.getElementById('lot-modal-title').innerText = `Select Lot for ${item.product_name}`;

            fetch(`pos_api.php?action=get_product_lots&product_id=${item.product_id}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.data) {
                        const availableLots = res.data.length > 0 ? res.data : [];
                        if (availableLots.length === 0) {
                            alert(`No stock lots found for "${item.product_name}" in this branch.`);
                            return;
                        }
                        let html = '';
                        availableLots.forEach((lot, idx) => {
                            const isSelected = idx === 0 ? 'selected' : '';
                            const lotRem = parseFloat(lot.remaining_quantity || 0);
                            html += `
                                <div class="lot-card ${isSelected}" data-index="${idx}" onclick="selectLotForCartItem(${lot.lot_id}, '${escapeHtml(lot.lot_number)}', ${parseFloat(lot.selling_price)}, ${lotRem})">
                                    <div>
                                        <div style="font-weight: 800; color: var(--pos-text);">${escapeHtml(lot.lot_number)}</div>
                                        <div style="font-size: 0.8rem; color: var(--pos-text-muted);">
                                            ${lotRem > 0 ? `Available Stock: <strong style="color:#047857;">${lotRem} units</strong>` : `<span style="color:#dc2626; font-weight:800;">0 Stock (Auto-Stock)</span>`}
                                        </div>
                                    </div>
                                    <div style="font-family: 'Outfit', sans-serif; font-weight: 900; color: #047857;">
                                        LKR ${parseFloat(lot.selling_price).toFixed(2)}
                                    </div>
                                </div>
                            `;
                        });
                        document.getElementById('lot-modal-body').innerHTML = html;
                        openModal('modal-lot-selector');
                    }
                })
                .catch(err => console.error("Error fetching product lots", err));
        }

        function updateLotCardHighlight() {
            const cards = document.querySelectorAll('#lot-modal-body .lot-card');
            cards.forEach((card, idx) => {
                if (idx === selectedLotIndex) {
                    card.classList.add('selected');
                    card.scrollIntoView({ block: 'nearest' });
                } else {
                    card.classList.remove('selected');
                }
            });
        }

        function selectLotForCartItem(lotId, lotNumber, sellingPrice, remainingStock) {
            if (pendingLotItemIndex !== null && cart[pendingLotItemIndex]) {
                cart[pendingLotItemIndex].lot_id = lotId;
                cart[pendingLotItemIndex].lot_number = lotNumber;
                cart[pendingLotItemIndex].unit_price = sellingPrice;
                cart[pendingLotItemIndex].original_price = sellingPrice;
                cart[pendingLotItemIndex].lot_remaining_stock = remainingStock;
                renderCart();
            }
            closeModal('modal-lot-selector');
        }

        // Hold / Park Invoice
        document.getElementById('btn-hold-sale').addEventListener('click', holdSale);

        function holdSale() {
            if (cart.length === 0) {
                alert("Cannot hold an empty cart.");
                return;
            }

            const payload = {
                action: 'hold_invoice',
                sale_id: activeSaleId,
                customer_id: currentCustomer.id,
                sales_rep_id: currentSalesRep ? currentSalesRep.id : null,
                discount_value: summaryDiscountInput.value.trim(),
                held_notes: 'Parked by cashier',
                items: cart.map(it => ({
                    product_id: it.product_id,
                    lot_id: it.lot_id,
                    quantity: it.quantity,
                    unit_price: it.unit_price,
                    original_price: it.original_price || it.unit_price,
                    discount_value: it.discount_value,
                    promo_discount_amount: parseFloat(it.promo_discount_amount) || 0,
                    applied_rule_id: it.applied_rule_id || null,
                    promo_name: it.promo_name || null
                }))
            };

            fetch('pos_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    clearCartState();
                } else {
                    alert("Failed to hold invoice: " + res.message);
                }
            })
            .catch(err => console.error("Error holding invoice", err));
        }

        // View Held Invoices
        document.getElementById('btn-view-held').addEventListener('click', openHeldModal);

        function openHeldModal() {
            fetch('pos_api.php?action=get_held_invoices')
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.data) {
                        let html = '';
                        if (res.data.length === 0) {
                            html = '<div style="text-align: center; color: var(--pos-text-muted); padding: 2rem;">No held/parked invoices found for this branch.</div>';
                        } else {
                            res.data.forEach(h => {
                                const repInfo = h.sales_rep_name ? ` | Rep: ${escapeHtml(h.sales_rep_name)}` : '';
                                html += `
                                    <div class="lot-card">
                                        <div>
                                            <div style="font-weight: 800; color: var(--pos-text);">Invoice #${escapeHtml(h.invoice_no)} (${h.customer_name})</div>
                                            <div style="font-size: 0.8rem; color: var(--pos-text-muted);">Held at: ${h.held_at} | Items: ${h.item_count}${repInfo}</div>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="font-family: 'Outfit', sans-serif; font-weight: 900; color: var(--pos-primary);">LKR ${parseFloat(h.total_amount).toFixed(2)}</span>
                                            <button type="button" class="btn-header-action" onclick="resumeHeldInvoice(${h.sale_id})">
                                                <i class="fa-solid fa-play"></i> Resume
                                            </button>
                                        </div>
                                    </div>
                                `;
                            });
                        }
                        document.getElementById('held-modal-body').innerHTML = html;
                        openModal('modal-held-invoices');
                    }
                })
                .catch(err => console.error("Error loading held invoices", err));
        }

        function resumeHeldInvoice(saleId) {
            fetch(`pos_api.php?action=resume_invoice&sale_id=${saleId}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.data) {
                        const s = res.data;
                        activeSaleId = s.id;
                        currentCustomer = { id: s.customer_id, name: s.customer_name, card_barcode: s.customer_card_barcode, phone: s.customer_phone, is_walkin: s.customer_id == 1 ? 1 : 0 };
                        updateCustomerDisplay();

                        if (s.sales_rep_id) {
                            currentSalesRep = { id: parseInt(s.sales_rep_id), full_name: s.sales_rep_name, employee_code: s.sales_rep_code };
                        } else {
                            currentSalesRep = null;
                        }
                        updateSalesRepDisplay();

                        summaryDiscountInput.value = s.discount_value || '';

                        cart = s.items.map(item => ({
                            product_id: parseInt(item.product_id),
                            product_name: item.product_name,
                            sku: item.sku || item.barcode,
                            barcode: item.barcode,
                            lot_id: parseInt(item.lot_id),
                            lot_number: item.lot_number,
                            lot_remaining_stock: parseFloat(item.lot_remaining_stock || 999999),
                            quantity: parseFloat(item.quantity),
                            original_price: parseFloat(item.original_price),
                            unit_price: parseFloat(item.unit_price),
                            discount_value: item.discount_value || ''
                        }));

                        renderCart();
                        closeModal('modal-held-invoices');
                    } else {
                        alert(res.message);
                    }
                })
                .catch(err => console.error("Error resuming held invoice", err));
        }

        // ==========================================
        // SALES HISTORY & RECEIPT REPRINT CONTROLLER
        // ==========================================
        let currentHistPage = 1;
        let currentHistPreset = 'today';
        let currentHistSearch = '';
        let currentHistTotalPages = 1;
        let histSearchTimer = null;

        function openSalesHistoryModal() {
            openModal('modal-sales-history');
            const searchInput = document.getElementById('hist-search-input');
            if (searchInput) {
                searchInput.value = '';
                currentHistSearch = '';
            }
            setSalesHistoryPreset('today', false);
            loadSalesHistory(1);
        }

        function closeSalesHistoryModal() {
            closeModal('modal-sales-history');
            lastModalClosedTime = Date.now();
            setTimeout(() => {
                if (searchInput) searchInput.focus();
            }, 100);
        }

        function setSalesHistoryPreset(preset, autoLoad = true) {
            currentHistPreset = preset;
            const presets = ['today', 'yesterday', 'week', 'all'];
            presets.forEach(p => {
                const btn = document.getElementById('btn-hist-preset-' + p);
                if (btn) {
                    if (p === preset) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                }
            });
            if (autoLoad) {
                loadSalesHistory(1);
            }
        }

        function debounceHistorySearch() {
            if (histSearchTimer) clearTimeout(histSearchTimer);
            histSearchTimer = setTimeout(() => {
                const input = document.getElementById('hist-search-input');
                currentHistSearch = input ? input.value.trim() : '';
                loadSalesHistory(1);
            }, 300);
        }

        function loadSalesHistory(page = 1) {
            currentHistPage = page;
            const tbody = document.getElementById('sales-history-tbody');
            if (!tbody) return;

            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2.5rem; color: #64748b;">
                        <i class="fa-solid fa-spinner fa-spin me-2" style="font-size: 1.2rem; color: #0284c7;"></i> Loading sales records...
                    </td>
                </tr>
            `;

            const url = `pos_api.php?action=get_sales_history&page=${page}&date_preset=${encodeURIComponent(currentHistPreset)}&search=${encodeURIComponent(currentHistSearch)}`;
            fetch(url)
                .then(res => res.json())
                .then(res => {
                    if (!res.success || !res.data) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2.5rem; color: #dc2626;">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i> Failed to retrieve sales history.
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    const items = res.data;
                    const pag = res.pagination || {};
                    currentHistTotalPages = pag.totalPages || 1;

                    // Update pagination UI
                    const pageInfo = document.getElementById('hist-page-info');
                    const pageNum = document.getElementById('hist-page-num');
                    const prevBtn = document.getElementById('btn-hist-prev');
                    const nextBtn = document.getElementById('btn-hist-next');

                    const start = pag.totalItems > 0 ? (pag.page - 1) * pag.limit + 1 : 0;
                    const end = Math.min(pag.page * pag.limit, pag.totalItems);
                    if (pageInfo) pageInfo.innerText = `Showing ${start} to ${end} of ${pag.totalItems} sales`;
                    if (pageNum) pageNum.innerText = `${pag.page} / ${currentHistTotalPages}`;
                    if (prevBtn) prevBtn.disabled = (pag.page <= 1);
                    if (nextBtn) nextBtn.disabled = (pag.page >= currentHistTotalPages);

                    if (items.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 3rem 1rem; color: #64748b;">
                                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem auto; color: #94a3b8; font-size: 1.25rem;">
                                        <i class="fa-solid fa-receipt"></i>
                                    </div>
                                    <div style="font-weight: 700; color: #334155; margin-bottom: 2px;">No sales found</div>
                                    <div style="font-size: 0.8rem; color: #94a3b8;">No matching transactions found for the selected period.</div>
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    let html = '';
                    items.forEach(sale => {
                        const totalAmt = parseFloat(sale.total_amount) || 0;
                        const itemsCount = parseFloat(sale.total_items) || 0;
                        const isCompleted = (sale.status === 'completed');
                        const isCancelled = (sale.status === 'cancelled');
                        
                        let statusBadge = '';
                        if (isCompleted) {
                            statusBadge = `<span style="display: inline-flex; align-items: center; gap: 5px; height: 24px; padding: 0 9px; border-radius: 999px; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 800;"><span style="width: 6px; height: 6px; border-radius: 50%; background: #16a34a;"></span>Completed</span>`;
                        } else if (isCancelled) {
                            statusBadge = `<span style="display: inline-flex; align-items: center; gap: 5px; height: 24px; padding: 0 9px; border-radius: 999px; background: #fee2e2; color: #b91c1c; font-size: 11px; font-weight: 800;"><span style="width: 6px; height: 6px; border-radius: 50%; background: #dc2626;"></span>Cancelled</span>`;
                        } else {
                            statusBadge = `<span style="display: inline-flex; align-items: center; gap: 5px; height: 24px; padding: 0 9px; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 800;"><span style="width: 6px; height: 6px; border-radius: 50%; background: #d97706;"></span>${escapeHtml(sale.status)}</span>`;
                        }

                        const pmtMethod = sale.payment_methods || 'Cash';
                        const custPhone = sale.customer_phone ? `<span style="color: #64748b; font-size: 0.76rem; font-weight: 500;"> (${escapeHtml(sale.customer_phone)})</span>` : '';

                        html += `
                            <tr onclick="viewSaleDetails(${sale.id})">
                                <td style="font-weight: 800; color: #0284c7; white-space: nowrap;">
                                    <i class="fa-solid fa-receipt me-1" style="font-size: 0.8rem; opacity: 0.7;"></i>${escapeHtml(sale.invoice_no)}
                                </td>
                                <td style="white-space: nowrap; color: #475569; font-size: 0.78rem;">
                                    ${escapeHtml(sale.created_at)}
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a; max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        ${escapeHtml(sale.customer_name)}${custPhone}
                                    </div>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #334155;">
                                    ${itemsCount}
                                </td>
                                <td style="text-align: right; font-weight: 900; font-family: 'Outfit', sans-serif; color: #0f172a; white-space: nowrap;">
                                    ${totalAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                </td>
                                <td style="text-align: center;">
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 6px; background: #f1f5f9; color: #334155; font-size: 0.74rem; font-weight: 700; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        ${escapeHtml(pmtMethod)}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    ${statusBadge}
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <button type="button" class="btn-hist-print" onclick="event.stopPropagation(); printSaleReceipt(${sale.id});" title="Print 80mm Thermal Receipt">
                                            <i class="fa-solid fa-print"></i> Print
                                        </button>
                                        <button type="button" class="btn-hist-view" onclick="event.stopPropagation(); viewSaleDetails(${sale.id});" title="View Sale Items & Payment Breakdown">
                                            <i class="fa-solid fa-eye"></i> View
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });

                    tbody.innerHTML = html;
                })
                .catch(err => {
                    console.error("Error loading sales history:", err);
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: #dc2626;">
                                Error communicating with POS server.
                            </td>
                        </tr>
                    `;
                });
        }

        function prevSalesHistoryPage() {
            if (currentHistPage > 1) {
                loadSalesHistory(currentHistPage - 1);
            }
        }

        function nextSalesHistoryPage() {
            if (currentHistPage < currentHistTotalPages) {
                loadSalesHistory(currentHistPage + 1);
            }
        }

        function printSaleReceipt(saleId) {
            if (!saleId) return;
            const url = `receipt.php?sale_id=${saleId}&reprint=1`;
            window.open(url, '_blank', 'width=400,height=650');
        }

        function viewSaleDetails(saleId) {
            if (!saleId) return;
            const content = document.getElementById('sale-details-content');
            const title = document.getElementById('detail-invoice-title');
            const printBtn = document.getElementById('btn-detail-print');

            if (content) {
                content.innerHTML = `
                    <div style="text-align: center; padding: 3rem; color: #64748b;">
                        <i class="fa-solid fa-spinner fa-spin me-2" style="font-size: 1.3rem; color: #0284c7;"></i> Loading sale details...
                    </div>
                `;
            }
            if (title) title.innerText = 'Invoice Details';
            if (printBtn) {
                printBtn.onclick = () => printSaleReceipt(saleId);
            }
            openModal('modal-sale-details');

            fetch(`pos_api.php?action=get_sale_details&sale_id=${saleId}`)
                .then(res => res.json())
                .then(res => {
                    if (!res.success || !res.sale) {
                        if (content) {
                            content.innerHTML = `<div style="text-align: center; padding: 2rem; color: #dc2626;">Sale details not found.</div>`;
                        }
                        return;
                    }

                    const s = res.sale;
                    if (title) title.innerText = `Invoice #${s.invoice_no}`;

                    let itemsHtml = '';
                    const lineItems = res.items || s.items || [];
                    lineItems.forEach((it, idx) => {
                        const lineTot = parseFloat(it.line_total) || 0;
                        const uPrice = parseFloat(it.unit_price) || 0;
                        const qty = parseFloat(it.quantity) || 0;
                        const disc = parseFloat(it.discount_amount) || 0;
                        const discText = disc > 0 ? `<span style="color: #dc2626; font-size: 0.72rem;">(-${disc.toFixed(2)})</span>` : '';

                        itemsHtml += `
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 8px 10px; font-size: 0.78rem; text-align: center; color: #94a3b8;">${idx + 1}</td>
                                <td style="padding: 8px 10px;">
                                    <div style="font-weight: 700; color: #0f172a; font-size: 0.82rem;">${escapeHtml(it.product_name)}</div>
                                    <div style="font-size: 0.72rem; color: #64748b;">SKU: ${escapeHtml(it.sku || '—')} | Lot: ${escapeHtml(it.lot_number || '—')}</div>
                                </td>
                                <td style="padding: 8px 10px; font-size: 0.82rem; text-align: center; font-weight: 700;">${qty}</td>
                                <td style="padding: 8px 10px; font-size: 0.82rem; text-align: right; color: #475569;">${uPrice.toFixed(2)}</td>
                                <td style="padding: 8px 10px; font-size: 0.82rem; text-align: right; font-weight: 800; color: #0f172a;">${lineTot.toFixed(2)} ${discText}</td>
                            </tr>
                        `;
                    });

                    let paymentsHtml = '';
                    const payRecords = res.payments || s.payments || [];
                    payRecords.forEach(p => {
                        const pAmt = parseFloat(p.amount) || 0;
                        paymentsHtml += `
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 4px 0; font-size: 0.82rem;">
                                <span style="color: #64748b; font-weight: 600;"><i class="fa-solid fa-credit-card me-1" style="font-size: 0.75rem;"></i>${escapeHtml(p.payment_method)} (${escapeHtml(p.reference_no || 'Tendered')})</span>
                                <span style="font-weight: 800; color: #0f172a;">LKR ${pAmt.toFixed(2)}</span>
                            </div>
                        `;
                    });

                    const subtotal = parseFloat(s.subtotal) || 0;
                    const discount = parseFloat(s.discount_amount) || 0;
                    const total = parseFloat(s.total_amount) || 0;

                    if (content) {
                        content.innerHTML = `
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
                                <div>
                                    <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8;">Customer Information</div>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">${escapeHtml(s.customer_name)}</div>
                                    <div style="font-size: 0.78rem; color: #64748b;">Phone: ${escapeHtml(s.customer_phone || 'None')}</div>
                                    <div style="font-size: 0.78rem; color: #64748b;">Date: ${escapeHtml(s.created_at)}</div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8;">Terminal & Staff</div>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 0.85rem;">Branch: ${escapeHtml(s.branch_name || 'Main')}</div>
                                    <div style="font-size: 0.78rem; color: #64748b;">Cashier: ${escapeHtml(s.cashier_name || 'Cashier')}</div>
                                    ${s.sales_rep_name ? `<div style="font-size: 0.78rem; color: #0284c7; font-weight: 700;">Rep: ${escapeHtml(s.sales_rep_name)}</div>` : ''}
                                </div>
                            </div>

                            <!-- Line items table -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 1rem;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead style="background: #f8fafc;">
                                        <tr>
                                            <th style="padding: 8px 10px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; text-align: center; width: 36px;">#</th>
                                            <th style="padding: 8px 10px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; text-align: left;">Item Description</th>
                                            <th style="padding: 8px 10px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; text-align: center; width: 60px;">Qty</th>
                                            <th style="padding: 8px 10px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; text-align: right; width: 90px;">Price</th>
                                            <th style="padding: 8px 10px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; text-align: right; width: 100px;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${itemsHtml}
                                    </tbody>
                                </table>
                            </div>

                            <!-- Totals and Payment Summary -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem;">
                                <div>
                                    <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 6px;">Payment Breakdown</div>
                                    ${paymentsHtml || '<div style="font-size: 0.78rem; color: #94a3b8;">No payment entries found.</div>'}
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 4px; border-left: 1px solid #e2e8f0; padding-left: 14px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.82rem; color: #64748b;">
                                        <span>Subtotal:</span>
                                        <span style="font-weight: 700; color: #0f172a;">LKR ${subtotal.toFixed(2)}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 0.82rem; color: #dc2626;">
                                        <span>Total Discount:</span>
                                        <span style="font-weight: 700;">- LKR ${discount.toFixed(2)}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 1.05rem; font-weight: 900; color: #0f172a; border-top: 1.5px dashed #cbd5e1; padding-top: 6px; margin-top: 4px; font-family: 'Outfit', sans-serif;">
                                        <span>Total Paid:</span>
                                        <span style="color: #047857;">LKR ${total.toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    console.error("Error fetching sale details", err);
                    if (content) {
                        content.innerHTML = `<div style="text-align: center; padding: 2rem; color: #dc2626;">Error loading sale details.</div>`;
                    }
                });
        }

        function closeSaleDetailsModal() {
            closeModal('modal-sale-details');
        }

        // Clear & Cancel Actions
        document.getElementById('btn-clear-sale').addEventListener('click', clearCartState);

        document.getElementById('btn-cancel-sale').addEventListener('click', () => {
            if (!activeSaleId && cart.length === 0) return;
            if (confirm("Are you sure you want to cancel this sale?")) {
                if (activeSaleId) {
                    fetch(`pos_api.php?action=cancel_invoice&sale_id=${activeSaleId}`)
                        .then(res => res.json())
                        .then(res => {
                            alert(res.message);
                            clearCartState();
                        })
                        .catch(err => console.error("Error cancelling sale", err));
                } else {
                    clearCartState();
                }
            }
        });

        // --- HYBRID ONLINE/OFFLINE REAL-TIME CUSTOMER SELECTION ENGINE ---
        let custSearchTimer = null;
        const custSearchInput = document.getElementById('cust-search-input');

        function isPaymentModalActive() {
            const el = document.getElementById('modal-payment');
            return el && el.classList.contains('active');
        }

        function focusCurrentPaymentMethodInput() {
            if (activePaymentMethod === 'Split') {
                const cashSplit = document.getElementById('split-cash-amount');
                if (cashSplit) { cashSplit.focus(); cashSplit.select(); }
            } else if (activePaymentMethod === 'Cash') {
                const cashGiven = document.getElementById('pay-cash-given-input');
                if (cashGiven) { cashGiven.focus(); cashGiven.select(); }
            } else {
                const splitInput = document.getElementById('pay-split-amount-input');
                if (splitInput) { splitInput.focus(); splitInput.select(); }
            }
        }

        function handleCustomerSelectedInPayment() {}
        function openCustomerModal() {}
        function openCustomerModalFromCredit() {}
        function selectWalkinCustomer() {}
        const btnChangeCust = document.getElementById('btn-change-customer');
        if (btnChangeCust) {
            btnChangeCust.addEventListener('click', (e) => { e.stopPropagation(); });
        }

        function performCustomerSearch(q) {}

        function updateCustomerHighlight() {}

        function renderCustomerSearchResults(customers) {}
        function toggleQuickCustCreditLimit(cb) {}
        function openQuickCustomerModal(query) {}
        function closeQuickCustomerModal() {}
        function submitQuickCustomer() {}
        function showLoyaltyPromptModal(cust, discountPct) {}
        function updateLoyaltyChoiceHighlight() {}
        function confirmApplyLoyaltyDiscount() {}
        function cancelLoyaltyPrompt() {}
        function selectCustomerByIndex(idx) {}

        // Dedicated Payment View & Switch Logic
        document.getElementById('btn-proceed-pay').addEventListener('click', proceedToPayment);

        let isPreparingDraft = false;
        function proceedToPayment() {
            if (isPreparingDraft) return;
            if (Date.now() - lastModalClosedTime < 80) {
                return;
            }
            if (document.querySelector('.pos-modal.active')) {
                return;
            }
            if (cart.length === 0) {
                alert("Cart is empty. Please scan or select items before completing the sale.");
                return;
            }

            isPreparingDraft = true;
            const payHeroBtn = document.getElementById('btn-proceed-pay');
            if (payHeroBtn) payHeroBtn.style.opacity = '0.6';

            const payload = {
                action: 'prepare_draft',
                sale_id: activeSaleId,
                customer_id: currentCustomer && currentCustomer.id ? currentCustomer.id : 1,
                sales_rep_id: currentSalesRep ? currentSalesRep.id : null,
                discount_value: summaryDiscountInput.value.trim(),
                applied_promotions: currentAppliedPromotions,
                promo_discount_amount: currentPromoDiscount,
                items: cart.map(it => ({
                    product_id: it.product_id,
                    lot_id: it.lot_id,
                    quantity: it.quantity,
                    unit_price: it.unit_price,
                    original_price: it.original_price || it.unit_price,
                    discount_value: it.discount_value,
                    promo_discount_amount: parseFloat(it.promo_discount_amount) || 0,
                    applied_rule_id: it.applied_rule_id || null,
                    promo_name: it.promo_name || null
                }))
            };

            fetch('pos_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(res => {
                isPreparingDraft = false;
                if (payHeroBtn) payHeroBtn.style.opacity = '1';
                if (res.success) {
                    activeSaleId = res.sale_id;
                    openPaymentModal(res.total_amount);
                } else {
                    alert("Error preparing payment draft: " + res.message);
                }
            })
            .catch(err => {
                isPreparingDraft = false;
                if (payHeroBtn) payHeroBtn.style.opacity = '1';
                alert("Communication error preparing payment: " + err.message);
            });
        }

        function openPaymentModal(totalAmount) {
            paymentAllocations = [];
            currentPayableTotal = parseFloat(totalAmount);
            
            document.getElementById('pay-modal-total').innerText = `LKR ${currentPayableTotal.toFixed(2)}`;
            document.getElementById('pay-modal-discount').innerText = `- LKR ${currentTotalDiscount.toFixed(2)}`;
            
            const addSplitBtn = document.getElementById('btn-add-split-line');
            if (addSplitBtn) addSplitBtn.disabled = false;

            // Reset split fields for fresh payment
            ['split-cash-amount', 'split-card-amount', 'split-bank-amount', 'split-cash-tendered', 'split-bank-ref-input'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            const bankDet = document.getElementById('split-bank-details');
            if (bankDet) bankDet.style.display = 'none';

            updatePaymentSummary();
            switchPaymentMethod('Cash');
            openModal('modal-payment');
        }

        const paymentMethodsList = ['Cash', 'Card', 'Bank', 'Split'];

        function switchPaymentMethod(method) {
            activePaymentMethod = method;

            // Re-enable split button on method change by default
            const addSplitBtn = document.getElementById('btn-add-split-line');
            if (addSplitBtn) addSplitBtn.disabled = false;

            document.querySelectorAll('.payment-method-btn').forEach(btn => {
                if (btn.getAttribute('data-method') === method) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            const singleContainer = document.getElementById('single-payment-method-container');
            const splitContainer = document.getElementById('split-multi-payment-section');

            if (method === 'Split') {
                if (singleContainer) singleContainer.style.display = 'none';
                if (splitContainer) splitContainer.style.display = 'block';

                loadBankAccounts();

                const hasAnyVal = ['split-cash-amount', 'split-card-amount', 'split-bank-amount']
                    .some(id => (parseFloat(document.getElementById(id)?.value) || 0) > 0);

                if (!hasAnyVal) {
                    const cashSplit = document.getElementById('split-cash-amount');
                    if (cashSplit) cashSplit.value = currentPayableTotal.toFixed(2);
                    const cashTend = document.getElementById('split-cash-tendered');
                    if (cashTend) cashTend.value = currentPayableTotal.toFixed(2);
                }
                recalculateSplitSummary();

                setTimeout(() => {
                    const cashSplit = document.getElementById('split-cash-amount');
                    if (cashSplit) { cashSplit.focus(); cashSplit.select(); }
                }, 80);
                return;
            }

            if (singleContainer) singleContainer.style.display = 'block';
            if (splitContainer) splitContainer.style.display = 'none';

            // Update active method label in split amount prompt
            const labelEl = document.getElementById('active-method-label');
            if (labelEl) labelEl.innerText = method;

            const cashSection = document.getElementById('cash-payment-section');
            const cardSection = document.getElementById('card-payment-section');
            const bankSection = document.getElementById('bank-payment-section');

            const splitInput = document.getElementById('pay-split-amount-input');
            const remDue = getRemainingDue();
            if (splitInput) {
                splitInput.value = remDue > 0 ? remDue.toFixed(2) : '0.00';
            }
            updateSplitButtonLabel();

            // Hide all method sections first
            if (cashSection) cashSection.style.display = 'none';
            if (cardSection) cardSection.style.display = 'none';
            if (bankSection) bankSection.style.display = 'none';

            if (method === 'Cash') {
                if (cashSection) cashSection.style.display = 'block';
                const cashGiven = document.getElementById('pay-cash-given-input');
                if (cashGiven) cashGiven.value = (splitInput ? splitInput.value : remDue.toFixed(2));
                calculateCashBalance();
                setTimeout(() => {
                    if (cashGiven) { cashGiven.focus(); cashGiven.select(); }
                }, 80);
            } else if (method === 'Card') {
                if (cardSection) cardSection.style.display = 'block';
                setTimeout(() => {
                    if (splitInput) { splitInput.focus(); splitInput.select(); }
                }, 80);
            } else if (method === 'Bank') {
                if (bankSection) bankSection.style.display = 'block';
                loadBankAccounts();
                setTimeout(() => {
                    const bankSelect = document.getElementById('pay-bank-account-select');
                    if (bankSelect) bankSelect.focus();
                }, 80);
            }
        }

        function populateCreditCustomerInfo() {}

        function setSplitCashTendered(val) {
            const cashAmt = parseFloat(document.getElementById('split-cash-amount')?.value) || 0;
            const tendInput = document.getElementById('split-cash-tendered');
            if (!tendInput) return;
            if (val === 'exact') {
                tendInput.value = cashAmt.toFixed(2);
            } else {
                tendInput.value = parseFloat(val).toFixed(2);
            }
            recalculateSplitSummary();
            tendInput.focus();
        }

        function fillSplitField(fieldId) {
            const fields = ['split-cash-amount', 'split-card-amount', 'split-bank-amount'];
            let otherSum = 0;
            fields.forEach(f => {
                if (f !== fieldId) {
                    otherSum += (parseFloat(document.getElementById(f)?.value) || 0);
                }
            });
            const rem = Math.max(0, currentPayableTotal - otherSum);
            const targetEl = document.getElementById(fieldId);
            if (targetEl) {
                targetEl.value = rem.toFixed(2);
                if (fieldId === 'split-cash-amount') {
                    const tendInput = document.getElementById('split-cash-tendered');
                    if (tendInput) tendInput.value = rem.toFixed(2);
                }
            }
            recalculateSplitSummary();
            if (targetEl) {
                targetEl.focus();
                targetEl.select();
            }
        }

        function recalculateSplitSummary() {
            const cashAmt = parseFloat(document.getElementById('split-cash-amount')?.value) || 0;
            const cardAmt = parseFloat(document.getElementById('split-card-amount')?.value) || 0;
            const bankAmt = parseFloat(document.getElementById('split-bank-amount')?.value) || 0;

            const totalAllocated = cashAmt + cardAmt + bankAmt;
            const remainingDue = Math.max(0, currentPayableTotal - totalAllocated);

            const totalAllocEl = document.getElementById('lbl-total-allocated');
            const remainingEl = document.getElementById('pay-modal-remaining');
            if (totalAllocEl) totalAllocEl.innerText = `LKR ${totalAllocated.toFixed(2)}`;
            if (remainingEl) {
                remainingEl.innerText = `LKR ${remainingDue.toFixed(2)}`;
                remainingEl.style.color = (remainingDue <= 0.001) ? '#15803d' : '#dc2626';
            }

            // Toggle Bank Details conditional visibility: ONLY show if split-bank-amount > 0
            const bankDetails = document.getElementById('split-bank-details');
            if (bankDetails) {
                bankDetails.style.display = (bankAmt > 0) ? 'block' : 'none';
                if (bankAmt > 0) loadBankAccounts();
            }

            // Update Split Cash Change
            const cashTendered = parseFloat(document.getElementById('split-cash-tendered')?.value) || 0;
            const changeBadge = document.getElementById('split-cash-change-badge');
            if (changeBadge) {
                if (cashAmt > 0 && cashTendered > 0) {
                    const chg = Math.max(0, cashTendered - cashAmt);
                    changeBadge.innerText = `Change: LKR ${chg.toFixed(2)}`;
                    changeBadge.style.color = (cashTendered >= cashAmt) ? '#16a34a' : '#dc2626';
                } else {
                    changeBadge.innerText = 'Change: LKR 0.00';
                    changeBadge.style.color = '#16a34a';
                }
            }
        }

        function setSplitExactRemaining() {
            const splitInput = document.getElementById('pay-split-amount-input');
            const rem = getRemainingDue();
            if (splitInput) {
                splitInput.value = rem.toFixed(2);
                updateSplitButtonLabel();
            }
            if (activePaymentMethod === 'Cash') {
                const cashGiven = document.getElementById('pay-cash-given-input');
                if (cashGiven) cashGiven.value = rem.toFixed(2);
                calculateCashBalance();
            }
        }

        function setCashTendered(val) {
            const cashGiven = document.getElementById('pay-cash-given-input');
            const splitInput = document.getElementById('pay-split-amount-input');
            const currentSplit = parseFloat(splitInput ? splitInput.value : 0) || getRemainingDue();

            if (val === 'exact') {
                if (cashGiven) cashGiven.value = currentSplit.toFixed(2);
            } else {
                if (cashGiven) cashGiven.value = parseFloat(val).toFixed(2);
            }
            calculateCashBalance();
            if (cashGiven) cashGiven.focus();
        }

        function updateSplitButtonLabel() {
            const splitInput = document.getElementById('pay-split-amount-input');
            const lbl = document.getElementById('btn-split-amt-lbl');
            if (splitInput && lbl) {
                const val = parseFloat(splitInput.value) || 0;
                lbl.innerText = `LKR ${val.toFixed(2)}`;
            }
        }

        function loadBankAccounts() {
            if (activeBankAccounts.length > 0) {
                populateBankDropdown(activeBankAccounts);
                return;
            }

            fetch('pos_api.php?action=get_bank_accounts')
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.data) {
                        activeBankAccounts = res.data;
                        populateBankDropdown(activeBankAccounts);
                    }
                })
                .catch(err => console.error("Error fetching bank accounts:", err));
        }

        function populateBankDropdown(accounts) {
            const selectElem = document.getElementById('pay-bank-account-select');
            const splitSelect = document.getElementById('split-bank-account-select');

            let html = '<option value="">-- Choose Active Bank Account --</option>';
            accounts.forEach(acc => {
                html += `<option value="${acc.id}">${escapeHtml(acc.bank_name)} - ${escapeHtml(acc.account_name)} (${escapeHtml(acc.account_number)})</option>`;
            });

            if (selectElem) {
                selectElem.innerHTML = html;
                if (accounts.length > 0 && !selectElem.value) {
                    selectElem.value = accounts[0].id;
                }
                if (activePaymentMethod === 'Bank') {
                    selectElem.focus();
                }
            }
            if (splitSelect) {
                splitSelect.innerHTML = html;
                if (accounts.length > 0 && !splitSelect.value) {
                    splitSelect.value = accounts[0].id;
                }
            }
        }

        // Attach Split card input listeners for real-time recalculation
        ['split-cash-amount', 'split-card-amount', 'split-bank-amount'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', recalculateSplitSummary);
            }
        });
        const splitCashTend = document.getElementById('split-cash-tendered');
        if (splitCashTend) {
            splitCashTend.addEventListener('input', recalculateSplitSummary);
        }

        const paySplitInput = document.getElementById('pay-split-amount-input');
        if (paySplitInput) {
            paySplitInput.addEventListener('input', () => {
                updateSplitButtonLabel();
                if (activePaymentMethod === 'Cash') {
                    const cashGiven = document.getElementById('pay-cash-given-input');
                    if (cashGiven) {
                        cashGiven.value = paySplitInput.value;
                    }
                    calculateCashBalance();
                }
            });
        }

        const payCashGivenInput = document.getElementById('pay-cash-given-input');
        if (payCashGivenInput) {
            payCashGivenInput.addEventListener('input', calculateCashBalance);
        }

        const btnAddSplit = document.getElementById('btn-add-split-line');
        if (btnAddSplit) {
            btnAddSplit.addEventListener('click', (e) => {
                e.preventDefault();
                addSplitPaymentLine();
            });
        }

        function setQuickCash(val) {
            setCashTendered(val);
        }

        function calculateCashBalance() {
            const cashGivenInput = document.getElementById('pay-cash-given-input');
            const splitInput = document.getElementById('pay-split-amount-input');
            const changeDisplay = document.getElementById('pay-change-amount');
            if (!cashGivenInput || !changeDisplay) return;

            const given = parseFloat(cashGivenInput.value) || 0;
            const cashPayable = parseFloat(splitInput ? splitInput.value : 0) || getRemainingDue();
            const change = Math.max(0, given - cashPayable);

            changeDisplay.innerText = `LKR ${change.toFixed(2)}`;

            if (given < cashPayable && cashPayable > 0) {
                changeDisplay.style.color = '#dc2626';
            } else {
                changeDisplay.style.color = '#15803d';
            }
        }

        function getTotalAllocated() {
            let sum = 0;
            paymentAllocations.forEach(p => sum += (parseFloat(p.amount) || 0));
            return Math.round(sum * 100) / 100;
        }

        function getRemainingDue() {
            const allocated = getTotalAllocated();
            return Math.max(0, Math.round((currentPayableTotal - allocated) * 100) / 100);
        }

        function addSplitPaymentLine() {
            const method = activePaymentMethod;
            const splitInput = document.getElementById('pay-split-amount-input');
            const cashGivenInput = document.getElementById('pay-cash-given-input');
            let amount = parseFloat(splitInput ? splitInput.value : 0) || 0;
            const remDue = getRemainingDue();

            if (remDue <= 0) {
                alert("The invoice amount is already fully allocated (Remaining: LKR 0.00). Click 'CONFIRM PAYMENT' or 'FINALIZE SALE' to complete the transaction.");
                return;
            }

            if (isNaN(amount) || amount <= 0) {
                alert("Please enter a valid payment amount greater than zero.");
                if (splitInput) splitInput.focus();
                return;
            }

            amount = Math.round(amount * 100) / 100;

            // Non-cash overpayment rule: cannot exceed remaining due
            if (method !== 'Cash' && amount > remDue) {
                alert(`Cannot allocate LKR ${amount.toFixed(2)} with ${method}. Remaining due is only LKR ${remDue.toFixed(2)}. Overpayment is only permitted for Cash.`);
                if (splitInput) {
                    splitInput.value = remDue.toFixed(2);
                    updateSplitButtonLabel();
                    splitInput.focus();
                }
                return;
            }

            // Cash method: handle tender and overpayment cleanly
            let cashTenderedVal = 0;
            if (method === 'Cash') {
                const enteredCash = parseFloat(cashGivenInput ? cashGivenInput.value : 0) || 0;
                cashTenderedVal = enteredCash > 0 ? enteredCash : amount;
                if (cashTenderedVal < amount) {
                    cashTenderedVal = amount;
                    if (cashGivenInput) cashGivenInput.value = amount.toFixed(2);
                }
                if (amount > remDue) {
                    amount = remDue;
                }
            }

            let refNo = '';
            let bankAccountId = null;

            if (method === 'Card') {
                refNo = 'Card Payment';
            } else if (method === 'Bank') {
                const bankSelect = document.getElementById('pay-bank-account-select');
                bankAccountId = bankSelect ? (bankSelect.value || null) : null;
                if (!bankAccountId) {
                    alert("Please select an active destination bank account for Bank Transfer.");
                    if (bankSelect) bankSelect.focus();
                    return;
                }
                const bankRef = document.getElementById('pay-bank-ref-input');
                refNo = bankRef ? bankRef.value.trim() : '';
            }

            paymentAllocations.push({
                payment_method: method,
                amount: amount,
                reference_no: refNo,
                bank_account_id: bankAccountId ? parseInt(bankAccountId) : null,
                tendered: method === 'Cash' ? cashTenderedVal : amount
            });

            // Reset field states for next allocation
            if (method === 'Bank') {
                const bankRef = document.getElementById('pay-bank-ref-input');
                if (bankRef) bankRef.value = '';
            }

            updatePaymentSummary();

            const newRem = getRemainingDue();
            if (newRem > 0) {
                if (splitInput) splitInput.value = newRem.toFixed(2);
                updateSplitButtonLabel();
                if (activePaymentMethod === 'Cash') {
                    const cashGiven = document.getElementById('pay-cash-given-input');
                    if (cashGiven) cashGiven.value = newRem.toFixed(2);
                    calculateCashBalance();
                }
            } else {
                if (splitInput) splitInput.value = '0.00';
                updateSplitButtonLabel();
                const confirmBtn = document.getElementById('btn-finalize-sale');
                if (confirmBtn) confirmBtn.focus();
            }

            // Bring the added split allocations container into view
            const allocContainer = document.getElementById('allocated-payments-container');
            if (allocContainer) {
                allocContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function updatePaymentSummary() {
            const allocated = getTotalAllocated();
            const remaining = getRemainingDue();
            const count = paymentAllocations.length;

            const container = document.getElementById('allocated-payments-container');
            const tbody = document.getElementById('payment-allocations-body');
            const countEl = document.getElementById('allocated-count');
            const statusBadge = document.getElementById('allocated-status-badge');
            const totalAllocEl = document.getElementById('lbl-total-allocated');
            const remainingEl = document.getElementById('pay-modal-remaining');
            const exactDueBtnLbl = document.getElementById('lbl-btn-exact-due');
            const finalizeBtn = document.getElementById('btn-finalize-sale');

            if (totalAllocEl) totalAllocEl.innerText = `LKR ${allocated.toFixed(2)}`;
            if (remainingEl) {
                remainingEl.innerText = `LKR ${remaining.toFixed(2)}`;
                if (remaining <= 0 && allocated >= currentPayableTotal) {
                    remainingEl.style.color = '#15803d';
                } else {
                    remainingEl.style.color = '#dc2626';
                }
            }
            if (exactDueBtnLbl) exactDueBtnLbl.innerText = `LKR ${remaining.toFixed(2)}`;

            if (count > 0) {
                if (container) container.style.display = 'block';
                if (countEl) countEl.innerText = count;

                if (statusBadge) {
                    if (remaining <= 0) {
                        statusBadge.style.background = '#dcfce7';
                        statusBadge.style.color = '#15803d';
                        statusBadge.innerText = '✓ Fully Allocated';
                    } else {
                        statusBadge.style.background = '#fef3c7';
                        statusBadge.style.color = '#b45309';
                        statusBadge.innerText = `Partial (LKR ${remaining.toFixed(2)} due)`;
                    }
                }

                let html = '';
                paymentAllocations.forEach((p, idx) => {
                    let infoText = escapeHtml(p.reference_no || '');
                    if (p.bank_account_id && activeBankAccounts.length > 0) {
                        const bAcc = activeBankAccounts.find(b => b.id == p.bank_account_id);
                        if (bAcc) {
                            infoText = escapeHtml(`Bank: ${bAcc.bank_name}` + (p.reference_no ? ' (' + p.reference_no + ')' : ''));
                        }
                    }

                    let iconClass = 'fa-money-bill-wave';
                    if (p.payment_method === 'Card') iconClass = 'fa-credit-card';
                    else if (p.payment_method === 'Bank') iconClass = 'fa-building-columns';
                    else if (p.payment_method === 'Credit') iconClass = 'fa-handshake-angle';

                    html += `
                        <tr style="height: 38px; border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 6px 12px; font-weight: 800; font-size: 12px; color: #0f172a;">
                                <i class="fa-solid ${iconClass} me-1" style="color: var(--pos-primary);"></i>
                                ${escapeHtml(p.payment_method)}
                            </td>
                            <td style="padding: 6px 12px; font-size: 11.5px; color: #64748b;">${infoText || '-'}</td>
                            <td style="padding: 6px 12px; text-align: right; font-weight: 800; font-size: 12px; font-family: 'Outfit', sans-serif; color: #0f172a;">
                                LKR ${parseFloat(p.amount).toFixed(2)}
                            </td>
                            <td style="padding: 6px 12px; text-align: center;">
                                <button type="button" onclick="removePaymentLine(${idx})" style="background: #fee2e2; border: 1px solid #fca5a5; color: #dc2626; border-radius: 6px; width: 22px; height: 22px; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;" title="Remove this allocation">&times;</button>
                            </td>
                        </tr>
                    `;
                });
                if (tbody) tbody.innerHTML = html;
            } else {
                if (container) container.style.display = 'none';
                if (tbody) tbody.innerHTML = '';
            }

            if (finalizeBtn) {
                if (count === 0) {
                    finalizeBtn.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> CONFIRM PAYMENT (Enter)';
                    finalizeBtn.style.background = '';
                } else if (remaining <= 0) {
                    finalizeBtn.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> FINALIZE SALE (Enter)';
                    finalizeBtn.style.background = '#15803d';
                } else {
                    finalizeBtn.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1"></i> DUE: LKR ${remaining.toFixed(2)}`;
                    finalizeBtn.style.background = '#e11d48';
                }
            }
        }

        function removePaymentLine(idx) {
            paymentAllocations.splice(idx, 1);
            updatePaymentSummary();
            const splitInput = document.getElementById('pay-split-amount-input');
            const rem = getRemainingDue();
            if (splitInput) {
                splitInput.value = rem.toFixed(2);
                updateSplitButtonLabel();
            }
            if (activePaymentMethod === 'Cash') {
                const cashGiven = document.getElementById('pay-cash-given-input');
                if (cashGiven) cashGiven.value = rem.toFixed(2);
                calculateCashBalance();
            }
        }

        // Finalize Sale Transaction
        let isFinalizingSale = false;
        function finalizeSale(postAction = 'print') {
            if (isFinalizingSale) return;
            if (!activeSaleId) {
                alert("No active sale draft.");
                return;
            }

            // Gather allocations based on active method
            if (activePaymentMethod === 'Split') {
                paymentAllocations = [];
                const cashAmt = parseFloat(document.getElementById('split-cash-amount')?.value) || 0;
                const cashTendered = parseFloat(document.getElementById('split-cash-tendered')?.value) || cashAmt;
                const cardAmt = parseFloat(document.getElementById('split-card-amount')?.value) || 0;
                const bankAmt = parseFloat(document.getElementById('split-bank-amount')?.value) || 0;
                const bankSelect = document.getElementById('split-bank-account-select');
                const bankRef = document.getElementById('split-bank-ref-input')?.value?.trim() || '';

                if (cashAmt > 0) {
                    paymentAllocations.push({
                        payment_method: 'Cash',
                        amount: cashAmt,
                        reference_no: '',
                        bank_account_id: null,
                        tendered: Math.max(cashAmt, cashTendered)
                    });
                }
                if (cardAmt > 0) {
                    paymentAllocations.push({
                        payment_method: 'Card',
                        amount: cardAmt,
                        reference_no: 'Card Payment',
                        bank_account_id: null,
                        tendered: cardAmt
                    });
                }
                if (bankAmt > 0) {
                    const bId = bankSelect ? bankSelect.value : null;
                    if (!bId) {
                        alert("Please select an active destination bank account for the bank transfer portion.");
                        if (bankSelect) bankSelect.focus();
                        return;
                    }
                    paymentAllocations.push({
                        payment_method: 'Bank',
                        amount: bankAmt,
                        reference_no: bankRef,
                        bank_account_id: parseInt(bId),
                        tendered: bankAmt
                    });
                }
            } else if (paymentAllocations.length === 0) {
                // Single Payment Fast Path: If no split allocations added yet
                const method = activePaymentMethod;
                const splitInput = document.getElementById('pay-split-amount-input');
                let amount = parseFloat(splitInput ? splitInput.value : 0) || currentPayableTotal;
                let refNo = '';
                let bankAccountId = null;
                let cashTenderedVal = amount;

                if (method === 'Cash') {
                    const cashGivenInput = document.getElementById('pay-cash-given-input');
                    cashTenderedVal = parseFloat(cashGivenInput ? cashGivenInput.value : 0) || amount;
                    if (cashTenderedVal < currentPayableTotal) {
                        alert(`Cash Tendered (LKR ${cashTenderedVal.toFixed(2)}) is less than Payable Total (LKR ${currentPayableTotal.toFixed(2)}). Please enter full cash amount.`);
                        if (cashGivenInput) cashGivenInput.focus();
                        return;
                    }
                    amount = cashTenderedVal; // Backend calculates change & net cash drawer movement
                } else if (method === 'Card') {
                    refNo = 'Card Payment';
                    amount = currentPayableTotal;
                } else if (method === 'Bank') {
                    const bankSelect = document.getElementById('pay-bank-account-select');
                    bankAccountId = bankSelect ? (bankSelect.value || null) : null;
                    if (!bankAccountId) {
                        alert("Please select an active destination bank account for Bank Transfer.");
                        if (bankSelect) bankSelect.focus();
                        return;
                    }
                    const bankRef = document.getElementById('pay-bank-ref-input');
                    refNo = bankRef ? bankRef.value.trim() : '';
                    amount = currentPayableTotal;
                }

                paymentAllocations.push({
                    payment_method: method,
                    amount: amount,
                    reference_no: refNo,
                    bank_account_id: bankAccountId ? parseInt(bankAccountId) : null,
                    tendered: cashTenderedVal
                });
            }

            const totalAllocated = getTotalAllocated();
            const remainingDue = getRemainingDue();

            if (remainingDue > 0.001) {
                alert(`Payment is incomplete. Remaining balance due: LKR ${remainingDue.toFixed(2)}. Please allocate the full payable amount before confirming.`);
                return;
            }

            const finalizeBtn = document.getElementById('btn-finalize-sale');
            const printBtn = document.getElementById('btn-save-print-sale');
            const waBtn = document.getElementById('btn-save-whatsapp-sale');
            const saveBtn = document.getElementById('btn-save-sale-only');
            isFinalizingSale = true;
            if (finalizeBtn) finalizeBtn.disabled = true;
            if (printBtn) printBtn.disabled = true;
            if (waBtn) waBtn.disabled = true;
            if (saveBtn) saveBtn.disabled = true;

            // Real-Time Server Finalization
            const payload = {
                action: 'finalize_sale',
                sale_id: activeSaleId,
                payments: paymentAllocations,
                applied_promotions: currentAppliedPromotions
            };

            fetch('pos_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(res => {
                isFinalizingSale = false;
                if (finalizeBtn) finalizeBtn.disabled = false;
                if (printBtn) printBtn.disabled = false;
                if (waBtn) waBtn.disabled = false;
                if (saveBtn) saveBtn.disabled = false;
                if (res.success) {
                    closeModal('modal-payment');
                    
                    lastCompletedSaleData = {
                        sale_id: res.sale_id,
                        offline_ref: null,
                        invoice_no: res.invoice_no,
                        total_amount: res.total_amount || currentPayableTotal,
                        change_amount: parseFloat(res.change_amount || 0),
                        payment_method: paymentAllocations.map(p => p.payment_method).join(', ')
                    };

                    if (postAction === 'print') {
                        triggerPrintInvoice();
                    } else if (postAction === 'whatsapp') {
                        triggerWhatsAppShare();
                    } else {
                        showPosToast(`Sale saved successfully! Invoice #${lastCompletedSaleData.invoice_no}`, 'success');
                    }
                    clearCartState();
                } else {
                    alert("Finalization Error: " + res.message);
                }
            })
            .catch(err => {
                isFinalizingSale = false;
                if (finalizeBtn) finalizeBtn.disabled = false;
                if (printBtn) printBtn.disabled = false;
                if (waBtn) waBtn.disabled = false;
                if (saveBtn) saveBtn.disabled = false;
                alert("Error finalizing sale: " + err.message);
            });

        }

        // Invoice Complete Screen Handler
        function showInvoiceCompleteModal(data) {
            document.getElementById('complete-inv-no').innerText = data.invoice_no;
            document.getElementById('complete-total').innerText = `LKR ${parseFloat(data.total_amount).toFixed(2)}`;
            document.getElementById('complete-payment').innerText = data.payment_method;
            document.getElementById('complete-balance').innerText = `LKR ${parseFloat(data.change_amount).toFixed(2)}`;

            openModal('modal-invoice-complete');

            setTimeout(() => {
                const newSaleBtn = document.getElementById('btn-complete-newsale');
                if (newSaleBtn) newSaleBtn.focus();
            }, 100);
        }

        // Print Invoice Action
        document.getElementById('btn-complete-print').addEventListener('click', triggerPrintInvoice);

        function triggerPrintInvoice() {
            if (!lastCompletedSaleData || !lastCompletedSaleData.sale_id) return;
            const receiptUrl = `receipt.php?sale_id=${lastCompletedSaleData.sale_id}`;
            window.open(receiptUrl, '_blank', 'width=400,height=650');
        }

        // Send via WhatsApp Action
        document.getElementById('btn-complete-whatsapp').addEventListener('click', triggerWhatsAppShare);

        function triggerWhatsAppShare() {
            if (!lastCompletedSaleData) return;
            const phone = currentCustomer.phone ? currentCustomer.phone.replace(/[^0-9]/g, '') : '';
            const msg = `Hello ${encodeURIComponent(currentCustomer.name)},\nThank you for shopping with ${encodeURIComponent('<?= htmlspecialchars($businessName) ?>')}!\n\nInvoice #: ${lastCompletedSaleData.invoice_no}\nTotal Payable: LKR ${parseFloat(lastCompletedSaleData.total_amount).toFixed(2)}\nBalance Returned: LKR ${parseFloat(lastCompletedSaleData.change_amount).toFixed(2)}\n\nHave a great day!`;
            
            const waUrl = phone ? `https://wa.me/${phone}?text=${msg}` : `https://wa.me/?text=${msg}`;
            window.open(waUrl, '_blank');
        }

        // New Sale Action
        document.getElementById('btn-complete-newsale').addEventListener('click', startNewSale);

        function startNewSale() {
            closeModal('modal-invoice-complete');
            clearCartState();
            setTimeout(() => {
                if (searchInput) searchInput.focus();
            }, 100);
        }

        function showPosToast(message, type = 'success') {
            let toast = document.getElementById('pos-toast-notification');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'pos-toast-notification';
                toast.style.cssText = 'position: fixed; top: 22px; right: 24px; z-index: 99999; padding: 12px 22px; border-radius: 14px; font-weight: 800; font-size: 0.95rem; box-shadow: 0 12px 30px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 10px; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); transform: translateY(-20px); opacity: 0; pointer-events: none;';
                document.body.appendChild(toast);
            }
            toast.style.background = (type === 'success') ? 'linear-gradient(135deg, #065f46 0%, #047857 100%)' : '#1e293b';
            toast.style.color = '#ffffff';
            toast.innerHTML = `<i class="fa-solid fa-circle-check" style="font-size: 1.25rem; color: #34d399;"></i> <span>${escapeHtml(message)}</span>`;
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';

            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => {
                toast.style.transform = 'translateY(-20px)';
                toast.style.opacity = '0';
            }, 3500);
        }

        function clearCartState() {
            cart = [];
            activeSaleId = null;
            lastCompletedSaleData = null;
            summaryDiscountInput.value = '';
            activeCouponCode = '';
            currentAppliedPromotions = [];
            currentPromoDiscount = 0.00;
            confirmedPromoKey = null;
            declinedPromoKey = null;
            pendingPromotionData = null;
            pendingPromoKey = null;
            const coupInput = document.getElementById('summary-coupon-input');
            if (coupInput) coupInput.value = '';
            updatePromoSummaryUI(0, 0, []);
            paymentAllocations = [];
            clearSalesRep();
            renderCart();
            searchInput.focus();
        }

        // Global Modal Helpers
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { 
            if (id === 'modal-payment') {
                if (typeof isFinalizingSale !== 'undefined') {
                    isFinalizingSale = false;
                }
            }
            document.getElementById(id).classList.remove('active'); 
            lastModalClosedTime = Date.now();
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // Full-Screen Sales Returns & Exchanges Modal Helpers
        function openReturnsModal() {
            const modal = document.getElementById('modal-pos-returns');
            const iframe = document.getElementById('pos-returns-iframe');
            if (!modal || !iframe) return;

            if (!iframe.src || iframe.src === 'about:blank' || !iframe.src.includes('returns.php')) {
                iframe.src = 'returns.php?embedded=1';
            }
            openModal('modal-pos-returns');
        }

        function closeReturnsModal() {
            closeModal('modal-pos-returns');
            lastModalClosedTime = Date.now();
            setTimeout(() => {
                if (searchInput && !document.querySelector('.pos-modal.active')) {
                    searchInput.focus();
                }
            }, 180);
        }
        window.openReturnsModal = openReturnsModal;
        window.closeReturnsModal = closeReturnsModal;

        // Global Keyboard Shortcuts Handler
        document.addEventListener('keydown', (e) => {
            const isReturnsModalOpen = document.getElementById('modal-pos-returns') && document.getElementById('modal-pos-returns').classList.contains('active');
            if (isReturnsModalOpen) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeReturnsModal();
                }
                return;
            }



            const isPromoModalOpen = document.getElementById('modal-promotion-confirm') && document.getElementById('modal-promotion-confirm').classList.contains('active');
            if (isPromoModalOpen) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    dismissPromotionPrompt();
                    return;
                }
                if (e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'Tab') {
                    e.preventDefault();
                    promoChoiceIndex = (promoChoiceIndex === 1) ? 0 : 1;
                    updatePromoChoiceHighlight();
                    return;
                }
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    if (Date.now() - lastPromoModalOpenTime < 200) return;
                    if (e.repeat) return;
                    if (promoChoiceIndex === 1) {
                        confirmApplyPromotion();
                    } else {
                        dismissPromotionPrompt();
                    }
                    return;
                }
                return;
            }

            const isLoyaltyPromptOpen = document.getElementById('modal-loyalty-prompt') && document.getElementById('modal-loyalty-prompt').classList.contains('active');
            if (isLoyaltyPromptOpen) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelLoyaltyPrompt();
                    return;
                }
                if (e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'Tab') {
                    e.preventDefault();
                    loyaltyChoiceIndex = (loyaltyChoiceIndex === 1) ? 0 : 1;
                    updateLoyaltyChoiceHighlight();
                    return;
                }
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    if (Date.now() - lastLoyaltyModalOpenTime < 250) {
                        return;
                    }
                    if (e.repeat) return;
                    if (loyaltyChoiceIndex === 1) {
                        confirmApplyLoyaltyDiscount();
                    } else {
                        cancelLoyaltyPrompt();
                    }
                    return;
                }
                return;
            }

            const isLotModalOpen = document.getElementById('modal-lot-selector').classList.contains('active');
            const isPaymentModalOpen = document.getElementById('modal-payment').classList.contains('active');
            const isCompleteModalOpen = document.getElementById('modal-invoice-complete').classList.contains('active');

            if (isLotModalOpen) {
                const cards = document.querySelectorAll('#lot-modal-body .lot-card');
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeModal('modal-lot-selector');
                    searchInput.focus();
                    return;
                }
                if (cards.length > 0) {
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        selectedLotIndex = (selectedLotIndex + 1) % cards.length;
                        updateLotCardHighlight();
                        return;
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        selectedLotIndex = (selectedLotIndex - 1 + cards.length) % cards.length;
                        updateLotCardHighlight();
                        return;
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        e.stopPropagation();
                        // Guard: Prevent the same Enter key that opened the lot modal from immediately selecting a lot
                        if (Date.now() - lastLotModalOpenTime < 250) {
                            return;
                        }
                        if (e.repeat) return;
                        if (selectedLotIndex >= 0 && cards[selectedLotIndex]) {
                            cards[selectedLotIndex].click();
                        }
                        return;
                    }
                }
                return;
            }

            if (isCompleteModalOpen) {
                if (e.key === 'Enter' || e.key === 'Space') {
                    e.preventDefault();
                    startNewSale();
                } else if (e.key === 'p' || e.key === 'P') {
                    e.preventDefault();
                    triggerPrintInvoice();
                } else if (e.key === 'Escape' || e.key === 'F2' || e.key === 'n' || e.key === 'N') {
                    e.preventDefault();
                    startNewSale();
                } else if (e.key === 'w' || e.key === 'W') {
                    e.preventDefault();
                    triggerWhatsAppShare();
                }
                return;
            }

        function focusNextPaymentField(currentEl) {
            const modal = document.getElementById('modal-payment');
            if (!modal) return;

            const allElements = Array.from(modal.querySelectorAll('input:not([type="hidden"]):not([disabled]):not([readonly]), select:not([disabled])'));
            const visibleFields = allElements.filter(el => {
                return (el.offsetWidth > 0 || el.offsetHeight > 0) && window.getComputedStyle(el).visibility !== 'hidden';
            });

            const currentIndex = visibleFields.indexOf(currentEl);
            if (currentIndex !== -1 && currentIndex < visibleFields.length - 1) {
                const nextEl = visibleFields[currentIndex + 1];
                nextEl.focus();
                if (typeof nextEl.select === 'function') {
                    nextEl.select();
                }
            } else {
                // On the last field, advance focus to the primary Save & Print button for rapid keyboard completion
                const printBtn = document.getElementById('btn-save-print-sale');
                if (printBtn) {
                    printBtn.focus();
                    return;
                }
                if (visibleFields.length > 0) {
                    visibleFields[0].focus();
                    if (typeof visibleFields[0].select === 'function') {
                        visibleFields[0].select();
                    }
                }
            }
        }



            const isHistModalOpen = document.getElementById('modal-sales-history') && document.getElementById('modal-sales-history').classList.contains('active');
            if (isHistModalOpen) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeSalesHistoryModal();
                    if (searchInput) searchInput.focus();
                }
                return;
            }

            const isSaleDetailsModalOpen = document.getElementById('modal-sale-details') && document.getElementById('modal-sale-details').classList.contains('active');
            if (isSaleDetailsModalOpen) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeSaleDetailsModal();
                }
                return;
            }

            if (isPaymentModalOpen) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeModal('modal-payment');
                    searchInput.focus();
                    return;
                }

                // Ctrl + Enter -> Save & Print Bill
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    finalizeSale('print');
                    return;
                }

                // Alt + Enter -> Save & WhatsApp Bill
                if (e.altKey && e.key === 'Enter') {
                    e.preventDefault();
                    finalizeSale('whatsapp');
                    return;
                }

                // Shift + Enter -> Save Only (no print, no WhatsApp)
                if (e.shiftKey && e.key === 'Enter') {
                    e.preventDefault();
                    finalizeSale('none');
                    return;
                }

                // Alt + 1..4 Direct Payment Method Switching (Cash, Card, Bank, Split)
                if (e.altKey && ['1', '2', '3', '4'].includes(e.key)) {
                    e.preventDefault();
                    const targetIdx = parseInt(e.key, 10) - 1;
                    if (paymentMethodsList[targetIdx]) {
                        switchPaymentMethod(paymentMethodsList[targetIdx]);
                    }
                    return;
                }

                const isInsideInput = e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT');

                // ArrowLeft / ArrowRight navigation between payment methods (Cash, Card, Bank, Split)
                if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                    e.preventDefault();
                    const currentIdx = paymentMethodsList.indexOf(activePaymentMethod);
                    let nextIdx = 0;
                    if (e.key === 'ArrowRight') {
                        nextIdx = (currentIdx + 1) % paymentMethodsList.length;
                    } else {
                        nextIdx = (currentIdx - 1 + paymentMethodsList.length) % paymentMethodsList.length;
                    }
                    switchPaymentMethod(paymentMethodsList[nextIdx]);
                    return;
                }

                // Enter Key Action inside Payment Settlement Modal: Direct Save Only
                if (e.key === 'Enter') {
                    e.preventDefault();

                    // If user is focused on Cancel, Quick Cash, or Fill Exact button, trigger that button
                    if (e.target && (e.target.classList.contains('btn-payment-cancel') || e.target.classList.contains('btn-quick-cash') || e.target.classList.contains('btn-fill-exact'))) {
                        e.target.click();
                        return;
                    }

                    // Direct Enter = Save Bill
                    finalizeSale('none');
                    return;
                }

                if (e.key === 'F10') {
                    e.preventDefault();
                    finalizeSale('none');
                    return;
                }
                return;
            }



            // Ctrl+D -> Focus on Total Bill Discount field (block browser bookmark)
            if ((e.ctrlKey || e.metaKey) && (e.key === 'd' || e.key === 'D')) {
                e.preventDefault();
                e.stopPropagation();
                const discInput = document.getElementById('summary-discount-input');
                if (discInput && !discInput.disabled) {
                    discInput.focus();
                    discInput.select();
                }
                return;
            }

            // Ctrl+S -> Focus on Product Search Bar (block browser save page dialog)
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
                e.preventDefault();
                e.stopPropagation();
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
                return;
            }

            // Billing Screen Global Shortcuts
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                if (e.key === 'Escape') {
                    e.target.blur();
                    searchInput.focus();
                    return;
                } else if (e.key === 'Tab') {
                    if (!e.shiftKey) {
                        if (e.target.id === 'cart-item-qty-0') {
                            e.preventDefault();
                            const topDisc = document.getElementById('cart-item-disc-0');
                            if (topDisc && !topDisc.disabled) {
                                topDisc.focus();
                                topDisc.select();
                            } else {
                                searchInput.focus();
                            }
                            return;
                        } else if (e.target.id === 'cart-item-disc-0') {
                            e.preventDefault();
                            searchInput.focus();
                            return;
                        }
                    } else {
                        // Shift + Tab reverse navigation
                        if (e.target.id === 'cart-item-disc-0') {
                            e.preventDefault();
                            const topQty = document.getElementById('cart-item-qty-0');
                            if (topQty) {
                                topQty.focus();
                                topQty.select();
                            } else {
                                searchInput.focus();
                            }
                            return;
                        } else if (e.target.id === 'cart-item-qty-0') {
                            e.preventDefault();
                            searchInput.focus();
                            return;
                        }
                    }
                } else if (e.target.id === 'cart-item-qty-0' && (e.key === '+' || e.code === 'NumpadAdd' || (e.key === '=' && !e.ctrlKey && !e.altKey))) {
                    e.preventDefault();
                    updateItemQty(0, 1);
                    const topQty = document.getElementById('cart-item-qty-0');
                    if (topQty) { topQty.focus(); topQty.select(); }
                    return;
                } else if (e.target.id === 'cart-item-qty-0' && (e.key === '-' || e.code === 'NumpadSubtract')) {
                    e.preventDefault();
                    updateItemQty(0, -1);
                    const topQty = document.getElementById('cart-item-qty-0');
                    if (topQty) { topQty.focus(); topQty.select(); } else { searchInput.focus(); }
                    return;
                } else if (e.key === 'Enter') {
                    if (e.target.classList.contains('item-qty-input') || e.target.classList.contains('item-discount-input') || e.target.id === 'summary-discount-input') {
                        e.target.blur();
                        searchInput.focus();
                        return;
                    }
                }
                return;
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                if (Date.now() - lastModalClosedTime < 450) {
                    return;
                }
                if (document.querySelector('.pos-modal.active')) {
                    return;
                }
                document.getElementById('btn-proceed-pay').click();
                return;
            }

            if (e.key === 'F1') {
                e.preventDefault();
                openReturnsModal();
                return;
            } else if (e.key === '/' || e.key === 'F2') {
                e.preventDefault();
                searchInput.focus();
            } else if (e.key === 'F5' || ((e.ctrlKey || e.metaKey) && (e.key === 'h' || e.key === 'H'))) {
                e.preventDefault();
                openSalesHistoryModal();
            } else if (e.key === 'F6') {
                e.preventDefault();
                holdSale();
            } else if (e.key === 'F7') {
                e.preventDefault();
                openHeldModal();
            } else if (e.key === 'F8') {
                e.preventDefault();
                clearCartState();
            } else if (e.key === 'F9') {
                e.preventDefault();
                document.getElementById('btn-cancel-sale').click();
            } else if (e.key === 'F10') {
                e.preventDefault();
                document.getElementById('btn-proceed-pay').click();
            }
        });

        // Terminal launch handler
        window.addEventListener('load', () => {
            setTimeout(() => {
                if (searchInput) searchInput.focus();
            }, 300);
        });
    </script>
</body>
</html>
