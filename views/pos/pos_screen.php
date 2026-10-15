<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($businessName) ?> - Modern POS Billing Terminal</title>
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

        * { box-sizing: border-box; margin: 0; padding: 0; }
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

        /* ── Top Header - Dark Minimal Glass Theme ── */
        .pos-header {
            background: #0f172a;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 60px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.12);
            z-index: 20;
            color: #ffffff;
        }

        .pos-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-family: 'SF Pro Display', sans-serif;
            font-weight: 800;
            font-size: 1.25rem;
            color: #ffffff;
            letter-spacing: -0.02em;
        }

        .pos-brand i { 
            font-size: 1.35rem; 
            color: #38bdf8;
        }

        .pos-info-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.85rem;
        }

        .badge-branch {
            background: rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            backdrop-filter: blur(8px);
        }

        .badge-user {
            background: rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            backdrop-filter: blur(8px);
        }

        .badge-status-online {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.3);
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .badge-status-offline {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #22c55e;
            display: inline-block;
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.3; transform: scale(0.85); }
            100% { opacity: 1; transform: scale(1); }
        }

        .btn-header-action {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            transition: all 0.18s ease;
            text-decoration: none;
        }

        .btn-header-action:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* ── POS Workspace Layout ── */
        .pos-body {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        .pos-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--pos-border);
            background-color: #f8fafc;
            padding: 0.85rem;
            gap: 0.85rem;
            position: relative;
            z-index: 100;
        }

        .pos-right {
            width: 440px;
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
            padding: 0.85rem;
            gap: 0.85rem;
            border-left: 1px solid var(--pos-border);
            z-index: 10;
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
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(16px);
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
            transition: all 0.15s ease;
        }

        .search-item:hover, .search-item.selected {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            padding-left: calc(1.15rem - 3px);
        }

        .search-item.selected {
            font-weight: 800;
        }

        /* ── Cart Container & Glassmorphism Table ── */
        .cart-container {
            flex: 1;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 16px;
            border: 1px solid var(--pos-border);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 1;
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

        .cart-table tr:hover { background-color: #f8fafc; }

        .qty-controls {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .btn-qty {
            background: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .btn-qty:hover { 
            background: #0f172a; 
            color: #ffffff;
            border-color: #0f172a;
        }

        .qty-input {
            width: 55px;
            background: #ffffff;
            border: 1px solid var(--pos-border);
            color: var(--pos-text);
            text-align: center;
            padding: 0.25rem;
            border-radius: 6px;
            font-weight: 800;
        }

        .price-override-input, .item-discount-input {
            width: 80px;
            background: #ffffff;
            border: 1px solid var(--pos-border);
            color: var(--pos-text);
            text-align: right;
            padding: 0.3rem;
            border-radius: 6px;
            font-size: 0.85rem;
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
            padding: 0.15rem 0.5rem;
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

        /* ── Right Panel Glassmorphic Cards ── */
        .customer-card {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--pos-border);
            border-radius: 16px;
            padding: 0.95rem;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .customer-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
        }
        .btn-customer-change {
            background: #0f172a;
            color: #ffffff !important;
            border: none;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.76rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15);
            transition: all 0.15s ease;
        }
        .btn-customer-change:hover {
            background: #1e293b;
            transform: translateY(-1px);
        }
        .btn-customer-select {
            background: #0f172a;
            color: #ffffff !important;
            border: none;
            padding: 0.38rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.15s ease;
        }
        .btn-customer-select:hover {
            background: #1e293b;
            transform: translateY(-1px);
        }

        .summary-card {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--pos-border);
            border-radius: 16px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
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

        /* ── Glassmorphism Modals & Dialogs ── */
        .pos-modal {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease-out;
        }

        .pos-modal.active {
            opacity: 1;
            pointer-events: auto;
        }

        .pos-modal-content {
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 20px;
            width: 92%;
            max-width: 630px;
            max-height: 88vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.2);
            color: var(--pos-text);
            transform: scale(0.97);
            transition: transform 0.2s ease-out;
        }

        .pos-modal.active .pos-modal-content { transform: scale(1); }

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

        .lot-card, .cust-item-card {
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

        .lot-card:hover, .cust-item-card:hover, .cust-item-card.selected, .lot-card.selected {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
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

        /* ── Payment Methods Grid ── */
        .pm-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 0.5rem;
            margin-bottom: 1.15rem;
        }

        .payment-method-btn {
            padding: 0.9rem 0.25rem;
            border: 1px solid var(--pos-border);
            border-radius: 12px;
            background: #ffffff;
            color: var(--pos-text);
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .payment-method-btn.active {
            border-color: #0f172a;
            background: #0f172a;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.2);
        }

        .payment-method-btn i { font-size: 1.35rem; }

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
            <div class="pos-brand">
                <i class="fa-solid fa-cash-register"></i>
                <span><?= htmlspecialchars($businessName) ?> POS</span>
            </div>

            <div class="pos-info-bar">
                <span id="pos-network-status" class="badge-status-online" onclick="openOfflineSyncModal()">
                    <span class="pulse-dot"></span> <i class="fa-solid fa-wifi"></i> Online
                </span>
                <button type="button" class="btn-header-action" id="btn-sync-offline" onclick="triggerManualSync()" title="Synchronize pending offline sales">
                    <i class="fa-solid fa-rotate"></i> Sync <span id="sync-pending-badge" style="background:#f59e0b; color:#fff; border-radius:10px; padding:0.1rem 0.4rem; font-size:0.75rem; display:none;">0</span>
                </button>
                <span class="badge-branch"><i class="fa-solid fa-store"></i> <?= htmlspecialchars($currentBranch['name']) ?></span>
                <span class="badge-user"><i class="fa-solid fa-user-gear"></i> <?= htmlspecialchars($currentUser['full_name']) ?></span>
                <span id="pos-clock" style="font-family: monospace; color: var(--pos-text-muted); font-weight: 800;"></span>
                <a href="dashboard.php" class="btn-header-action" title="Back to Main ERP Dashboard">
                    <i class="fa-solid fa-right-from-bracket"></i> Exit POS
                </a>
            </div>
        </header>

        <!-- POS Workspace Body -->
        <div class="pos-body">
            <!-- Left Side: Barcode Search & Cart Table -->
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
                                <th style="width: 4%;">#</th>
                                <th style="width: 44%;">Product Item</th>
                                <th style="width: 15%;">Qty</th>
                                <th style="width: 12%;">Unit Price</th>
                                <th style="width: 11%;">Discount</th>
                                <th style="width: 12%;">Subtotal</th>
                                <th style="width: 2%; text-align: center;"></th>
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
                <!-- Customer Selection Card -->
                <div class="customer-card" id="card-customer-selection" onclick="openCustomerModal()" title="Click or press F4 to select customer">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <span style="font-size: 0.8rem; color: var(--pos-text-muted); font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">CUSTOMER SELECTION</span>
                        <button type="button" class="btn-customer-change" id="btn-change-customer" onclick="event.stopPropagation(); openCustomerModal();" title="Change Customer (F4)">
                            <i class="fa-solid fa-user-pen"></i> Change (F4)
                        </button>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fa-solid fa-circle-user" style="font-size: 2.3rem; color: var(--pos-primary);"></i>
                        <div style="flex: 1;">
                            <div id="cust-disp-name" style="font-weight: 800; font-size: 1.05rem; color: var(--pos-text);">Walk-in Customer</div>
                            <div id="cust-disp-code" style="font-size: 0.8rem; color: var(--pos-text-muted);">Default POS Customer</div>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="color: #94a3b8; font-size: 0.85rem;"></i>
                    </div>
                </div>

                <!-- Sales Rep Selection Card -->
                <div class="customer-card" id="card-sales-rep-selection" onclick="openSalesRepModal()" title="Click or press F3 to select Sales Rep" style="margin-top: 0.65rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <span style="font-size: 0.8rem; color: var(--pos-text-muted); font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">SALES REP / SOLD BY</span>
                        <button type="button" class="btn-customer-change" id="btn-change-sales-rep" onclick="event.stopPropagation(); openSalesRepModal();" title="Change Sales Rep (F3)">
                            <i class="fa-solid fa-user-tag"></i> Select (F3)
                        </button>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fa-solid fa-id-badge" style="font-size: 2.2rem; color: #475569;"></i>
                        <div style="flex: 1;">
                            <div id="rep-disp-name" style="font-weight: 800; font-size: 1rem; color: var(--pos-text);">Direct Cashier / None</div>
                            <div id="rep-disp-code" style="font-size: 0.8rem; color: var(--pos-text-muted);">No Sales Rep Attributed</div>
                        </div>
                        <button type="button" id="btn-clear-sales-rep" onclick="event.stopPropagation(); clearSalesRep();" style="display:none; background:none; border:none; color:#ef4444; font-size:1.1rem; cursor:pointer;" title="Clear Sales Rep"><i class="fa-solid fa-xmark"></i></button>
                        <i class="fa-solid fa-chevron-right" style="color: #94a3b8; font-size: 0.85rem;"></i>
                    </div>
                </div>

                <!-- Order Totals & Discount Card -->
                <div class="summary-card">
                    <div class="summary-row">
                        <span style="color: var(--pos-text-muted); font-weight: 600;">Cart Subtotal</span>
                        <span id="summary-subtotal" style="font-weight: 800;">LKR 0.00</span>
                    </div>

                    <div class="summary-row">
                        <span style="color: var(--pos-text-muted); font-weight: 600;">Invoice Discount <span style="font-size: 0.72rem; color: #94a3b8; font-weight: 700; font-family: monospace;">(Ctrl+D)</span></span>
                        <input type="text" id="summary-discount-input" class="item-discount-input" placeholder="0 or 10%" style="width: 100px; text-align: right;" autocomplete="off" title="Invoice Discount (Ctrl+D)">
                    </div>

                    <!-- Round-up Suggestion Button -->
                    <div id="roundup-container" style="display: none;">
                        <div class="roundup-pill" id="btn-apply-roundup">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span id="roundup-text">Add LKR 0.00 to Round total to LKR 0.00</span>
                        </div>
                    </div>

                    <div class="summary-row">
                        <span style="color: var(--pos-text-muted); font-weight: 600;">Total Discount</span>
                        <span id="summary-discount-val" style="color: var(--pos-danger); font-weight: 700;">- LKR 0.00</span>
                    </div>

                    <div class="summary-row summary-row-total">
                        <span>Payable Amount</span>
                        <span id="summary-payable">LKR 0.00</span>
                    </div>
                </div>

                <!-- POS Action Button Grid -->
                <div class="action-grid">
                    <button class="btn-pos-action btn-pos-hold" id="btn-hold-sale" title="Park current invoice line items">
                        <i class="fa-solid fa-pause fa-lg"></i>
                        <span>Hold Invoice (F6)</span>
                    </button>

                    <button class="btn-pos-action btn-pos-held-list" id="btn-view-held" title="View list of parked invoices">
                        <i class="fa-solid fa-folder-open fa-lg"></i>
                        <span>Held List (F7)</span>
                    </button>

                    <button class="btn-pos-action btn-pos-clear" id="btn-clear-sale" title="Reset cart items">
                        <i class="fa-solid fa-rotate-right fa-lg"></i>
                        <span>Clear Sale (F8)</span>
                    </button>

                    <button class="btn-pos-action btn-pos-cancel" id="btn-cancel-sale" title="Cancel active sale">
                        <i class="fa-solid fa-ban fa-lg"></i>
                        <span>Cancel Sale (F9)</span>
                    </button>

                    <button class="btn-pos-action btn-pos-pay" id="btn-proceed-pay" title="Proceed to payment view">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>COMPLETE SALE (Enter / F10)</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Keyboard Shortcut Footer -->
        <footer class="shortcut-bar">
            <span><span class="sc-tag">Ctrl+S / F2</span> Search</span>
            <span><span class="sc-tag">Ctrl+D</span> Bill Discount</span>
            <span><span class="sc-tag">Tab / +/-</span> Qty Adjust</span>
            <span><span class="sc-tag">F3</span> Sales Rep</span>
            <span><span class="sc-tag">F4</span> Customer</span>
            <span><span class="sc-tag">F6</span> Hold</span>
            <span><span class="sc-tag">F7</span> Held List</span>
            <span><span class="sc-tag">F8</span> Clear</span>
            <span><span class="sc-tag">F9</span> Cancel</span>
            <span><span class="sc-tag">F10 / Enter</span> Pay</span>
        </footer>
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

    <!-- Offline Sales Sync Queue Modal -->
    <div class="pos-modal" id="modal-offline-sync-queue">
        <div class="pos-modal-content" style="max-width: 800px;">
            <div class="pos-modal-header">
                <span><i class="fa-solid fa-cloud-arrow-up me-2"></i> Offline Sales Synchronization Queue</span>
                <button type="button" style="background: none; border: none; color: var(--pos-text); cursor: pointer; font-size: 1.2rem;" onclick="closeModal('modal-offline-sync-queue')">&times;</button>
            </div>
            <div class="pos-modal-body">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="font-size: 0.9rem; color: var(--pos-text-muted);">
                        Sales recorded locally while offline are stored safely in IndexedDB and synchronized idempotently.
                    </div>
                    <button type="button" class="btn-header-action" onclick="triggerManualSync()" style="background: var(--pos-primary); color: #fff; font-weight: 700;">
                        <i class="fa-solid fa-rotate me-1"></i> Sync Now
                    </button>
                </div>
                <div id="offline-sales-list">
                    <!-- JS Populated -->
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Customer Selector Modal -->
    <div class="pos-modal" id="modal-customer-selector">
        <div class="pos-modal-content" style="max-width: 620px;">
            <div class="pos-modal-header">
                <span style="font-weight: 800; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-users" style="color: #0f172a;"></i> Select Customer (F4)
                </span>
                <button type="button" style="background: none; border: none; color: var(--pos-text); cursor: pointer; font-size: 1.2rem;" onclick="closeModal('modal-customer-selector')">&times;</button>
            </div>
            <div class="pos-modal-body">
                <div style="display: flex; gap: 8px; margin-bottom: 0.85rem;">
                    <input type="text" id="cust-search-input" class="search-input" placeholder="Type Name, Phone, Code or Barcode..." style="flex: 1;" autocomplete="off">
                    <button type="button" class="btn-customer-change" onclick="selectWalkinCustomer()" style="white-space: nowrap; height: 44px; border-radius: 12px; padding: 0 16px;" title="Reset to default Walk-in Customer">
                        <i class="fa-solid fa-user-clock"></i> Walk-in
                    </button>
                </div>
                <div id="cust-search-results" style="max-height: 380px; overflow-y: auto;">
                    <!-- JS Populated -->
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Create Customer Popup Modal -->
    <div class="pos-modal" id="modal-quick-customer" style="z-index: 100060;">
        <div class="pos-modal-content" style="max-width: 480px; padding: 0; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25); border: 1px solid rgba(226, 232, 240, 0.95);">
            <div style="background: #0f172a; padding: 1.15rem 1.4rem; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(255, 255, 255, 0.12); display: flex; align-items: center; justify-content: center; font-size: 1.05rem; color: #38bdf8;">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8;">Quick Customer Setup</div>
                        <div style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 800; color: #ffffff;">Create New Customer</div>
                    </div>
                </div>
                <button type="button" onclick="closeQuickCustomerModal()" style="background: rgba(255, 255, 255, 0.12); border: none; color: #fff; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: background 0.15s;">&times;</button>
            </div>

            <form id="form-quick-customer" onsubmit="event.preventDefault(); submitQuickCustomer();" style="padding: 1.35rem 1.4rem; background: #ffffff;">
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin-bottom: 0.35rem;">
                            Customer Name <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="text" id="quick-cust-name" class="search-input" placeholder="Full Name / Customer Name" required autocomplete="off" style="width: 100%; height: 44px; border-radius: 12px; font-size: 0.95rem; font-weight: 600; padding: 0 14px; border: 1.5px solid #cbd5e1;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin-bottom: 0.35rem;">
                            Phone Number (10 Digits)
                        </label>
                        <input type="tel" id="quick-cust-phone" class="search-input" placeholder="07XXXXXXXX (10 digits)" maxlength="10" autocomplete="off" style="width: 100%; height: 44px; border-radius: 12px; font-size: 0.95rem; font-weight: 600; padding: 0 14px; border: 1.5px solid #cbd5e1;">
                    </div>

                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                            <label style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569;">
                                WhatsApp Number (10 Digits)
                            </label>
                            <label style="font-size: 0.75rem; color: #0284c7; cursor: pointer; display: flex; align-items: center; gap: 4px; user-select: none;">
                                <input type="checkbox" id="quick-cust-same-wa" style="cursor: pointer;"> Same as Phone
                            </label>
                        </div>
                        <input type="tel" id="quick-cust-whatsapp" class="search-input" placeholder="07XXXXXXXX (10 digits)" maxlength="10" autocomplete="off" style="width: 100%; height: 44px; border-radius: 12px; font-size: 0.95rem; font-weight: 600; padding: 0 14px; border: 1.5px solid #cbd5e1;">
                    </div>
                </div>

                <div id="quick-cust-error" style="display: none; margin-top: 0.85rem; padding: 0.65rem 0.85rem; background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; border-radius: 10px; font-size: 0.82rem; font-weight: 600;"></div>

                <div style="display: grid; grid-template-columns: 1fr 1.4fr; gap: 0.75rem; margin-top: 1.25rem;">
                    <button type="button" onclick="closeQuickCustomerModal()" class="btn-loyalty-choice btn-loyalty-cancel" style="height: 44px; border-radius: 12px; font-weight: 700; border: 1.5px solid #cbd5e1; background: #f8fafc; color: #475569;">
                        <i class="fa-solid fa-xmark"></i> Cancel (Esc)
                    </button>
                    <button type="submit" id="btn-save-quick-cust" class="btn-loyalty-choice btn-loyalty-apply" style="height: 44px; border-radius: 12px; font-weight: 800; background: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-solid fa-check"></i> Save & Select (Enter)
                    </button>
                </div>

                <div style="display: flex; justify-content: center; gap: 12px; margin-top: 0.85rem; font-size: 0.72rem; color: #94a3b8; font-weight: 600;">
                    <span><kbd style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1px 5px; color: #475569;">Enter</kbd> Next Field / Save</span>
                    <span><kbd style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1px 5px; color: #475569;">Esc</kbd> Cancel</span>
                </div>
            </form>
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

    <!-- Enhanced Sales Rep Selector Modal -->
    <div class="pos-modal" id="modal-sales-rep-selector">
        <div class="pos-modal-content" style="max-width: 620px;">
            <div class="pos-modal-header">
                <span style="font-weight: 800; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-user-tag" style="color: #0f172a;"></i> Select Sales Representative (F3)
                </span>
                <button type="button" style="background: none; border: none; color: var(--pos-text); cursor: pointer; font-size: 1.2rem;" onclick="closeModal('modal-sales-rep-selector')">&times;</button>
            </div>
            <div class="pos-modal-body">
                <div style="display: flex; gap: 8px; margin-bottom: 0.85rem;">
                    <input type="text" id="rep-search-input" class="search-input" placeholder="Type Sales Rep Name, Code, Role or Phone..." style="flex: 1;" autocomplete="off">
                    <button type="button" class="btn-customer-change" onclick="clearSalesRep(); closeModal('modal-sales-rep-selector');" style="white-space: nowrap; height: 44px; border-radius: 12px; padding: 0 16px;" title="No Sales Rep Attributed">
                        <i class="fa-solid fa-user-xmark"></i> Direct
                    </button>
                </div>
                <div id="rep-search-results" style="max-height: 380px; overflow-y: auto;">
                    <!-- JS Populated -->
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Dedicated Payment Settlement View / Modal -->
    <div class="pos-modal" id="modal-payment">
        <div class="pos-modal-content" style="max-width: 680px;">
            <div class="pos-modal-header" style="display: flex; justify-content: space-between; align-items: center; padding: 0.9rem 1.25rem; border-bottom: 1px solid var(--pos-border);">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: #0f172a; color: #fff; font-size: 0.9rem;">
                        <i class="fa-solid fa-cash-register"></i>
                    </span>
                    <span style="font-weight: 800; font-size: 1.15rem; color: #0f172a;">Payment Settlement</span>
                </div>
                <button type="button" style="background: none; border: none; color: var(--pos-text-muted); cursor: pointer; font-size: 1.4rem; line-height: 1;" onclick="closeModal('modal-payment')">&times;</button>
            </div>
            <div class="pos-modal-body" style="padding: 1.1rem 1.25rem; max-height: calc(90vh - 120px); overflow-y: auto;">
                <!-- 4-Column Invoice Payment Summary Bar -->
                <div style="background: #f8fafc; border: 1px solid var(--pos-border); border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 1rem; display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; text-align: center;">
                    <div>
                        <div style="font-size: 0.72rem; color: var(--pos-text-muted); font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;">Invoice Total</div>
                        <div id="pay-modal-total" style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: #0f172a;">LKR 0.00</div>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; color: var(--pos-text-muted); font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;">Discounts</div>
                        <div id="pay-modal-discount" style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--pos-danger);">- LKR 0.00</div>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; color: var(--pos-text-muted); font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;">Allocated / Paid</div>
                        <div id="lbl-total-allocated" style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: #0284c7;">LKR 0.00</div>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; color: var(--pos-text-muted); font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;">Remaining Due</div>
                        <div id="pay-modal-remaining" style="font-family: 'Outfit', sans-serif; font-size: 1.35rem; font-weight: 900; color: #dc2626; transition: color .2s;">LKR 0.00</div>
                    </div>
                </div>

                <!-- Existing Split Payment Allocations Table Container (Shown when 1+ split added) -->
                <div id="allocated-payments-container" style="display: none; margin-bottom: 1rem; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                    <div style="background: #f1f5f9; padding: 0.5rem 0.85rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
                        <span style="font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">
                            <i class="fa-solid fa-list-check me-1"></i> Added Split Allocations (<span id="allocated-count">0</span>)
                        </span>
                        <span id="allocated-status-badge" style="font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 999px; background: #fef3c7; color: #b45309;">
                            Partial Payment
                        </span>
                    </div>
                    <div style="max-height: 125px; overflow-y: auto;">
                        <table class="cart-table" style="margin: 0; width: 100%;">
                            <thead>
                                <tr style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                                    <th style="padding: 6px 12px; font-size: 10.5px; text-align: left;">Method</th>
                                    <th style="padding: 6px 12px; font-size: 10.5px; text-align: left;">Details / Info</th>
                                    <th style="padding: 6px 12px; font-size: 10.5px; text-align: right;">Amount (LKR)</th>
                                    <th style="padding: 6px 12px; font-size: 10.5px; text-align: center; width: 36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="payment-allocations-body">
                                <!-- JS Populated -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payment Method Tabs (6 In A Single Row) -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <span style="font-size: 0.8rem; font-weight: 800; color: var(--pos-text-muted); text-transform: uppercase; letter-spacing: 0.04em;">
                        Select Payment Method
                    </span>
                    <span style="font-size: 0.72rem; color: #64748b;">Keyboard: <kbd style="background: #e2e8f0; padding: 1px 4px; border-radius: 4px; font-size: 10px;">←</kbd> <kbd style="background: #e2e8f0; padding: 1px 4px; border-radius: 4px; font-size: 10px;">→</kbd> to switch</span>
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
                    <button type="button" class="payment-method-btn" data-method="Credit" onclick="switchPaymentMethod('Credit')">
                        <i class="fa-solid fa-handshake-angle"></i>
                        <span>Credit</span>
                    </button>
                    <button type="button" class="payment-method-btn" data-method="Voucher" onclick="switchPaymentMethod('Voucher')">
                        <i class="fa-solid fa-ticket"></i>
                        <span>Voucher</span>
                    </button>
                    <button type="button" class="payment-method-btn" data-method="Koko" onclick="switchPaymentMethod('Koko')" style="position: relative;">
                        <i class="fa-solid fa-qrcode" style="color: #0284c7;"></i>
                        <span style="font-weight: 800;">Koko</span>
                        <span style="position: absolute; top: 4px; right: 4px; background: #0284c7; color: #fff; font-size: 8px; font-weight: 900; padding: 1px 4px; border-radius: 4px; text-transform: uppercase;">BNPL</span>
                    </button>
                </div>

                <!-- Universal Payment Amount & Specific Method Settings -->
                <div style="background: #ffffff; border: 1.5px solid var(--pos-border); border-radius: 12px; padding: 1.05rem; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <label id="lbl-split-amount" for="pay-split-amount-input" style="font-weight: 800; font-size: 0.9rem; color: #0f172a;">
                            Amount to Pay with <span id="active-method-label" style="color: var(--pos-primary);">Cash</span> (LKR)
                        </label>
                        <button type="button" class="btn-quick-cash" onclick="setSplitExactRemaining()" style="font-size: 0.72rem; padding: 2px 8px; border-radius: 6px;">
                            <i class="fa-solid fa-bolt me-1"></i> Exact Due (<span id="lbl-btn-exact-due">LKR 0.00</span>)
                        </button>
                    </div>
                    <input type="number" id="pay-split-amount-input" class="search-input" placeholder="0.00" step="0.01" style="font-family: 'Outfit', sans-serif; font-size: 1.6rem; font-weight: 900; text-align: right; padding: 0.55rem 1rem; margin-bottom: 0.75rem;">

                    <!-- Cash Specific Method Details -->
                    <div id="cash-payment-section">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                            <label for="pay-cash-given-input" style="font-weight: 700; font-size: 0.82rem; color: var(--pos-text-muted);">
                                Cash Tendered by Customer (LKR)
                            </label>
                            <span style="font-size: 0.72rem; color: #64748b;">(For calculating balance return)</span>
                        </div>
                        <input type="number" id="pay-cash-given-input" class="search-input" placeholder="Enter Tendered Cash..." step="0.01" style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; text-align: right; padding: 0.5rem 1rem;">
                        
                        <div class="quick-cash-btns" style="margin-top: 0.5rem; margin-bottom: 0.75rem;">
                            <button type="button" class="btn-quick-cash" onclick="setCashTendered('exact')">Exact Amount</button>
                            <button type="button" class="btn-quick-cash" onclick="setCashTendered(500)">LKR 500</button>
                            <button type="button" class="btn-quick-cash" onclick="setCashTendered(1000)">LKR 1,000</button>
                            <button type="button" class="btn-quick-cash" onclick="setCashTendered(2000)">LKR 2,000</button>
                            <button type="button" class="btn-quick-cash" onclick="setCashTendered(5000)">LKR 5,000</button>
                        </div>

                        <!-- Balance Return Banner -->
                        <div class="balance-banner" style="margin-top: 0.5rem; padding: 0.75rem 1rem;">
                            <div class="balance-banner-label" style="font-size: 0.75rem;">Cash Balance to Return</div>
                            <div class="balance-banner-amount" id="pay-change-amount" style="font-size: 1.8rem;">LKR 0.00</div>
                        </div>
                    </div>

                    <!-- Card Specific Method Details -->
                    <div id="card-payment-section" style="display: none;">
                        <div style="display: flex; align-items: center; gap: 10px; padding: 0.75rem 1rem; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px;">
                            <div style="width: 38px; height: 38px; border-radius: 8px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                                <i class="fa-solid fa-credit-card"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.85rem; font-weight: 800; color: #0f172a;">Card Payment Terminal</div>
                                <div style="font-size: 0.75rem; color: #64748b;">Process card transaction on POS terminal for the allocated amount.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Specific Method Details -->
                    <div id="bank-payment-section" style="display: none;">
                        <div style="margin-bottom: 0.65rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                <label for="pay-bank-account-select" style="font-weight: 700; font-size: 0.82rem; color: var(--pos-text-muted);">
                                    Destination Bank Account <span style="color: var(--pos-danger);">*</span>
                                </label>
                                <span style="font-size: 0.72rem; color: var(--pos-danger); font-weight: 700;">Required</span>
                            </div>
                            <select id="pay-bank-account-select" class="search-input" style="font-weight: 800; font-size: 0.95rem; padding: 0.65rem 0.85rem; cursor: pointer;">
                                <option value="">-- Choose Active Bank Account --</option>
                            </select>
                        </div>
                        <div>
                            <label for="pay-bank-ref-input" style="font-weight: 700; font-size: 0.82rem; color: var(--pos-text-muted); display: block; margin-bottom: 0.35rem;">
                                Transfer Reference / Slip No <span style="font-weight: normal; font-size: 0.72rem;">(Optional)</span>
                            </label>
                            <input type="text" id="pay-bank-ref-input" class="search-input" placeholder="e.g. Slip #49120 or Tx Ref" style="font-size: 0.95rem; font-weight: 700; padding: 0.55rem 0.85rem;">
                        </div>
                    </div>

                    <!-- Credit Specific Method Details -->
                    <div id="credit-payment-section" style="display: none;">
                        <div id="credit-customer-info" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.75rem 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <span style="font-size: 0.82rem; font-weight: 800; color: #0f172a;" id="credit-cust-name">Walk-in Customer</span>
                                <span style="font-size: 0.75rem; font-weight: 700; color: #64748b;" id="credit-cust-phone">-</span>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; text-align: center; border-top: 1px dashed #cbd5e1; padding-top: 0.45rem;">
                                <div>
                                    <div style="font-size: 0.68rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Credit Limit</div>
                                    <div id="credit-cust-limit" style="font-size: 0.88rem; font-weight: 800; color: #0f172a;">LKR 0.00</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.68rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Current Balance</div>
                                    <div id="credit-cust-balance" style="font-size: 0.88rem; font-weight: 800; color: #dc2626;">LKR 0.00</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.68rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Available Credit</div>
                                    <div id="credit-cust-available" style="font-size: 0.88rem; font-weight: 800; color: #16a34a;">LKR 0.00</div>
                                </div>
                            </div>
                        </div>
                        <div id="credit-walkin-warning" style="display: none; margin-top: 0.5rem; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 0.6rem 0.85rem; color: #991b1b; font-size: 0.8rem; font-weight: 700;">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Credit payment is strictly prohibited for Walk-in Customers. Please assign a registered customer first.
                        </div>
                    </div>

                    <!-- Voucher Specific Method Details -->
                    <div id="voucher-payment-section" style="display: none;">
                        <label for="pay-voucher-code-input" style="font-weight: 700; font-size: 0.82rem; color: var(--pos-text-muted); display: block; margin-bottom: 0.35rem;">
                            Gift Voucher Barcode / Serial Number <span style="color: var(--pos-danger);">*</span>
                        </label>
                        <div style="display: flex; gap: 8px; margin-bottom: 0.5rem;">
                            <input type="text" id="pay-voucher-code-input" class="search-input" placeholder="Scan or enter gift voucher barcode..." style="font-size: 0.95rem; font-weight: 700; padding: 0.55rem 0.85rem;">
                            <button type="button" id="btn-verify-voucher" class="btn-pos-action" onclick="verifyVoucherCode()" style="width: auto; padding: 0 1.25rem; font-size: 0.82rem; background: #0f172a; color: #fff; border-radius: 8px; white-space: nowrap;">
                                <i class="fa-solid fa-magnifying-glass me-1"></i> Verify
                            </button>
                        </div>
                        <div id="voucher-verification-result" style="display: none; padding: 0.55rem 0.85rem; border-radius: 8px; font-size: 0.8rem; font-weight: 700;"></div>
                    </div>

                    <!-- KOKO BNPL Payment Section -->
                    <div id="koko-payment-section" style="border: 1.5px solid #0284c7; border-radius: 12px; padding: 0.9rem; margin-top: 0.5rem; display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="background: #0284c7; color: #ffffff; font-size: 0.72rem; font-weight: 900; padding: 2px 7px; border-radius: 5px;">KOKO BNPL</span>
                                <span style="font-size: 0.82rem; font-weight: 800; color: #0f172a;">Buy Now Pay in 3 Installments</span>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase;">3x Installments</div>
                                <div id="koko-installment-amount" style="font-size: 0.92rem; font-weight: 900; color: #0284c7; font-family: 'Outfit', sans-serif;">3 x LKR 0.00</div>
                            </div>
                        </div>

                        <!-- Channel Tabs -->
                        <div style="display: flex; gap: 6px; margin-bottom: 0.75rem; background: #f1f5f9; padding: 4px; border-radius: 10px;">
                            <button type="button" class="koko-tab-btn active" id="tab-koko-qr" onclick="switchKokoTab('qr')" style="flex: 1; padding: 6px 10px; border-radius: 8px; border: none; font-size: 0.78rem; font-weight: 800; cursor: pointer; background: #0f172a; color: #ffffff; transition: all .15s;">
                                <i class="fa-solid fa-qrcode me-1"></i> Scan QR
                            </button>
                            <button type="button" class="koko-tab-btn" id="tab-koko-link" onclick="switchKokoTab('link')" style="flex: 1; padding: 6px 10px; border-radius: 8px; border: none; font-size: 0.78rem; font-weight: 800; cursor: pointer; background: transparent; color: #475569; transition: all .15s;">
                                <i class="fa-solid fa-link me-1"></i> Link
                            </button>
                            <button type="button" class="koko-tab-btn" id="tab-koko-sms" onclick="switchKokoTab('sms')" style="flex: 1; padding: 6px 10px; border-radius: 8px; border: none; font-size: 0.78rem; font-weight: 800; cursor: pointer; background: transparent; color: #475569; transition: all .15s;">
                                <i class="fa-solid fa-mobile-screen-button me-1"></i> SMS
                            </button>
                        </div>

                        <div id="koko-view-qr" style="text-align: center; padding: 0.35rem 0;">
                            <div id="koko-qr-box" style="display: inline-block; padding: 8px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 0.5rem; min-height: 180px; min-width: 180px;">
                                <div id="koko-qr-spinner" style="color: #64748b; font-size: 0.85rem; padding: 30px 0;"><i class="fa-solid fa-spinner fa-spin me-1"></i> Generating QR...</div>
                            </div>
                        </div>

                        <div id="koko-view-link" style="display: none; padding: 0.35rem 0;">
                            <div style="display: flex; gap: 6px; margin-bottom: 0.5rem;">
                                <input type="text" id="koko-link-input" readonly class="search-input" style="font-size: 0.8rem; padding: 0.5rem 0.75rem; background: #f8fafc; font-family: monospace;" value="Generating...">
                                <button type="button" class="btn-pos-action" onclick="copyKokoLink()" style="width: auto; padding: 0 1rem; font-size: 0.8rem; background: #0f172a; color: #fff; border-radius: 8px;">
                                    <i class="fa-solid fa-copy me-1"></i> Copy
                                </button>
                            </div>
                            <div style="text-align: right;">
                                <a id="koko-link-open" href="#" target="_blank" style="font-size: 0.75rem; font-weight: 800; color: #0284c7; text-decoration: none;">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Checkout
                                </a>
                            </div>
                        </div>

                        <div id="koko-view-sms" style="display: none; padding: 0.35rem 0;">
                            <div style="display: flex; gap: 6px; margin-bottom: 0.5rem;">
                                <input type="tel" id="koko-phone-input" class="search-input" placeholder="07XXXXXXXX or +947XXXXXXXX" style="font-size: 0.95rem; font-weight: 700; padding: 0.5rem 0.75rem;">
                                <button type="button" id="btn-koko-send-sms" class="btn-pos-action" onclick="sendKokoSms()" style="width: auto; padding: 0 1.1rem; font-size: 0.8rem; background: #0284c7; color: #fff; border-radius: 8px;">
                                    <i class="fa-solid fa-paper-plane me-1"></i> Send
                                </button>
                            </div>
                            <div id="koko-sms-status" style="font-size: 0.72rem; font-weight: 700; color: #64748b;"></div>
                        </div>

                        <!-- Status & Instant Verification -->
                        <div style="margin-top: 0.65rem; padding: 0.65rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span id="koko-status-indicator" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #eab308; box-shadow: 0 0 8px #eab308;"></span>
                                <div>
                                    <div id="koko-status-label" style="font-size: 0.8rem; font-weight: 800; color: #0f172a;">Awaiting Payment...</div>
                                    <div id="koko-order-ref-lbl" style="font-size: 0.7rem; color: #64748b; font-family: monospace;">Order: -</div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 6px;">
                                <button type="button" class="btn-pos-action" onclick="checkKokoStatusManual()" style="width: auto; padding: 4px 10px; font-size: 0.72rem; background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 8px;">
                                    <i class="fa-solid fa-rotate me-1"></i> Check
                                </button>
                                <button type="button" id="btn-koko-verify-instant" class="btn-pos-action" onclick="verifyKokoInstant()" style="width: auto; padding: 4px 12px; font-size: 0.72rem; background: #16a34a; color: #fff; border-radius: 8px;">
                                    <i class="fa-solid fa-check-circle me-1"></i> Verify
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Split Payment Action Bar -->
                    <div style="margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.75rem; color: #64748b;">
                            <i class="fa-solid fa-circle-info me-1"></i> Paying partially? Add this amount to split.
                        </span>
                        <button type="button" id="btn-add-split-line" class="btn-header-action" onclick="addSplitPaymentLine()" style="background: #eff6ff; color: #1d4ed8; border: 1.5px solid #bfdbfe; font-size: 0.82rem; font-weight: 800; padding: 0.5rem 1.1rem; border-radius: 8px; cursor: pointer; transition: all .15s;">
                            <i class="fa-solid fa-plus-circle me-1"></i> Add to Split (<span id="btn-split-amt-lbl">LKR 0.00</span>)
                        </button>
                    </div>
                </div>
            </div>
            <div class="pos-modal-footer" style="display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1.25rem;">
                <button type="button" class="btn-header-action" onclick="closeModal('modal-payment')">Back (Esc)</button>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="btn-pos-action btn-pos-pay" id="btn-finalize-sale" onclick="finalizeSale()" style="width: auto; grid-column: auto; padding: 0.75rem 2.2rem; font-size: 1.05rem; border-radius: 10px; font-weight: 800;">
                        <i class="fa-solid fa-check-circle me-1"></i> CONFIRM PAYMENT (Enter)
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
    <script src="assets/js/offline_pos.js"></script>
    <script>
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

        function updateNetworkStatusUI() {
            const statusElem = document.getElementById('pos-network-status');
            if (!statusElem) return;
            if (navigator.onLine) {
                statusElem.className = 'badge-status-online';
                statusElem.innerHTML = '<span class="pulse-dot"></span> <i class="fa-solid fa-wifi"></i> Online';
            } else {
                statusElem.className = 'badge-status-offline';
                statusElem.innerHTML = '<i class="fa-solid fa-wifi-slash"></i> Offline';
            }
            updatePendingBadge();
        }

        // Synchronize & refresh offline cache on load
        if (typeof CurtissOfflinePOS !== 'undefined') {
            CurtissOfflinePOS.refreshBillingCache();
        }

        window.addEventListener('online', () => {
            updateNetworkStatusUI();
            if (typeof CurtissOfflinePOS !== 'undefined') {
                CurtissOfflinePOS.refreshBillingCache();
                CurtissOfflinePOS.syncAllPendingSales(() => updatePendingBadge());
            }
        });
        window.addEventListener('offline', () => {
            updateNetworkStatusUI();
        });

        async function updatePendingBadge() {
            if (typeof CurtissOfflinePOS !== 'undefined') {
                const sales = await CurtissOfflinePOS.getAllOfflineSales();
                const pending = sales.filter(s => s.sync_status === 'Pending Sync' || s.sync_status === 'Failed');
                const badge = document.getElementById('sync-pending-badge');
                if (badge) {
                    if (pending.length > 0) {
                        badge.innerText = pending.length;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        }

        async function triggerManualSync() {
            if (!navigator.onLine) {
                alert("Device is offline. Cannot sync until internet connection is restored.");
                return;
            }
            if (typeof CurtissOfflinePOS !== 'undefined') {
                const btn = document.getElementById('btn-sync-offline');
                if (btn) btn.disabled = true;
                const res = await CurtissOfflinePOS.syncAllPendingSales();
                if (btn) btn.disabled = false;
                alert(`Sync Operation Completed:\n• Synced: ${res.syncedCount || 0}\n• Conflicts: ${res.conflictCount || 0}\n• Failures: ${res.failedCount || 0}`);
                updatePendingBadge();
                renderOfflineSalesQueueModal();
            }
        }

        async function openOfflineSyncModal() {
            await renderOfflineSalesQueueModal();
            openModal('modal-offline-sync-queue');
        }

        async function renderOfflineSalesQueueModal() {
            if (typeof CurtissOfflinePOS === 'undefined') return;
            const sales = await CurtissOfflinePOS.getAllOfflineSales();
            const container = document.getElementById('offline-sales-list');
            if (!container) return;

            if (sales.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:2rem; color: var(--pos-text-muted);">No offline sales recorded on this device.</div>';
                return;
            }

            let html = `
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Temp Ref</th>
                            <th>Date / Time</th>
                            <th>Customer</th>
                            <th>Total (LKR)</th>
                            <th>Status</th>
                            <th>Server Invoice / Message</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            sales.slice().reverse().forEach(s => {
                let badgeStyle = 'background:#fef3c7; color:#b45309;';
                if (s.sync_status === 'Synced') badgeStyle = 'background:#dcfce7; color:#15803d;';
                else if (s.sync_status === 'Conflict') badgeStyle = 'background:#fee2e2; color:#b91c1c;';
                else if (s.sync_status === 'Failed') badgeStyle = 'background:#f3f4f6; color:#374151;';

                const detail = s.server_invoice_no ? `Invoice #${s.server_invoice_no}` : (s.error_message || s.conflict_details ? escapeHtml(s.error_message || JSON.stringify(s.conflict_details)) : '-');

                html += `
                    <tr>
                        <td style="font-weight:700;">${escapeHtml(s.temp_ref)}</td>
                        <td style="font-size:0.8rem;">${escapeHtml(s.created_at)}</td>
                        <td>${escapeHtml(s.customer_name)}</td>
                        <td style="font-weight:700; text-align:right;">${parseFloat(s.total_amount).toFixed(2)}</td>
                        <td><span style="padding:0.2rem 0.5rem; border-radius:4px; font-weight:600; font-size:0.75rem; ${badgeStyle}">${escapeHtml(s.sync_status)}</span></td>
                        <td style="font-size:0.8rem; color:var(--pos-text-muted);">${detail}</td>
                    </tr>
                `;
            });

            html += `</tbody></table>`;
            container.innerHTML = html;
        }

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

        function updateSalesRepDisplay() {
            const nameEl = document.getElementById('rep-disp-name');
            const codeEl = document.getElementById('rep-disp-code');
            const clearBtn = document.getElementById('btn-clear-sales-rep');
            if (currentSalesRep) {
                if (nameEl) nameEl.innerText = currentSalesRep.full_name;
                if (codeEl) codeEl.innerText = `${currentSalesRep.employee_code} (${currentSalesRep.job_role || 'Sales Rep'})`;
                if (clearBtn) clearBtn.style.display = 'inline-block';
            } else {
                if (nameEl) nameEl.innerText = 'Direct Cashier / None';
                if (codeEl) codeEl.innerText = 'No Sales Rep Attributed';
                if (clearBtn) clearBtn.style.display = 'none';
            }
        }

        function clearSalesRep() {
            currentSalesRep = null;
            updateSalesRepDisplay();
            closeModal('modal-sales-rep-selector');
            setTimeout(() => {
                searchInput.focus();
                searchInput.select();
            }, 60);
        }

        function selectSalesRep(rep) {
            currentSalesRep = rep;
            updateSalesRepDisplay();
            closeModal('modal-sales-rep-selector');
            setTimeout(() => {
                searchInput.focus();
                searchInput.select();
            }, 60);
        }

        function selectSalesRepByIndex(idx) {
            if (currentSearchReps && currentSearchReps[idx]) {
                selectSalesRep(currentSearchReps[idx]);
            }
        }

        function openSalesRepModal() {
            openModal('modal-sales-rep-selector');
            const repInput = document.getElementById('rep-search-input');
            if (repInput) {
                repInput.value = '';
                performSalesRepSearch('');
                setTimeout(() => {
                    repInput.focus();
                    repInput.select();
                }, 100);
            }
        }

        async function performSalesRepSearch(q) {
            q = (q || '').trim();
            try {
                let reps = [];
                let onlineSuccess = false;

                if (navigator.onLine) {
                    try {
                        const res = await fetch(`pos_api.php?action=search_sales_reps&q=${encodeURIComponent(q)}`);
                        const data = await res.json();
                        if (data.success && Array.isArray(data.data)) {
                            reps = data.data;
                            onlineSuccess = true;
                        }
                    } catch (e) {
                        console.warn("Online sales rep search failed, falling back to cache", e);
                    }
                }

                if (!onlineSuccess && typeof CurtissOfflinePOS !== 'undefined') {
                    const cached = await CurtissOfflinePOS.getCachedData('sales_reps');
                    if (cached && cached.length > 0) {
                        if (!q) {
                            reps = cached;
                        } else {
                            const qLower = q.toLowerCase();
                            reps = cached.filter(r =>
                                (r.full_name && r.full_name.toLowerCase().includes(qLower)) ||
                                (r.employee_code && r.employee_code.toLowerCase().includes(qLower)) ||
                                (r.phone && r.phone.includes(qLower))
                            );
                        }
                    }
                }

                currentSearchReps = reps;
                selectedRepIndex = reps.length > 0 ? 0 : -1;
                renderSalesRepSearchResults(currentSearchReps);
            } catch (err) {
                console.error("Sales rep search error:", err);
            }
        }

        function updateSalesRepHighlight() {
            const cards = document.querySelectorAll('#rep-search-results .cust-item-card');
            cards.forEach((card, idx) => {
                if (idx === selectedRepIndex) {
                    card.classList.add('selected');
                    card.scrollIntoView({ block: 'nearest' });
                } else {
                    card.classList.remove('selected');
                }
            });
        }

        function renderSalesRepSearchResults(reps) {
            const container = document.getElementById('rep-search-results');
            if (!container) return;
            if (!reps || reps.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:1.5rem; color: var(--pos-text-muted); font-weight: 600;">No active branch sales representatives found.</div>';
                return;
            }

            let html = '';
            reps.forEach((r, idx) => {
                const isSelected = idx === selectedRepIndex ? 'selected' : '';
                const isCurrent = currentSalesRep && currentSalesRep.id == r.id;
                html += `
                    <div class="cust-item-card ${isSelected}" data-index="${idx}" onclick="selectSalesRepByIndex(${idx})" style="border: 1px solid ${isCurrent ? '#0f172a' : 'var(--pos-border)'};">
                        <div>
                            <div style="font-weight: 800; color: var(--pos-text); font-size: 1rem; display: flex; align-items: center; gap: 6px;">
                                ${escapeHtml(r.full_name)} <span style="font-size: 0.78rem; font-weight: 700; color: #64748b;">(${escapeHtml(r.employee_code || '')})</span>
                                ${isCurrent ? '<span style="font-size:0.68rem; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:999px; font-weight:800;">SELECTED</span>' : ''}
                            </div>
                            <div style="font-size: 0.8rem; color: var(--pos-text-muted); margin-top: 3px;">
                                Role: <strong>${escapeHtml(r.job_role || 'Sales Rep')}</strong> | Phone: <strong>${escapeHtml(r.phone || 'N/A')}</strong>
                            </div>
                        </div>
                        <button class="btn-customer-select" type="button">Select</button>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        const repInputElem = document.getElementById('rep-search-input');
        if (repInputElem) {
            repInputElem.addEventListener('input', (e) => {
                clearTimeout(repSearchTimer);
                const q = e.target.value;
                repSearchTimer = setTimeout(() => {
                    performSalesRepSearch(q);
                }, 60);
            });

            repInputElem.addEventListener('keydown', (e) => {
                if (currentSearchReps.length > 0) {
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        selectedRepIndex = (selectedRepIndex + 1) % currentSearchReps.length;
                        updateSalesRepHighlight();
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        selectedRepIndex = (selectedRepIndex - 1 + currentSearchReps.length) % currentSearchReps.length;
                        updateSalesRepHighlight();
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (selectedRepIndex >= 0 && currentSearchReps[selectedRepIndex]) {
                            selectSalesRepByIndex(selectedRepIndex);
                        } else {
                            closeModal('modal-sales-rep-selector');
                            setTimeout(() => {
                                searchInput.focus();
                                searchInput.select();
                            }, 60);
                        }
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    closeModal('modal-sales-rep-selector');
                    setTimeout(() => {
                        searchInput.focus();
                        searchInput.select();
                    }, 60);
                }
            });
        }

        // --- HYBRID ONLINE/OFFLINE REAL-TIME PRODUCT SEARCH ENGINE ---
        let productSearchTimer = null;

        async function performProductSearch(val) {
            val = val.trim();
            if (!val) {
                searchDropdown.style.display = 'none';
                currentSearchProducts = [];
                selectedSearchIndex = -1;
                return;
            }

            try {
                let products = [];
                let onlineSuccess = false;

                if (navigator.onLine) {
                    try {
                        const res = await fetch(`pos_api.php?action=search_products&q=${encodeURIComponent(val)}`);
                        const data = await res.json();
                        if (data.success && Array.isArray(data.data)) {
                            products = data.data;
                            onlineSuccess = true;
                        }
                    } catch (e) {
                        console.warn("Online search failed, using local offline cache", e);
                    }
                }

                // Fallback to local IndexedDB cache ONLY if offline / network fetch failed
                if (!onlineSuccess && typeof CurtissOfflinePOS !== 'undefined') {
                    const cached = await CurtissOfflinePOS.getCachedData('products');
                    const cachedLots = await CurtissOfflinePOS.getCachedData('lots') || [];
                    if (cached && cached.length > 0) {
                        const qLower = val.toLowerCase();
                        const matched = cached.filter(p => 
                            (p.name && p.name.toLowerCase().includes(qLower)) || 
                            (p.barcode && p.barcode.toLowerCase().includes(qLower)) ||
                            (p.sku && p.sku.toLowerCase().includes(qLower))
                        );

                        if (matched.length > 0) {
                            products = matched.map(p => {
                                const prodLots = cachedLots.filter(l => l.product_id == p.id && parseFloat(l.remaining_quantity || 0) > 0);
                                const totalStock = prodLots.reduce((sum, l) => sum + parseFloat(l.remaining_quantity || 0), 0);
                                return {
                                    product_id: parseInt(p.id),
                                    sku: p.sku,
                                    barcode: p.barcode,
                                    product_name: p.name,
                                    default_cost: parseFloat(p.cost || 0),
                                    default_price: parseFloat(p.selling_price || 0),
                                    brand_name: 'Store Product',
                                    category_name: 'General',
                                    total_branch_stock: totalStock,
                                    lots: prodLots.map(l => ({
                                        lot_id: parseInt(l.id),
                                        lot_number: l.lot_number,
                                        product_id: parseInt(l.product_id),
                                        selling_price: parseFloat(l.selling_price),
                                        remaining_quantity: parseFloat(l.remaining_quantity)
                                    })),
                                    active_lots_count: prodLots.length,
                                    auto_selected_lot: prodLots.length > 0 ? {
                                        lot_id: parseInt(prodLots[0].id),
                                        lot_number: prodLots[0].lot_number,
                                        product_id: parseInt(prodLots[0].product_id),
                                        selling_price: parseFloat(prodLots[0].selling_price),
                                        remaining_quantity: parseFloat(prodLots[0].remaining_quantity)
                                    } : null
                                };
                            });
                        }
                    }
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

        // Paste event listener for direct barcode pasting (Ctrl+V or Context Menu Paste)
        searchInput.addEventListener('paste', (e) => {
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            if (pastedText && pastedText.trim().length > 0) {
                setTimeout(() => {
                    handleBarcodeOrSearchSubmit(pastedText.trim());
                }, 50);
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
            }, 50);
        });

        searchInput.addEventListener('focus', () => {
            if (searchInput.value.trim().length > 0) {
                performProductSearch(searchInput.value);
            }
        });

        searchInput.addEventListener('keydown', async (e) => {
            // Guard: If lot modal or any overlay modal is open, ignore searchInput key navigation
            if (document.getElementById('modal-lot-selector').classList.contains('active') ||
                document.getElementById('modal-payment').classList.contains('active') ||
                document.getElementById('modal-invoice-complete').classList.contains('active')) {
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

                let onlineSuccess = false;
                if (navigator.onLine) {
                    try {
                        const res = await fetch(`pos_api.php?action=search_products&q=${encodeURIComponent(val)}`);
                        const data = await res.json();
                        if (data.success && Array.isArray(data.data)) {
                            products = data.data;
                            onlineSuccess = true;
                        }
                    } catch (e) {
                        console.warn("Online barcode search failed, using local offline cache", e);
                    }
                }

                if (!onlineSuccess && typeof CurtissOfflinePOS !== 'undefined') {
                    const cached = await CurtissOfflinePOS.getCachedData('products');
                    const cachedLots = await CurtissOfflinePOS.getCachedData('lots') || [];
                    if (cached && cached.length > 0) {
                        const qLower = val.toLowerCase();
                        const matched = cached.filter(p => {
                            const bc = String(p.barcode || '').trim().toLowerCase();
                            const sku = String(p.sku || '').trim().toLowerCase();
                            const name = String(p.name || '').trim().toLowerCase();
                            return bc === qLower || sku === qLower || name.includes(qLower);
                        });

                        if (matched.length > 0) {
                            products = matched.map(p => {
                                const prodLots = cachedLots.filter(l => l.product_id == p.id && parseFloat(l.remaining_quantity || 0) > 0);
                                const totalStock = prodLots.reduce((sum, l) => sum + parseFloat(l.remaining_quantity || 0), 0);
                                return {
                                    product_id: parseInt(p.id),
                                    sku: p.sku,
                                    barcode: p.barcode,
                                    product_name: p.name,
                                    default_cost: parseFloat(p.cost || 0),
                                    default_price: parseFloat(p.selling_price || 0),
                                    brand_name: 'Store Product',
                                    category_name: 'General',
                                    total_branch_stock: totalStock,
                                    lots: prodLots.map(l => ({
                                        lot_id: parseInt(l.id),
                                        lot_number: l.lot_number,
                                        product_id: parseInt(l.product_id),
                                        selling_price: parseFloat(l.selling_price),
                                        remaining_quantity: parseFloat(l.remaining_quantity)
                                    })),
                                    active_lots_count: prodLots.length,
                                    auto_selected_lot: prodLots.length > 0 ? {
                                        lot_id: parseInt(prodLots[0].id),
                                        lot_number: prodLots[0].lot_number,
                                        product_id: parseInt(prodLots[0].product_id),
                                        selling_price: parseFloat(prodLots[0].selling_price),
                                        remaining_quantity: parseFloat(prodLots[0].remaining_quantity)
                                    } : null
                                };
                            });
                        }
                    }
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

            if (product.active_lots_count === 0 && availableLots.length === 0 && parseFloat(product.total_branch_stock || 0) <= 0) {
                alert(`Product "${product.product_name}" has no available stock in this branch.`);
                return;
            }

            // If product already has available lots attached from search
            if (availableLots.length > 0) {
                if (availableLots.length > 1) {
                    showAutomaticLotModal(product, availableLots);
                } else {
                    addItemToCartWithLot(product, availableLots[0]);
                }
                return;
            }

            // Always fetch/check active lots with available stock for this product if not already attached
            fetch(`pos_api.php?action=get_product_lots&product_id=${product.product_id}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.data && res.data.length > 0) {
                        const lots = res.data.filter(l => parseFloat(l.remaining_quantity) > 0);
                        product.lots = lots;

                        if (lots.length > 1) {
                            showAutomaticLotModal(product, lots);
                        } else if (lots.length === 1) {
                            addItemToCartWithLot(product, lots[0]);
                        } else {
                            alert(`Product "${product.product_name}" has no available stock lots in this branch.`);
                        }
                    } else if (product.auto_selected_lot && parseFloat(product.auto_selected_lot.remaining_quantity) > 0) {
                        addItemToCartWithLot(product, product.auto_selected_lot);
                    } else {
                        alert(`Product "${product.product_name}" has no available stock lots in this branch.`);
                    }
                })
                .catch(err => {
                    console.error("Error fetching product lots:", err);
                    if (product.auto_selected_lot && parseFloat(product.auto_selected_lot.remaining_quantity) > 0) {
                        addItemToCartWithLot(product, product.auto_selected_lot);
                    }
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
            if (maxStock <= 0) {
                alert(`Cannot add item. Lot "${selectedLot.lot_number}" has 0 available stock.`);
                return;
            }

            const existingIndex = cart.findIndex(i => i.product_id === product.product_id && i.lot_id === selectedLot.lot_id);
            if (existingIndex > -1) {
                const newQty = cart[existingIndex].quantity + 1;
                if (newQty > maxStock) {
                    alert(`Cannot bill over available stock! Only ${maxStock} units available for lot "${selectedLot.lot_number}".`);
                    return;
                }
                const existingItem = cart.splice(existingIndex, 1)[0];
                existingItem.quantity = newQty;
                cart.unshift(existingItem);
            } else {
                cart.unshift({
                    product_id: product.product_id,
                    product_name: product.product_name,
                    barcode: product.barcode,
                    lot_id: selectedLot.lot_id,
                    lot_number: selectedLot.lot_number,
                    lot_remaining_stock: parseFloat(selectedLot.remaining_quantity),
                    all_lots: product.lots || [selectedLot],
                    quantity: 1.00,
                    original_price: parseFloat(selectedLot.selling_price),
                    unit_price: parseFloat(selectedLot.selling_price),
                    discount_value: ''
                });
            }

            renderCart();
            setTimeout(() => {
                if (searchInput) searchInput.focus();
            }, 50);
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
                const itemSubtotal = item.unit_price * item.quantity;
                const parsedItemDisc = parseDiscountJS(item.discount_value, itemSubtotal);
                const lineTotalNum = Math.max(0, itemSubtotal - parsedItemDisc.total_discount);
                const lineTotal = lineTotalNum.toFixed(2);
                item.discount_amount = parsedItemDisc.total_discount;
                const isMultiLot = item.all_lots && item.all_lots.length > 1;

                html += `
                    <tr>
                        <td style="font-weight:700; color:var(--pos-text-muted);">${index + 1}</td>
                        <td>
                            <div style="font-weight: 800; color: var(--pos-text); font-size: 0.95rem;">${escapeHtml(item.product_name)}</div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 3px; flex-wrap: wrap;">
                                <span style="font-size: 0.75rem; color: var(--pos-text-muted); font-family: monospace;">${escapeHtml(item.barcode)}</span>
                                <span class="lot-badge-inline" onclick="openLotSelectorModal(${index})" title="${isMultiLot ? 'Click to change stock lot' : 'Active stock lot'}">
                                    <i class="fa-solid fa-layer-group"></i> ${escapeHtml(item.lot_number)} (${item.lot_remaining_stock} avail)
                                    ${isMultiLot ? '<i class="fa-solid fa-chevron-down" style="font-size:0.6rem;"></i>' : ''}
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="qty-controls">
                                <button type="button" class="btn-qty" onclick="updateItemQty(${index}, -1)">-</button>
                                <input type="number" id="cart-item-qty-${index}" class="qty-input item-qty-input" value="${item.quantity}" min="1" max="${item.lot_remaining_stock}" step="1" oninput="validateQtyRealtime(this, ${index})" onchange="setItemQty(${index}, this.value)">
                                <button type="button" class="btn-qty" onclick="updateItemQty(${index}, 1)">+</button>
                            </div>
                        </td>
                        <td>
                            <input type="number" class="price-override-input" value="${item.unit_price.toFixed(2)}" step="0.50" 
                                   ${!CAN_OVERRIDE_PRICE ? 'disabled' : ''} onchange="setItemPrice(${index}, this.value)">
                        </td>
                        <td>
                            <input type="text" id="cart-item-disc-${index}" class="item-discount-input" value="${escapeHtml(item.discount_value)}" placeholder="0 / %" 
                                   ${!CAN_DISCOUNT ? 'disabled' : ''} onchange="setItemDiscount(${index}, this.value)">
                        </td>
                        <td style="font-family: 'Outfit', sans-serif; font-weight: 900; color: var(--pos-primary); font-size:1.05rem;">LKR ${lineTotal}</td>
                        <td style="text-align: center;">
                            <button type="button" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size:1rem; padding:4px;" onclick="removeFromCart(${index})" title="Remove Item">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            cartTableBody.innerHTML = html;
            calculateTotals();
        }

        function validateQtyRealtime(inputEl, index) {
            const maxStock = parseFloat(cart[index].lot_remaining_stock) || 0;
            const entered = parseFloat(inputEl.value);
            if (!isNaN(entered) && entered > maxStock) {
                alert(`Cannot bill over available stock! Available stock for "${cart[index].product_name}" (Lot: ${cart[index].lot_number}) is ${maxStock} units.`);
                inputEl.value = maxStock;
                cart[index].quantity = maxStock;
                calculateTotals();
            }
        }

        function updateItemQty(index, delta) {
            const maxStock = parseFloat(cart[index].lot_remaining_stock) || 0;
            const newQty = cart[index].quantity + delta;
            if (newQty <= 0) {
                removeFromCart(index);
                return;
            }
            if (newQty > maxStock) {
                alert(`Cannot bill over available stock! Only ${maxStock} units available in stock.`);
                return;
            }
            cart[index].quantity = newQty;
            renderCart();
        }

        function setItemQty(index, value) {
            const qty = parseFloat(value);
            const maxStock = parseFloat(cart[index].lot_remaining_stock) || 0;
            if (isNaN(qty) || qty <= 0) {
                removeFromCart(index);
                return;
            }
            if (qty > maxStock) {
                alert(`Cannot bill over available stock! Only ${maxStock} units available in stock.`);
                cart[index].quantity = maxStock;
            } else {
                cart[index].quantity = qty;
            }
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

        function calculateTotals() {
            let subtotal = 0.00;
            cart.forEach(item => {
                const itemSubtotal = item.unit_price * item.quantity;
                const parsedItemDisc = parseDiscountJS(item.discount_value, itemSubtotal);
                subtotal += Math.max(0, itemSubtotal - parsedItemDisc.total_discount);
            });

            currentSubtotal = subtotal;
            summarySubtotal.innerText = `LKR ${subtotal.toFixed(2)}`;

            const invDiscountStr = summaryDiscountInput.value.trim();
            const parsedInvDisc = parseDiscountJS(invDiscountStr, subtotal);
            currentTotalDiscount = parsedInvDisc.total_discount;
            currentPayableTotal = Math.max(0, subtotal - currentTotalDiscount);

            summaryDiscountVal.innerText = (currentTotalDiscount >= 0 ? `- LKR ` : `+ LKR `) + Math.abs(currentTotalDiscount).toFixed(2);
            summaryPayable.innerText = `LKR ${currentPayableTotal.toFixed(2)}`;

            fetch(`pos_api.php?action=parse_discount&discount=${encodeURIComponent(invDiscountStr)}&subtotal=${subtotal}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        currentTotalDiscount = res.parsed.total_discount;
                        currentPayableTotal = Math.max(0, subtotal - currentTotalDiscount);
                        
                        summaryDiscountVal.innerText = (currentTotalDiscount >= 0 ? `- LKR ` : `+ LKR `) + Math.abs(currentTotalDiscount).toFixed(2);
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
                        const availableLots = res.data.filter(l => parseFloat(l.remaining_quantity) > 0);
                        if (availableLots.length === 0) {
                            alert(`No available stock lots found for "${item.product_name}" in this branch.`);
                            return;
                        }
                        let html = '';
                        availableLots.forEach((lot, idx) => {
                            const isSelected = idx === 0 ? 'selected' : '';
                            html += `
                                <div class="lot-card ${isSelected}" data-index="${idx}" onclick="selectLotForCartItem(${lot.lot_id}, '${escapeHtml(lot.lot_number)}', ${parseFloat(lot.selling_price)}, ${parseFloat(lot.remaining_quantity)})">
                                    <div>
                                        <div style="font-weight: 800; color: var(--pos-text);">${escapeHtml(lot.lot_number)}</div>
                                        <div style="font-size: 0.8rem; color: var(--pos-text-muted);">Available Stock: <strong style="color:#047857;">${parseFloat(lot.remaining_quantity)} units</strong></div>
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
                items: cart
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
                            barcode: item.barcode,
                            lot_id: parseInt(item.lot_id),
                            lot_number: item.lot_number,
                            lot_remaining_stock: parseFloat(item.lot_remaining_stock),
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

        function openCustomerModal() {
            openModal('modal-customer-selector');
            custSearchInput.value = '';
            performCustomerSearch('');
            setTimeout(() => {
                custSearchInput.focus();
                custSearchInput.select();
            }, 100);
        }

        function selectWalkinCustomer() {
            fetch('pos_api.php?action=get_walkin_customer')
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data) {
                        currentCustomer = res.data;
                    } else {
                        currentCustomer = { id: 1, name: 'Walk-in Customer', customer_code: 'CUST-WALKIN', is_walkin: 1 };
                    }
                    updateCustomerDisplay();
                    closeModal('modal-customer-selector');
                    setTimeout(() => {
                        openSalesRepModal();
                    }, 60);
                })
                .catch(() => {
                    currentCustomer = { id: 1, name: 'Walk-in Customer', customer_code: 'CUST-WALKIN', is_walkin: 1 };
                    updateCustomerDisplay();
                    closeModal('modal-customer-selector');
                    setTimeout(() => {
                        openSalesRepModal();
                    }, 60);
                });
        }

        document.getElementById('btn-change-customer').addEventListener('click', (e) => {
            e.stopPropagation();
            openCustomerModal();
        });

        async function performCustomerSearch(q) {
            q = (q || '').trim();
            try {
                let customers = [];
                let onlineSuccess = false;

                if (navigator.onLine) {
                    try {
                        const res = await fetch(`pos_api.php?action=search_customers&q=${encodeURIComponent(q)}`);
                        const data = await res.json();
                        if (data.success && Array.isArray(data.data)) {
                            customers = data.data;
                            onlineSuccess = true;
                        }
                    } catch (e) {
                        console.warn("Online customer search failed, falling back to cache", e);
                    }
                }

                if (!onlineSuccess && typeof CurtissOfflinePOS !== 'undefined') {
                    const cached = await CurtissOfflinePOS.getCachedData('customers');
                    if (cached && cached.length > 0) {
                        if (!q) {
                            customers = cached.slice(0, 15);
                        } else {
                            const qLower = q.toLowerCase();
                            customers = cached.filter(c => 
                                (c.name && c.name.toLowerCase().includes(qLower)) ||
                                (c.phone && c.phone.includes(qLower)) ||
                                (c.customer_code && c.customer_code.toLowerCase().includes(qLower)) ||
                                (c.card_barcode && c.card_barcode.toLowerCase().includes(qLower))
                            );
                        }
                    }
                }

                currentSearchCustomers = customers;
                selectedCustIndex = (customers.length > 0 || q.length > 0) ? 0 : -1;
                renderCustomerSearchResults(currentSearchCustomers);
            } catch (err) {
                console.error("Customer search error:", err);
            }
        }

        custSearchInput.addEventListener('input', (e) => {
            clearTimeout(custSearchTimer);
            const q = e.target.value;
            custSearchTimer = setTimeout(() => {
                performCustomerSearch(q);
            }, 60);
        });

        custSearchInput.addEventListener('keydown', (e) => {
            const q = custSearchInput.value.trim();
            const hasQuery = q.length > 0;
            const totalCount = currentSearchCustomers.length + (hasQuery ? 1 : 0);

            if (totalCount > 0) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedCustIndex = (selectedCustIndex + 1) % totalCount;
                    updateCustomerHighlight();
                    return;
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedCustIndex = (selectedCustIndex - 1 + totalCount) % totalCount;
                    updateCustomerHighlight();
                    return;
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (hasQuery && selectedCustIndex === currentSearchCustomers.length) {
                        openQuickCustomerModal(q);
                    } else if (selectedCustIndex >= 0 && currentSearchCustomers[selectedCustIndex]) {
                        selectCustomerByIndex(selectedCustIndex);
                    } else if (hasQuery) {
                        openQuickCustomerModal(q);
                    }
                    return;
                }
            } else if (hasQuery && e.key === 'Enter') {
                e.preventDefault();
                openQuickCustomerModal(q);
            }
        });

        function updateCustomerHighlight() {
            const cards = document.querySelectorAll('#cust-search-results .cust-item-card');
            cards.forEach((card, idx) => {
                if (idx === selectedCustIndex) {
                    card.classList.add('selected');
                    card.scrollIntoView({ block: 'nearest' });
                } else {
                    card.classList.remove('selected');
                }
            });
        }

        function renderCustomerSearchResults(customers) {
            const container = document.getElementById('cust-search-results');
            const q = custSearchInput.value.trim();
            const hasQuery = q.length > 0;

            if (!customers || customers.length === 0) {
                if (hasQuery) {
                    selectedCustIndex = 0;
                    container.innerHTML = `
                        <div class="cust-item-card quick-create-card selected" data-index="0" onclick="openQuickCustomerModal('${escapeHtml(q)}')" style="border: 1.5px dashed #0284c7; background: #f0f9ff; cursor: pointer; display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-radius: 12px; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                                    <i class="fa-solid fa-user-plus"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 800; color: #0369a1; font-size: 0.95rem;">
                                        + Create New Customer "${escapeHtml(q)}"
                                    </div>
                                    <div style="font-size: 0.78rem; color: #0284c7; margin-top: 2px;">
                                        No customer found. Press <kbd style="background: #fff; border: 1px solid #bae6fd; padding: 1px 5px; border-radius: 4px; font-weight: 700;">Enter</kbd> or click to quickly create.
                                    </div>
                                </div>
                            </div>
                            <button class="btn-customer-select" type="button" style="background: #0284c7; color: #fff; border: none; padding: 6px 14px; border-radius: 999px; font-weight: 700; cursor: pointer;">+ Create</button>
                        </div>
                    `;
                    return;
                } else {
                    container.innerHTML = '<div style="text-align:center; padding:1.5rem; color: var(--pos-text-muted); font-weight: 600;">No active customers found.</div>';
                    return;
                }
            }

            let html = '';
            customers.forEach((c, idx) => {
                const isSelected = idx === selectedCustIndex ? 'selected' : '';
                const isCurrent = currentCustomer && currentCustomer.id == c.id;
                const isLoyalty = (c.is_loyalty == 1 || c.is_loyalty === '1');
                const loyaltyDiscPct = (c.loyalty_discount_override && parseFloat(c.loyalty_discount_override) > 0) ? parseFloat(c.loyalty_discount_override) : DEFAULT_LOYALTY_DISCOUNT_PERCENTAGE;
                const loyaltyBadge = isLoyalty ? `<span style="font-size:0.68rem; background:#f5f3ff; color:#7c3aed; border:1px solid #ddd6fe; padding:2px 8px; border-radius:999px; font-weight:800;">★ LOYALTY (${loyaltyDiscPct}%)</span>` : '';

                html += `
                    <div class="cust-item-card ${isSelected}" data-index="${idx}" onclick="selectCustomerByIndex(${idx})" style="border: 1px solid ${isCurrent ? '#0f172a' : 'var(--pos-border)'};">
                        <div>
                            <div style="font-weight: 800; color: var(--pos-text); font-size: 1rem; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                ${escapeHtml(c.name)} <span style="font-size: 0.78rem; font-weight: 700; color: #64748b;">(${escapeHtml(c.customer_code || '')})</span>
                                ${loyaltyBadge}
                                ${isCurrent ? '<span style="font-size:0.68rem; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:999px; font-weight:800;">CURRENT</span>' : ''}
                            </div>
                            <div style="font-size: 0.8rem; color: var(--pos-text-muted); margin-top: 3px;">
                                Phone: <strong>${escapeHtml(c.phone || 'N/A')}</strong> | Card: <strong>${escapeHtml(c.card_barcode || 'N/A')}</strong>
                                ${parseFloat(c.credit_balance || 0) > 0 ? ` | Outstanding: <span style="color:#dc2626; font-weight:700;">LKR ${parseFloat(c.credit_balance).toFixed(2)}</span>` : ''}
                            </div>
                        </div>
                        <button class="btn-customer-select" type="button">Select</button>
                    </div>
                `;
            });

            if (hasQuery) {
                const isCreateSelected = selectedCustIndex === customers.length ? 'selected' : '';
                html += `
                    <div class="cust-item-card quick-create-card ${isCreateSelected}" data-index="${customers.length}" onclick="openQuickCustomerModal('${escapeHtml(q)}')" style="border: 1.5px dashed #0284c7; background: #f0f9ff; cursor: pointer; display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-radius: 12px; margin-top: 6px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>
                            <div>
                                <div style="font-weight: 800; color: #0369a1; font-size: 0.95rem;">
                                    + Create New Customer "${escapeHtml(q)}"
                                </div>
                                <div style="font-size: 0.78rem; color: #0284c7; margin-top: 2px;">
                                    Press <kbd style="background: #fff; border: 1px solid #bae6fd; padding: 1px 5px; border-radius: 4px; font-weight: 700;">Enter</kbd> or click to quickly create
                                </div>
                            </div>
                        </div>
                        <button class="btn-customer-select" type="button" style="background: #0284c7; color: #fff; border: none; padding: 6px 14px; border-radius: 999px; font-weight: 700; cursor: pointer;">+ Create</button>
                    </div>
                `;
            }

            container.innerHTML = html;
        }

        // --- QUICK CREATE CUSTOMER MODAL LOGIC & KEYBOARD INTERACTION ---
        const quickCustNameInput = document.getElementById('quick-cust-name');
        const quickCustPhoneInput = document.getElementById('quick-cust-phone');
        const quickCustWaInput = document.getElementById('quick-cust-whatsapp');
        const quickCustSameWaCheck = document.getElementById('quick-cust-same-wa');
        const quickCustErrorEl = document.getElementById('quick-cust-error');
        const btnSaveQuickCust = document.getElementById('btn-save-quick-cust');

        function openQuickCustomerModal(query) {
            query = (query || '').trim();
            if (quickCustErrorEl) {
                quickCustErrorEl.style.display = 'none';
                quickCustErrorEl.textContent = '';
            }

            const cleanDigits = query.replace(/[^0-9]/g, '');
            // Check if input looks like a phone number: at least 7 digits, or mostly numeric
            const isPhone = /^[0-9+\s\-()]{7,}$/.test(query) || (cleanDigits.length >= 7 && (cleanDigits.length / Math.max(1, query.length) > 0.7));

            if (isPhone) {
                const phone10 = cleanDigits.slice(0, 10);
                if (quickCustPhoneInput) quickCustPhoneInput.value = phone10;
                if (quickCustWaInput) quickCustWaInput.value = phone10;
                if (quickCustSameWaCheck) quickCustSameWaCheck.checked = true;
                if (quickCustNameInput) quickCustNameInput.value = '';
                openModal('modal-quick-customer');
                setTimeout(() => {
                    if (quickCustNameInput) quickCustNameInput.focus();
                }, 100);
            } else {
                if (quickCustNameInput) quickCustNameInput.value = query;
                if (quickCustPhoneInput) quickCustPhoneInput.value = '';
                if (quickCustWaInput) quickCustWaInput.value = '';
                if (quickCustSameWaCheck) quickCustSameWaCheck.checked = false;
                openModal('modal-quick-customer');
                setTimeout(() => {
                    if (quickCustPhoneInput) quickCustPhoneInput.focus();
                }, 100);
            }
        }

        function closeQuickCustomerModal() {
            closeModal('modal-quick-customer');
            setTimeout(() => {
                custSearchInput.focus();
                custSearchInput.select();
            }, 60);
        }

        if (quickCustPhoneInput) {
            quickCustPhoneInput.addEventListener('input', () => {
                quickCustPhoneInput.value = quickCustPhoneInput.value.replace(/[^0-9]/g, '').slice(0, 10);
                if (quickCustSameWaCheck && quickCustSameWaCheck.checked) {
                    quickCustWaInput.value = quickCustPhoneInput.value;
                }
            });

            quickCustPhoneInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (quickCustSameWaCheck && quickCustSameWaCheck.checked && !quickCustWaInput.value) {
                        quickCustWaInput.value = quickCustPhoneInput.value;
                    }
                    if (quickCustWaInput) {
                        quickCustWaInput.focus();
                        quickCustWaInput.select();
                    }
                }
            });
        }

        if (quickCustWaInput) {
            quickCustWaInput.addEventListener('input', () => {
                quickCustWaInput.value = quickCustWaInput.value.replace(/[^0-9]/g, '').slice(0, 10);
                if (quickCustSameWaCheck && quickCustPhoneInput && quickCustPhoneInput.value !== quickCustWaInput.value) {
                    quickCustSameWaCheck.checked = false;
                }
            });

            quickCustWaInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitQuickCustomer();
                }
            });
        }

        if (quickCustSameWaCheck) {
            quickCustSameWaCheck.addEventListener('change', () => {
                if (quickCustSameWaCheck.checked && quickCustPhoneInput && quickCustWaInput) {
                    quickCustWaInput.value = quickCustPhoneInput.value;
                }
            });
        }

        if (quickCustNameInput) {
            quickCustNameInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (!quickCustNameInput.value.trim()) {
                        if (quickCustErrorEl) {
                            quickCustErrorEl.textContent = 'Customer name is required.';
                            quickCustErrorEl.style.display = 'block';
                        }
                        quickCustNameInput.focus();
                        return;
                    }
                    if (quickCustErrorEl) quickCustErrorEl.style.display = 'none';
                    if (quickCustPhoneInput) {
                        quickCustPhoneInput.focus();
                        quickCustPhoneInput.select();
                    }
                }
            });
        }

        async function submitQuickCustomer() {
            const name = quickCustNameInput ? quickCustNameInput.value.trim() : '';
            const phone = quickCustPhoneInput ? quickCustPhoneInput.value.trim() : '';
            const wa = quickCustWaInput ? quickCustWaInput.value.trim() : '';

            if (!name) {
                if (quickCustErrorEl) {
                    quickCustErrorEl.textContent = 'Customer name is required.';
                    quickCustErrorEl.style.display = 'block';
                }
                if (quickCustNameInput) quickCustNameInput.focus();
                return;
            }

            if (quickCustErrorEl) quickCustErrorEl.style.display = 'none';
            if (btnSaveQuickCust) {
                btnSaveQuickCust.disabled = true;
                btnSaveQuickCust.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            }

            try {
                const response = await fetch('pos_api.php?action=quick_create_customer', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: name,
                        phone: phone,
                        whatsapp: wa
                    })
                });
                const res = await response.json();

                if (res.success && res.customer) {
                    currentCustomer = res.customer;
                    updateCustomerDisplay();
                    closeModal('modal-quick-customer');
                    closeModal('modal-customer-selector');

                    if (btnSaveQuickCust) {
                        btnSaveQuickCust.disabled = false;
                        btnSaveQuickCust.innerHTML = '<i class="fa-solid fa-check"></i> Save & Select (Enter)';
                    }

                    // Auto-focus directly on Sales Rep selection popup modal search bar
                    setTimeout(() => {
                        openSalesRepModal();
                    }, 100);
                } else {
                    if (btnSaveQuickCust) {
                        btnSaveQuickCust.disabled = false;
                        btnSaveQuickCust.innerHTML = '<i class="fa-solid fa-check"></i> Save & Select (Enter)';
                    }
                    if (quickCustErrorEl) {
                        quickCustErrorEl.textContent = res.message || 'Failed to create customer.';
                        quickCustErrorEl.style.display = 'block';
                    }
                }
            } catch (err) {
                console.error("Quick customer creation failed", err);
                if (btnSaveQuickCust) {
                    btnSaveQuickCust.disabled = false;
                    btnSaveQuickCust.innerHTML = '<i class="fa-solid fa-check"></i> Save & Select (Enter)';
                }
                if (quickCustErrorEl) {
                    quickCustErrorEl.textContent = 'Network or server error while creating customer.';
                    quickCustErrorEl.style.display = 'block';
                }
            }
        }

        let pendingLoyaltyCustomer = null;
        let pendingLoyaltyDiscountPct = 0;
        let loyaltyChoiceIndex = 1; // 0 = Cancel, 1 = Apply
        let lastLoyaltyModalOpenTime = 0;

        function showLoyaltyPromptModal(cust, discountPct) {
            pendingLoyaltyCustomer = cust;
            pendingLoyaltyDiscountPct = discountPct;
            loyaltyChoiceIndex = 1;
            lastLoyaltyModalOpenTime = Date.now();

            const nameEl = document.getElementById('loyalty-modal-cust-name');
            const badgeEl = document.getElementById('loyalty-modal-pct-badge');
            const descEl = document.getElementById('loyalty-modal-desc');
            const pctTextEl = document.getElementById('loyalty-modal-pct-text');

            if (nameEl) nameEl.innerText = cust.name;
            if (badgeEl) badgeEl.innerText = `${discountPct}% OFF`;
            if (descEl) descEl.innerHTML = `Customer "<strong>${escapeHtml(cust.name)}</strong>" is an active Loyalty Member eligible for a <strong>${discountPct}%</strong> discount.`;
            if (pctTextEl) pctTextEl.innerText = `${discountPct}%`;

            openModal('modal-loyalty-prompt');
            setTimeout(() => {
                updateLoyaltyChoiceHighlight();
            }, 60);
        }

        function updateLoyaltyChoiceHighlight() {
            const btnApply = document.getElementById('btn-loyalty-apply');
            const btnCancel = document.getElementById('btn-loyalty-cancel');
            if (!btnApply || !btnCancel) return;

            if (loyaltyChoiceIndex === 1) {
                btnApply.classList.add('focused');
                btnCancel.classList.remove('focused');
                btnApply.focus();
            } else {
                btnCancel.classList.add('focused');
                btnApply.classList.remove('focused');
                btnCancel.focus();
            }
        }

        function confirmApplyLoyaltyDiscount() {
            if (pendingLoyaltyDiscountPct > 0) {
                summaryDiscountInput.value = `${pendingLoyaltyDiscountPct}%`;
                calculateTotals();
            }
            closeModal('modal-loyalty-prompt');
            pendingLoyaltyCustomer = null;
            pendingLoyaltyDiscountPct = 0;
            setTimeout(() => {
                openSalesRepModal();
            }, 60);
        }

        function cancelLoyaltyPrompt() {
            closeModal('modal-loyalty-prompt');
            pendingLoyaltyCustomer = null;
            pendingLoyaltyDiscountPct = 0;
            setTimeout(() => {
                openSalesRepModal();
            }, 60);
        }

        function selectCustomerByIndex(idx) {
            if (currentSearchCustomers[idx]) {
                const cust = currentSearchCustomers[idx];
                currentCustomer = cust;
                updateCustomerDisplay();
                closeModal('modal-customer-selector');

                // Loyalty member discount prompt (Modal Popup)
                if (cust.is_loyalty == 1 || cust.is_loyalty === '1') {
                    const discountPct = (cust.loyalty_discount_override && parseFloat(cust.loyalty_discount_override) > 0) 
                        ? parseFloat(cust.loyalty_discount_override) 
                        : DEFAULT_LOYALTY_DISCOUNT_PERCENTAGE;

                    setTimeout(() => {
                        showLoyaltyPromptModal(cust, discountPct);
                    }, 80);
                } else {
                    setTimeout(() => {
                        openSalesRepModal();
                    }, 60);
                }
            }
        }

        // Dedicated Payment View & Switch Logic
        document.getElementById('btn-proceed-pay').addEventListener('click', proceedToPayment);

        function proceedToPayment() {
            if (cart.length === 0) {
                alert("Cart is empty. Please scan or select items before completing the sale.");
                return;
            }

            if (IS_SPECIAL_USER && !navigator.onLine) {
                alert("Special User is restricted from performing offline POS billing.");
                return;
            }

            if (navigator.onLine) {
                const payload = {
                    action: 'prepare_draft',
                    sale_id: activeSaleId,
                    customer_id: currentCustomer.id,
                    sales_rep_id: currentSalesRep ? currentSalesRep.id : null,
                    discount_value: summaryDiscountInput.value.trim(),
                    items: cart
                };

                fetch('pos_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        activeSaleId = res.sale_id;
                        openPaymentModal(res.total_amount);
                    } else {
                        alert("Error preparing payment draft: " + res.message);
                    }
                })
                .catch(() => {
                    proceedToOfflinePayment();
                });
            } else {
                proceedToOfflinePayment();
            }
        }

        function proceedToOfflinePayment() {
            let subtotal = 0;
            cart.forEach(item => {
                const itemSubtotal = item.quantity * item.unit_price;
                const parsedItemDisc = parseDiscountJS(item.discount_value, itemSubtotal);
                subtotal += Math.max(0, itemSubtotal - parsedItemDisc.total_discount);
            });

            const discStr = summaryDiscountInput.value.trim();
            const parsedInvDisc = parseDiscountJS(discStr, subtotal);
            const totalDiscount = parsedInvDisc.total_discount;
            const payableTotal = Math.max(0, subtotal - totalDiscount);
            activeSaleId = 'OFFLINE_DRAFT';
            openPaymentModal(payableTotal);
        }

        let currentVerifiedVoucher = null;

        function openPaymentModal(totalAmount) {
            paymentAllocations = [];
            currentPayableTotal = parseFloat(totalAmount);
            currentVerifiedVoucher = null;
            
            document.getElementById('pay-modal-total').innerText = `LKR ${currentPayableTotal.toFixed(2)}`;
            document.getElementById('pay-modal-discount').innerText = `- LKR ${currentTotalDiscount.toFixed(2)}`;
            
            const addSplitBtn = document.getElementById('btn-add-split-line');
            if (addSplitBtn) addSplitBtn.disabled = false;

            updatePaymentSummary();
            switchPaymentMethod('Cash');
            openModal('modal-payment');
        }

        const paymentMethodsList = ['Cash', 'Card', 'Bank', 'Credit', 'Voucher', 'Koko'];
        let activeKokoOrderId = null;
        let activeKokoPollTimer = null;
        let activeKokoStatus = 'pending';
        let currentKokoChannel = 'qr';

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

            // Update active method label in split amount prompt
            const labelEl = document.getElementById('active-method-label');
            if (labelEl) labelEl.innerText = method;

            const cashSection = document.getElementById('cash-payment-section');
            const cardSection = document.getElementById('card-payment-section');
            const bankSection = document.getElementById('bank-payment-section');
            const creditSection = document.getElementById('credit-payment-section');
            const voucherSection = document.getElementById('voucher-payment-section');
            const kokoSection = document.getElementById('koko-payment-section');

            const splitInput = document.getElementById('pay-split-amount-input');
            const remDue = getRemainingDue();
            if (splitInput) {
                if (method === 'Credit' && currentCustomer && currentCustomer.is_walkin != 1 && currentCustomer.id != 1) {
                    const limit = parseFloat(currentCustomer.credit_limit) || 0;
                    const bal = parseFloat(currentCustomer.credit_balance) || 0;
                    const avail = Math.max(0, limit - bal);
                    const existingCreditAlloc = paymentAllocations
                        .filter(p => p.payment_method === 'Credit')
                        .reduce((sum, p) => sum + p.amount, 0);
                    const remainingAvail = Math.max(0, avail - existingCreditAlloc);
                    splitInput.value = (remainingAvail > 0 ? Math.min(remDue, remainingAvail) : 0).toFixed(2);
                } else {
                    splitInput.value = remDue > 0 ? remDue.toFixed(2) : '0.00';
                }
            }
            updateSplitButtonLabel();

            // Clear any active Koko polling when switching away
            if (method !== 'Koko' && activeKokoPollTimer) {
                clearInterval(activeKokoPollTimer);
                activeKokoPollTimer = null;
            }

            // Hide all method sections first
            if (cashSection) cashSection.style.display = 'none';
            if (cardSection) cardSection.style.display = 'none';
            if (bankSection) bankSection.style.display = 'none';
            if (creditSection) creditSection.style.display = 'none';
            if (voucherSection) voucherSection.style.display = 'none';
            if (kokoSection) kokoSection.style.display = 'none';

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
            } else if (method === 'Credit') {
                if (creditSection) creditSection.style.display = 'block';
                populateCreditCustomerInfo();
                setTimeout(() => {
                    if (splitInput) { splitInput.focus(); splitInput.select(); }
                }, 80);
            } else if (method === 'Voucher') {
                if (voucherSection) voucherSection.style.display = 'block';
                const vRes = document.getElementById('voucher-verification-result');
                if (vRes) vRes.style.display = 'none';
                currentVerifiedVoucher = null;
                setTimeout(() => {
                    const vInput = document.getElementById('pay-voucher-code-input');
                    if (vInput) { vInput.value = ''; vInput.focus(); }
                }, 80);
            } else if (method === 'Koko') {
                if (kokoSection) kokoSection.style.display = 'block';
                initiateKokoPaymentOrder();
            }
        }

        function populateCreditCustomerInfo() {
            const nameEl = document.getElementById('credit-cust-name');
            const phoneEl = document.getElementById('credit-cust-phone');
            const limitEl = document.getElementById('credit-cust-limit');
            const balEl = document.getElementById('credit-cust-balance');
            const availEl = document.getElementById('credit-cust-available');
            const warningEl = document.getElementById('credit-walkin-warning');
            const addSplitBtn = document.getElementById('btn-add-split-line');

            if (!currentCustomer) {
                if (warningEl) {
                    warningEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> No customer selected.';
                    warningEl.style.display = 'block';
                }
                if (addSplitBtn && activePaymentMethod === 'Credit') addSplitBtn.disabled = true;
                return;
            }

            const isWalkin = currentCustomer.is_walkin == 1 || currentCustomer.id == 1;
            if (isWalkin) {
                if (nameEl) nameEl.innerText = 'Walk-in Customer (General)';
                if (phoneEl) phoneEl.innerText = '-';
                if (limitEl) limitEl.innerText = 'LKR 0.00';
                if (balEl) balEl.innerText = 'LKR 0.00';
                if (availEl) availEl.innerText = 'LKR 0.00';
                if (warningEl) {
                    warningEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Credit payment is strictly prohibited for Walk-in Customers. Please assign a registered customer first.';
                    warningEl.style.display = 'block';
                }
                if (addSplitBtn && activePaymentMethod === 'Credit') addSplitBtn.disabled = true;
            } else {
                const limit = parseFloat(currentCustomer.credit_limit) || 0;
                const bal = parseFloat(currentCustomer.credit_balance) || 0;
                const avail = Math.max(0, limit - bal);

                const existingCreditAlloc = paymentAllocations
                    .filter(p => p.payment_method === 'Credit')
                    .reduce((sum, p) => sum + p.amount, 0);

                const remainingAvail = Math.max(0, avail - existingCreditAlloc);

                if (nameEl) nameEl.innerText = currentCustomer.name;
                if (phoneEl) phoneEl.innerText = currentCustomer.phone || '-';
                if (limitEl) limitEl.innerText = `LKR ${limit.toFixed(2)}`;
                if (balEl) balEl.innerText = `LKR ${bal.toFixed(2)}`;
                if (availEl) availEl.innerText = `LKR ${avail.toFixed(2)}`;

                if (limit <= 0) {
                    if (warningEl) {
                        warningEl.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1"></i> Customer "<strong>${escapeHtml(currentCustomer.name)}</strong>" has no credit facility (Credit Limit: LKR 0.00). Credit sales are prohibited.`;
                        warningEl.style.display = 'block';
                    }
                    if (addSplitBtn && activePaymentMethod === 'Credit') addSplitBtn.disabled = true;
                } else if (remainingAvail <= 0) {
                    if (warningEl) {
                        warningEl.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1"></i> Credit limit reached for "<strong>${escapeHtml(currentCustomer.name)}</strong>"! Limit: LKR ${limit.toFixed(2)}, Outstanding: LKR ${bal.toFixed(2)}. Available Credit: <strong>LKR 0.00</strong>.`;
                        warningEl.style.display = 'block';
                    }
                    if (addSplitBtn && activePaymentMethod === 'Credit') addSplitBtn.disabled = true;
                } else {
                    if (warningEl) warningEl.style.display = 'none';
                    if (addSplitBtn && activePaymentMethod === 'Credit') addSplitBtn.disabled = false;
                }
            }
        }

        function verifyVoucherCode() {
            const vInput = document.getElementById('pay-voucher-code-input');
            const code = vInput ? vInput.value.trim() : '';
            const resEl = document.getElementById('voucher-verification-result');
            const btn = document.getElementById('btn-verify-voucher');

            if (!code) {
                alert("Please enter a gift voucher barcode or code.");
                if (vInput) vInput.focus();
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>';
            }

            const remDue = getRemainingDue();
            fetch(`pos_api.php?action=validate_voucher&code=${encodeURIComponent(code)}&payable=${encodeURIComponent(remDue)}`)
                .then(r => r.json())
                .then(res => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-magnifying-glass me-1"></i> Verify';
                    }
                    if (resEl) resEl.style.display = 'block';
                    if (res.valid && res.voucher) {
                        currentVerifiedVoucher = res.voucher;
                        const vAmt = parseFloat(res.voucher.amount);
                        if (resEl) {
                            resEl.style.background = '#dcfce7';
                            resEl.style.color = '#15803d';
                            resEl.style.border = '1px solid #86efac';
                            resEl.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> <strong>Valid Voucher:</strong> LKR ${vAmt.toFixed(2)} (Expires: ${res.voucher.expiry_date})`;
                        }
                        const splitInput = document.getElementById('pay-split-amount-input');
                        if (splitInput) {
                            splitInput.value = vAmt.toFixed(2);
                            updateSplitButtonLabel();
                        }
                    } else {
                        currentVerifiedVoucher = null;
                        if (resEl) {
                            resEl.style.background = '#fee2e2';
                            resEl.style.color = '#991b1b';
                            resEl.style.border = '1px solid #fca5a5';
                            resEl.innerHTML = `<i class="fa-solid fa-circle-xmark me-1"></i> ${escapeHtml(res.message || 'Invalid voucher.')}`;
                        }
                    }
                })
                .catch(err => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-magnifying-glass me-1"></i> Verify';
                    }
                    alert("Error validating voucher: " + err.message);
                });
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

        function switchKokoTab(tab) {
            currentKokoChannel = tab;
            document.querySelectorAll('.koko-tab-btn').forEach(btn => {
                btn.classList.remove('active');
                btn.style.background = 'transparent';
                btn.style.color = '#475569';
            });
            const activeBtn = document.getElementById(`tab-koko-${tab}`);
            if (activeBtn) {
                activeBtn.classList.add('active');
                activeBtn.style.background = '#0f172a';
                activeBtn.style.color = '#ffffff';
            }

            document.getElementById('koko-view-qr').style.display = (tab === 'qr') ? 'block' : 'none';
            document.getElementById('koko-view-link').style.display = (tab === 'link') ? 'block' : 'none';
            document.getElementById('koko-view-sms').style.display = (tab === 'sms') ? 'block' : 'none';

            if (tab === 'sms') {
                const phoneInput = document.getElementById('koko-phone-input');
                if (phoneInput && !phoneInput.value && currentCustomer && currentCustomer.phone) {
                    phoneInput.value = currentCustomer.phone;
                }
                if (phoneInput) phoneInput.focus();
            }
        }

        function initiateKokoPaymentOrder() {
            const unallocated = currentPayableTotal - getTotalAllocated();
            const amount = unallocated > 0 ? unallocated : currentPayableTotal;
            const installment = (amount / 3).toFixed(2);

            const instEl = document.getElementById('koko-installment-amount');
            if (instEl) instEl.innerText = `3 x LKR ${installment}`;

            const phoneInput = document.getElementById('koko-phone-input');
            let custPhone = '';
            if (currentCustomer && currentCustomer.phone) {
                custPhone = currentCustomer.phone;
                if (phoneInput && !phoneInput.value) phoneInput.value = custPhone;
            }

            // Set UI to loading state
            document.getElementById('koko-qr-box').innerHTML = '<div style="color: #64748b; font-size: 0.85rem; padding: 40px 0;"><i class="fa-solid fa-spinner fa-spin me-1"></i> Generating Dynamic Koko QR...</div>';
            document.getElementById('koko-link-input').value = 'Generating...';
            document.getElementById('koko-status-indicator').style.background = '#eab308';
            document.getElementById('koko-status-indicator').style.boxShadow = '0 0 8px #eab308';
            document.getElementById('koko-status-label').innerText = 'Awaiting Payment...';
            document.getElementById('koko-status-label').style.color = '#0f172a';
            document.getElementById('koko-order-ref-lbl').innerText = 'Initiating order...';

            if (activeKokoPollTimer) {
                clearInterval(activeKokoPollTimer);
                activeKokoPollTimer = null;
            }

            fetch('koko_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create_order',
                    amount: amount,
                    invoice_no: 'INV-' + (activeSaleId || Date.now()),
                    channel: currentKokoChannel,
                    customer_phone: custPhone,
                    customer_name: currentCustomer ? currentCustomer.name : 'Walk-in Customer',
                    sale_id: activeSaleId
                })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    activeKokoOrderId = res.koko_order_id;
                    activeKokoStatus = 'pending';
                    
                    if (res.qr_svg) {
                        document.getElementById('koko-qr-box').innerHTML = res.qr_svg;
                    }
                    document.getElementById('koko-link-input').value = res.payment_url;
                    document.getElementById('koko-link-open').href = res.payment_url;
                    document.getElementById('koko-order-ref-lbl').innerText = `Order: ${res.koko_order_id}`;
                    document.getElementById('pay-ref-input').value = res.koko_order_id;

                    // Poll every 3 seconds for payment updates
                    activeKokoPollTimer = setInterval(() => {
                        pollKokoStatus(activeKokoOrderId);
                    }, 3000);
                } else {
                    document.getElementById('koko-qr-box').innerHTML = `<div style="color: #dc2626; font-size: 0.85rem; padding: 20px;"><i class="fa-solid fa-circle-exclamation me-1"></i> ${escapeHtml(res.message || 'Error initiating KOKO')}</div>`;
                }
            })
            .catch(err => {
                console.error("Koko initiation error:", err);
                document.getElementById('koko-qr-box').innerHTML = '<div style="color: #dc2626; font-size: 0.85rem; padding: 20px;">Connection failed.</div>';
            });
        }

        function pollKokoStatus(orderId) {
            if (!orderId || activePaymentMethod !== 'Koko') {
                if (activeKokoPollTimer) {
                    clearInterval(activeKokoPollTimer);
                    activeKokoPollTimer = null;
                }
                return;
            }

            fetch(`koko_api.php?action=check_status&order_id=${encodeURIComponent(orderId)}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.status === 'paid') {
                        onKokoPaymentApproved(res);
                    }
                })
                .catch(err => console.error("Koko poll error:", err));
        }

        function onKokoPaymentApproved(tx) {
            if (activeKokoPollTimer) {
                clearInterval(activeKokoPollTimer);
                activeKokoPollTimer = null;
            }
            activeKokoStatus = 'paid';

            const ind = document.getElementById('koko-status-indicator');
            if (ind) {
                ind.style.background = '#16a34a';
                ind.style.boxShadow = '0 0 10px #16a34a';
            }
            const lbl = document.getElementById('koko-status-label');
            if (lbl) {
                lbl.innerText = 'Payment Verified & Approved!';
                lbl.style.color = '#15803d';
            }
            const refLbl = document.getElementById('koko-order-ref-lbl');
            if (refLbl) {
                refLbl.innerText = `Tx: ${tx.koko_payment_id || tx.koko_order_id}`;
            }

            document.getElementById('pay-ref-input').value = tx.koko_payment_id || tx.koko_order_id;
        }

        function checkKokoStatusManual() {
            if (!activeKokoOrderId) {
                alert("No active KOKO order initiated.");
                return;
            }
            const lbl = document.getElementById('koko-status-label');
            if (lbl) lbl.innerText = 'Checking gateway...';

            fetch(`koko_api.php?action=check_status&order_id=${encodeURIComponent(activeKokoOrderId)}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        if (res.status === 'paid') {
                            onKokoPaymentApproved(res);
                            alert("KOKO Payment has been verified as PAID!");
                        } else {
                            if (lbl) lbl.innerText = 'Awaiting Payment...';
                            alert(`Order status: ${res.status.toUpperCase()}. Awaiting customer payment on KOKO.`);
                        }
                    } else {
                        alert(res.message || "Failed to check order status.");
                    }
                })
                .catch(err => alert("Connection error: " + err.message));
        }

        function verifyKokoInstant() {
            if (!activeKokoOrderId) {
                alert("No active KOKO order initiated.");
                return;
            }

            const btn = document.getElementById('btn-koko-verify-instant');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Verifying...';
            }

            fetch('koko_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'verify_payment',
                    order_id: activeKokoOrderId,
                    sale_id: activeSaleId
                })
            })
            .then(res => res.json())
            .then(res => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> Verify & Confirm';
                }
                if (res.success) {
                    onKokoPaymentApproved(res);
                } else {
                    alert(res.message || "Verification failed.");
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> Verify & Confirm';
                }
                alert("Verification request error: " + err.message);
            });
        }

        function copyKokoLink() {
            const input = document.getElementById('koko-link-input');
            if (!input || !input.value) return;
            navigator.clipboard.writeText(input.value).then(() => {
                alert("KOKO Checkout Link copied to clipboard!");
            }).catch(() => {
                input.select();
                document.execCommand('copy');
                alert("KOKO Checkout Link copied!");
            });
        }

        function sendKokoSms() {
            const phoneInput = document.getElementById('koko-phone-input');
            const phone = phoneInput ? phoneInput.value.trim() : '';
            if (!phone) {
                alert("Please enter customer mobile phone number.");
                if (phoneInput) phoneInput.focus();
                return;
            }
            if (!activeKokoOrderId) {
                alert("Please wait for KOKO order to initialize.");
                return;
            }

            const btn = document.getElementById('btn-koko-send-sms');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Sending...';
            }

            fetch('koko_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'resend_sms',
                    order_id: activeKokoOrderId,
                    phone: phone
                })
            })
            .then(res => res.json())
            .then(res => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Send Link';
                }
                if (res.success) {
                    const statusEl = document.getElementById('koko-sms-status');
                    if (statusEl) {
                        statusEl.innerHTML = `<span style="color: #16a34a;"><i class="fa-solid fa-check-circle me-1"></i> ${escapeHtml(res.message)}</span>`;
                    }
                    alert(res.message);
                } else {
                    alert(res.message || "Failed to send SMS.");
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Send Link';
                }
                alert("SMS dispatch failed: " + err.message);
            });
        }

        function loadBankAccounts() {
            if (activeBankAccounts.length > 0) {
                populateBankDropdown(activeBankAccounts);
                return;
            }

            if (navigator.onLine) {
                fetch('pos_api.php?action=get_bank_accounts')
                    .then(res => res.json())
                    .then(res => {
                        if (res.success && res.data) {
                            activeBankAccounts = res.data;
                            populateBankDropdown(activeBankAccounts);
                        }
                    })
                    .catch(err => console.error("Error fetching bank accounts:", err));
            } else if (typeof CurtissOfflinePOS !== 'undefined') {
                CurtissOfflinePOS.getCachedData('bank_accounts').then(data => {
                    if (data && data.length > 0) {
                        activeBankAccounts = data;
                        populateBankDropdown(activeBankAccounts);
                    }
                });
            }
        }

        function populateBankDropdown(accounts) {
            const selectElem = document.getElementById('pay-bank-account-select');
            if (!selectElem) return;

            let html = '<option value="">-- Choose Active Bank Account --</option>';
            accounts.forEach(acc => {
                html += `<option value="${acc.id}">${escapeHtml(acc.bank_name)} - ${escapeHtml(acc.account_name)} (${escapeHtml(acc.account_number)})</option>`;
            });
            selectElem.innerHTML = html;

            if (accounts.length > 0) {
                selectElem.value = accounts[0].id;
            }
            if (activePaymentMethod === 'Bank') {
                selectElem.focus();
            }
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
            let voucherId = null;

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
            } else if (method === 'Credit') {
                if (!currentCustomer || currentCustomer.is_walkin == 1 || currentCustomer.id == 1) {
                    alert("Credit payment requires a registered customer. Walk-in Customer cannot receive credit.");
                    return;
                }
                const limit = parseFloat(currentCustomer.credit_limit) || 0;
                const bal = parseFloat(currentCustomer.credit_balance) || 0;
                const avail = Math.max(0, limit - bal);

                if (limit <= 0) {
                    alert(`Customer "${currentCustomer.name}" has no credit facility (Credit Limit: LKR 0.00). Credit sales are not permitted.`);
                    return;
                }

                const existingCreditAlloc = paymentAllocations
                    .filter(p => p.payment_method === 'Credit')
                    .reduce((sum, p) => sum + p.amount, 0);

                const remainingAvail = Math.max(0, avail - existingCreditAlloc);

                if (remainingAvail <= 0) {
                    alert(`Credit limit reached! Customer "${currentCustomer.name}" has no available credit remaining (Limit: LKR ${limit.toFixed(2)}, Outstanding: LKR ${bal.toFixed(2)}).`);
                    return;
                }

                if (amount > remainingAvail) {
                    alert(`Credit limit exceeded! Customer "${currentCustomer.name}" has only LKR ${remainingAvail.toFixed(2)} available credit remaining (Limit: LKR ${limit.toFixed(2)}, Outstanding: LKR ${bal.toFixed(2)}). Cannot allocate LKR ${amount.toFixed(2)}.`);
                    const splitInput = document.getElementById('pay-split-amount-input');
                    if (splitInput) splitInput.value = remainingAvail.toFixed(2);
                    return;
                }

                refNo = 'Credit Account';
            } else if (method === 'Voucher') {
                const vInput = document.getElementById('pay-voucher-code-input');
                const vCode = vInput ? vInput.value.trim() : '';
                if (!vCode) {
                    alert("Please enter or scan gift voucher barcode.");
                    if (vInput) vInput.focus();
                    return;
                }
                if (currentVerifiedVoucher && currentVerifiedVoucher.voucher_code.toLowerCase() === vCode.toLowerCase()) {
                    refNo = currentVerifiedVoucher.voucher_code;
                    voucherId = currentVerifiedVoucher.id;
                    amount = parseFloat(currentVerifiedVoucher.amount);
                } else {
                    refNo = vCode;
                }
            } else if (method === 'Koko') {
                refNo = activeKokoOrderId || '';
            }

            // Check duplicate voucher allocation
            if (method === 'Voucher') {
                const isDup = paymentAllocations.some(p => p.payment_method === 'Voucher' && p.reference_no.toLowerCase() === refNo.toLowerCase());
                if (isDup) {
                    alert(`Gift voucher ${refNo} has already been added.`);
                    return;
                }
            }

            paymentAllocations.push({
                payment_method: method,
                amount: amount,
                reference_no: refNo,
                bank_account_id: bankAccountId ? parseInt(bankAccountId) : null,
                voucher_id: voucherId,
                tendered: method === 'Cash' ? cashTenderedVal : amount
            });

            // Reset field states for next allocation
            if (method === 'Bank') {
                const bankRef = document.getElementById('pay-bank-ref-input');
                if (bankRef) bankRef.value = '';
            } else if (method === 'Voucher') {
                const vInput = document.getElementById('pay-voucher-code-input');
                if (vInput) vInput.value = '';
                const vRes = document.getElementById('voucher-verification-result');
                if (vRes) vRes.style.display = 'none';
                currentVerifiedVoucher = null;
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
                    else if (p.payment_method === 'Voucher') iconClass = 'fa-ticket';
                    else if (p.payment_method === 'Koko') iconClass = 'fa-qrcode';

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
        function finalizeSale() {
            if (!activeSaleId) {
                alert("No active sale draft.");
                return;
            }

            // Single Payment Fast Path: If no split allocations added yet
            if (paymentAllocations.length === 0) {
                const method = activePaymentMethod;
                const splitInput = document.getElementById('pay-split-amount-input');
                let amount = parseFloat(splitInput ? splitInput.value : 0) || currentPayableTotal;
                let refNo = '';
                let bankAccountId = null;
                let voucherId = null;
                let cashTenderedVal = amount;

                if (method === 'Cash') {
                    const cashGivenInput = document.getElementById('pay-cash-given-input');
                    cashTenderedVal = parseFloat(cashGivenInput ? cashGivenInput.value : 0) || amount;
                    if (cashTenderedVal < currentPayableTotal) {
                        alert(`Cash Tendered (LKR ${cashTenderedVal.toFixed(2)}) is less than Payable Total (LKR ${currentPayableTotal.toFixed(2)}). Please enter full cash amount or add split payment.`);
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
                } else if (method === 'Credit') {
                    if (!currentCustomer || currentCustomer.is_walkin == 1 || currentCustomer.id == 1) {
                        alert("Credit payment requires a registered customer. Walk-in Customer cannot receive credit.");
                        return;
                    }
                    const limit = parseFloat(currentCustomer.credit_limit) || 0;
                    const bal = parseFloat(currentCustomer.credit_balance) || 0;
                    const avail = Math.max(0, limit - bal);

                    if (limit <= 0) {
                        alert(`Customer "${currentCustomer.name}" has no credit facility (Credit Limit: LKR 0.00). Credit sales are not permitted.`);
                        return;
                    }

                    if (currentPayableTotal > avail) {
                        alert(`Credit limit exceeded! Customer "${currentCustomer.name}" has only LKR ${avail.toFixed(2)} available credit (Limit: LKR ${limit.toFixed(2)}, Outstanding: LKR ${bal.toFixed(2)}). Total payable LKR ${currentPayableTotal.toFixed(2)} cannot be placed on credit.`);
                        return;
                    }

                    refNo = 'Credit Account';
                    amount = currentPayableTotal;
                } else if (method === 'Voucher') {
                    const vInput = document.getElementById('pay-voucher-code-input');
                    refNo = vInput ? vInput.value.trim() : '';
                    if (!refNo) {
                        alert("Please enter gift voucher barcode.");
                        if (vInput) vInput.focus();
                        return;
                    }
                    if (currentVerifiedVoucher) {
                        voucherId = currentVerifiedVoucher.id;
                        amount = parseFloat(currentVerifiedVoucher.amount);
                    } else {
                        amount = currentPayableTotal;
                    }
                } else if (method === 'Koko') {
                    refNo = activeKokoOrderId || '';
                    amount = currentPayableTotal;
                }

                paymentAllocations.push({
                    payment_method: method,
                    amount: amount,
                    reference_no: refNo,
                    bank_account_id: bankAccountId ? parseInt(bankAccountId) : null,
                    voucher_id: voucherId,
                    tendered: cashTenderedVal
                });
            }

            const totalAllocated = getTotalAllocated();
            const remainingDue = getRemainingDue();

            if (remainingDue > 0) {
                alert(`Payment is incomplete. Remaining balance due: LKR ${remainingDue.toFixed(2)}. Please add remaining amount to split payment allocations before confirming.`);
                return;
            }

            const hasVoucher = paymentAllocations.some(p => p.payment_method === 'Voucher');
            if (hasVoucher && (!navigator.onLine || activeSaleId === 'OFFLINE_DRAFT')) {
                alert("Gift vouchers require an online connection.");
                return;
            }

            const finalizeBtn = document.getElementById('btn-finalize-sale');
            if (finalizeBtn) {
                finalizeBtn.disabled = true;
                finalizeBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> PROCESSING...';
            }

            // Offline Mode Finalization
            if (!navigator.onLine || activeSaleId === 'OFFLINE_DRAFT') {
                if (IS_SPECIAL_USER) {
                    alert("Special User is restricted from performing offline POS billing.");
                    if (finalizeBtn) finalizeBtn.disabled = false;
                    return;
                }

                let subtotal = 0;
                cart.forEach(item => {
                    const itemSubtotal = item.quantity * item.unit_price;
                    const parsedItemDisc = parseDiscountJS(item.discount_value, itemSubtotal);
                    subtotal += Math.max(0, itemSubtotal - parsedItemDisc.total_discount);
                });

                const discStr = summaryDiscountInput.value.trim();
                const parsedInvDisc = parseDiscountJS(discStr, subtotal);
                const totalDiscount = parsedInvDisc.total_discount;
                const totalAmount = Math.max(0, subtotal - totalDiscount);

                let allocated = getTotalAllocated();
                const changeAmount = Math.max(0, allocated - totalAmount);

                const offlinePayload = {
                    branch_id: CURRENT_BRANCH_ID,
                    user_id: CURRENT_USER_ID,
                    customer_id: currentCustomer.id,
                    customer_name: currentCustomer.name,
                    sales_rep_id: currentSalesRep ? currentSalesRep.id : null,
                    sales_rep_name: currentSalesRep ? currentSalesRep.full_name : null,
                    items: cart.map(item => {
                        const itemSubtotal = item.quantity * item.unit_price;
                        const parsedItemDisc = parseDiscountJS(item.discount_value, itemSubtotal);
                        const lineTotal = Math.max(0, itemSubtotal - parsedItemDisc.total_discount);
                        return {
                            product_id: item.product_id,
                            lot_id: item.lot_id,
                            quantity: item.quantity,
                            unit_price: item.unit_price,
                            discount_type: parsedItemDisc.type,
                            discount_value: item.discount_value,
                            discount_amount: parsedItemDisc.total_discount,
                            line_total: lineTotal,
                            subtotal: itemSubtotal
                        };
                    }),
                    discount_str: discStr,
                    customer_facing_discount: parsedInvDisc.customer_facing_text,
                    subtotal: subtotal,
                    total_discount: totalDiscount,
                    total_amount: totalAmount,
                    payments: paymentAllocations
                };

                CurtissOfflinePOS.saveOfflineSale(offlinePayload).then(record => {
                    closeModal('modal-payment');
                    
                    lastCompletedSaleData = {
                        sale_id: null,
                        offline_ref: record.temp_ref,
                        invoice_no: record.temp_ref,
                        total_amount: totalAmount,
                        change_amount: changeAmount,
                        payment_method: paymentAllocations.map(p => p.payment_method).join(', ')
                    };

                    showInvoiceCompleteModal(lastCompletedSaleData);
                    updatePendingBadge();
                }).catch(err => {
                    if (finalizeBtn) finalizeBtn.disabled = false;
                    alert("Error saving offline sale: " + err.message);
                });
                return;
            }

            // Online Mode Finalization
            const payload = {
                action: 'finalize_sale',
                sale_id: activeSaleId,
                payments: paymentAllocations
            };
            if (currentSalesRep) {
                payload.payments.sales_rep_id = currentSalesRep.id;
            }

            fetch('pos_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(res => {
                if (finalizeBtn) finalizeBtn.disabled = false;
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

                    showInvoiceCompleteModal(lastCompletedSaleData);
                } else {
                    alert("Finalization Error: " + res.message);
                }
            })
            .catch(err => {
                if (finalizeBtn) finalizeBtn.disabled = false;
                console.error("Error finalizing sale", err);
                alert("Finalization error: " + err.message);
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
            if (!lastCompletedSaleData) return;
            let receiptUrl = '';
            if (lastCompletedSaleData.sale_id) {
                receiptUrl = `receipt.php?sale_id=${lastCompletedSaleData.sale_id}`;
            } else if (lastCompletedSaleData.offline_ref) {
                receiptUrl = `receipt.php?offline_ref=${encodeURIComponent(lastCompletedSaleData.offline_ref)}`;
            }
            if (receiptUrl) {
                window.open(receiptUrl, '_blank', 'width=400,height=650');
            }
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
                openCustomerModal();
            }, 100);
        }

        function clearCartState() {
            cart = [];
            activeSaleId = null;
            lastCompletedSaleData = null;
            summaryDiscountInput.value = '';
            paymentAllocations = [];
            clearSalesRep();
            renderCart();
            searchInput.focus();
        }

        // Global Modal Helpers
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { 
            if (id === 'modal-payment' && typeof activeKokoPollTimer !== 'undefined' && activeKokoPollTimer) {
                clearInterval(activeKokoPollTimer);
                activeKokoPollTimer = null;
            }
            document.getElementById(id).classList.remove('active'); 
            lastModalClosedTime = Date.now();
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // Global Keyboard Shortcuts Handler
        document.addEventListener('keydown', (e) => {
            const isQuickCustModalOpen = document.getElementById('modal-quick-customer') && document.getElementById('modal-quick-customer').classList.contains('active');
            if (isQuickCustModalOpen) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeQuickCustomerModal();
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
            const isCustModalOpen = document.getElementById('modal-customer-selector').classList.contains('active');
            const isRepModalOpen = document.getElementById('modal-sales-rep-selector') ? document.getElementById('modal-sales-rep-selector').classList.contains('active') : false;

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

            if (isPaymentModalOpen) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeModal('modal-payment');
                    searchInput.focus();
                    return;
                }

                // ArrowLeft / ArrowRight navigation between payment methods
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

                // Enter Key Action inside Payment Settlement Modal
                if (e.key === 'Enter') {
                    e.preventDefault();

                    // Ctrl+Enter or Alt+Enter to quickly add split allocation
                    if (e.ctrlKey || e.altKey) {
                        addSplitPaymentLine();
                        return;
                    }

                    if (e.target.id === 'pay-voucher-code-input') {
                        verifyVoucherCode();
                        return;
                    }

                    if (e.target.id === 'pay-split-amount-input') {
                        const splitAmt = parseFloat(e.target.value) || 0;
                        const remDue = getRemainingDue();

                        // If user entered a partial split amount (< remDue) or has existing allocations, add to split!
                        if (splitAmt > 0 && splitAmt < remDue) {
                            addSplitPaymentLine();
                            return;
                        }

                        if (paymentAllocations.length > 0 && splitAmt > 0) {
                            addSplitPaymentLine();
                            return;
                        }

                        if (paymentAllocations.length === 0) {
                            if (activePaymentMethod === 'Cash') {
                                const cashGiven = document.getElementById('pay-cash-given-input');
                                if (cashGiven) {
                                    cashGiven.focus();
                                    cashGiven.select();
                                }
                                return;
                            } else {
                                finalizeSale();
                                return;
                            }
                        }
                    }

                    finalizeSale();
                    return;
                }

                if (e.key === 'F10') {
                    e.preventDefault();
                    finalizeSale();
                    return;
                }
                return;
            }

            if (isCustModalOpen) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeModal('modal-customer-selector');
                    searchInput.focus();
                }
                return;
            }

            if (isRepModalOpen) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeModal('modal-sales-rep-selector');
                    searchInput.focus();
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
                document.getElementById('btn-proceed-pay').click();
                return;
            }

            if (e.key === '/' || e.key === 'F2') {
                e.preventDefault();
                searchInput.focus();
            } else if (e.key === 'F3') {
                e.preventDefault();
                openSalesRepModal();
            } else if (e.key === 'F4') {
                e.preventDefault();
                openCustomerModal();
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

        // Auto open Customer Selection modal on POS Terminal launch
        window.addEventListener('load', () => {
            setTimeout(() => {
                openCustomerModal();
            }, 300);
        });
    </script>
</body>
</html>
