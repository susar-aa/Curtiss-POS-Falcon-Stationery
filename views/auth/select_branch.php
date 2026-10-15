<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Operating Branch - Curtiss POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, rgba(15, 23, 42, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.05) 0px, transparent 50%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
            color: #0f172a;
        }
        .branch-modal-card {
            width: 100%;
            max-width: 580px;
            background: #ffffff;
            border-radius: 24px;
            padding: 2.5rem 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(226, 232, 240, 0.8);
            position: relative;
            overflow: hidden;
        }
        .branch-modal-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #0f172a 0%, #3b82f6 50%, #10b981 100%);
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
            border: 1px solid #e2e8f0;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }
        .branch-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .user-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 0.65rem;
            font-size: 0.88rem;
            color: #64748b;
        }
        .role-tag {
            background: #eff6ff;
            color: #2563eb;
            font-weight: 700;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 999px;
            border: 1px solid #dbeafe;
        }
        .branch-grid {
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            margin-top: 1.75rem;
        }
        .branch-option-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.15rem 1.25rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            text-decoration: none;
            color: inherit;
            text-align: left;
            width: 100%;
        }
        .branch-option-btn:hover {
            border-color: #0f172a;
            background: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.08);
        }
        .branch-option-btn:focus {
            outline: none;
            border-color: #0f172a;
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.15);
        }
        .branch-option-btn.is-active {
            border-color: #0f172a;
            background: #fafafa;
        }
        .branch-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #0f172a;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        .branch-option-btn:hover .branch-icon-wrap {
            transform: scale(1.05);
            background: #1e293b;
        }
        .branch-name {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.05rem;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .branch-code-badge {
            font-size: 0.72rem;
            font-weight: 800;
            background: #f1f5f9;
            color: #475569;
            padding: 2px 7px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            font-family: monospace;
        }
        .branch-meta {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .branch-select-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            font-weight: 800;
            background: #0f172a;
            color: #ffffff;
            padding: 7px 14px;
            border-radius: 999px;
            transition: background 0.2s ease;
            white-space: nowrap;
        }
        .branch-option-btn:hover .branch-select-action {
            background: #1e293b;
        }
        .hint-footer {
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f1f5f9;
            font-size: 0.82rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 8px;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="branch-modal-card">
        <div class="brand-badge">
            <span class="status-dot"></span> Curtiss POS &bull; Enterprise
        </div>

        <h1 class="branch-title">Select Operating Branch</h1>
        <div class="user-pill">
            <span>Welcome, <strong><?= View::escape($user['full_name'] ?? 'User') ?></strong></span>
            <span class="role-tag"><?= View::escape($user['role_name'] ?? 'Administrator') ?></span>
        </div>

        <form method="POST" action="select_branch.php" class="branch-grid">
            <?php foreach ($branches as $idx => $branch): 
                $isCurrent = ((int)$branch['id'] === (int)($currentBranchId ?? 0));
            ?>
                <button type="submit" name="branch_id" value="<?= $branch['id'] ?>" class="branch-option-btn <?= $isCurrent ? 'is-active' : '' ?>" <?= $idx === 0 ? 'autofocus' : '' ?>>
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div class="branch-icon-wrap">
                            <i class="fa-solid fa-shop"></i>
                        </div>
                        <div>
                            <div class="branch-name">
                                <?= View::escape($branch['name']) ?>
                                <span class="branch-code-badge"><?= View::escape($branch['code']) ?></span>
                                <?php if ($isCurrent): ?>
                                    <span style="font-size: 0.68rem; background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 1px 7px; border-radius: 999px; font-weight: 800;">ACTIVE</span>
                                <?php endif; ?>
                            </div>
                            <div class="branch-meta">
                                <?php if (!empty($branch['address'])): ?>
                                    <span><i class="fa-solid fa-location-dot" style="font-size: 0.72rem; color: #94a3b8;"></i> <?= View::escape($branch['address']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($branch['phone'])): ?>
                                    <span>&bull; <i class="fa-solid fa-phone" style="font-size: 0.72rem; color: #94a3b8;"></i> <?= View::escape($branch['phone']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="branch-select-action">Launch &rarr;</span>
                    </div>
                </button>
            <?php endforeach; ?>
        </form>

        <div class="hint-footer">
            <i class="fa-solid fa-circle-info" style="color: #3b82f6; font-size: 0.95rem;"></i>
            <span>You can easily switch branches anytime using the branch selector in the top header bar.</span>
        </div>
    </div>
</body>
</html>
