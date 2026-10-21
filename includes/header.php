<?php
/**
 * Curtiss POS - UI Shell Floating Header
 * Top-left floating rounded bubbles: [ Back ] [ Current Page ] [ Branch ▼ ]
 */

$user = Auth::user();
if (empty($_SESSION['user_branches']) && !empty($user['id'])) {
    $_SESSION['user_branches'] = Auth::getUserBranches((int)$user['id'], $user['role_code'] ?? '');
}
$userBranches = $_SESSION['user_branches'] ?? [];
$activeBranchId = $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? null;
if (!empty($activeBranchId)) {
    $_SESSION['branch_id'] = (int)$activeBranchId;
}

$currentScript = preg_replace('/\.php$/i', '', basename($_SERVER['SCRIPT_NAME']));
$pageTitleText = $pageTitle ?? null;
if (!$pageTitleText || $pageTitleText === 'Curtiss POS') {
    $pageTitleText = $windowNavTitle ?? ucfirst(str_replace('_', ' ', $currentScript));
}

// Strip " - Curtiss POS", "- Curtiss POS", " | Curtiss POS" suffix for clean header display
$pageTitleText = preg_replace('/\s*[\-\|]?\s*Curtiss\s+POS\s*$/i', '', $pageTitleText);
if (empty($pageTitleText)) {
    $pageTitleText = ucfirst(str_replace('_', ' ', $currentScript));
}
?>
<div id="curtiss-floating-header" role="banner">
    <!-- Back Bubble -->
    <button type="button" class="top-bubble-btn" id="top-back-btn" onclick="window.history.back()" aria-label="Go back" data-tooltip="Go Back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
    </button>

    <!-- Current Page Pill -->
    <div class="top-bubble-pill" id="top-page-pill">
        <span class="top-page-title" id="top-page-title-text"><?= View::escape($pageTitleText) ?></span>
    </div>

    <!-- Branch Selector Bubble (only if user has more than 1 branch) -->
    <?php if (!empty($userBranches) && count($userBranches) > 1): 
        $activeBranchObj = null;
        foreach ($userBranches as $b) {
            if ((int)$b['id'] === (int)$activeBranchId) {
                $activeBranchObj = $b;
                break;
            }
        }
        if (!$activeBranchObj) $activeBranchObj = $userBranches[0];

        $isReportOrDashboard = (
            (isset($viewPath) && in_array($viewPath, ['dashboard/index', 'reports/index'], true)) ||
            in_array($currentScript, ['dashboard', 'reports'], true) ||
            (bool)preg_match('#/(dashboard|reports)(\b|/|\.php)#i', $_SERVER['REQUEST_URI'] ?? '')
        );
        $isAllSelected = ($isReportOrDashboard && !empty($_SESSION['all_branches_view']));
        $headerBranchName = $isAllSelected ? 'All Branches' : ($activeBranchObj['name'] ?? 'Branch');
        $headerBranchCode = $isAllSelected ? 'ALL' : ($activeBranchObj['code'] ?? 'BR');
    ?>
    <div class="top-branch-wrapper" id="top-branch-wrapper">
        <button type="button" class="top-bubble-pill top-branch-pill" id="top-branch-trigger" aria-expanded="false" aria-haspopup="true">
            <svg class="branch-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 21h18"></path>
                <path d="M5 21V7l8-4v18"></path>
                <path d="M19 21V11l-6-3"></path>
            </svg>
            <span class="branch-current-text"><?= View::escape($headerBranchName) ?> (<?= View::escape($headerBranchCode) ?>)</span>
            <svg class="branch-arrow" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </button>

        <!-- Floating Branch Selection Dropdown -->
        <div class="top-branch-dropdown" id="top-branch-dropdown" role="menu">
            <div class="branch-dropdown-header">Active Branch</div>
            <div class="branch-dropdown-list">
                <?php if ($isReportOrDashboard): ?>
                <button type="button" class="branch-dropdown-item <?= $isAllSelected ? 'active' : '' ?>" data-branch-id="all" role="menuitem">
                    <div class="branch-item-main">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="branch-item-icon">
                            <path d="M3 21h18"></path>
                            <path d="M5 21V7l8-4v18"></path>
                            <path d="M19 21V11l-6-3"></path>
                        </svg>
                        <span class="branch-item-name">All Branches</span>
                    </div>
                    <div class="branch-item-right">
                        <span class="branch-item-code">ALL</span>
                        <?php if ($isAllSelected): ?>
                        <svg class="branch-item-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <?php endif; ?>
                    </div>
                </button>
                <?php endif; ?>
                <?php foreach ($userBranches as $b): 
                    $isSelected = (!$isAllSelected && (int)$b['id'] === (int)$activeBranchId);
                ?>
                <button type="button" class="branch-dropdown-item <?= $isSelected ? 'active' : '' ?>" data-branch-id="<?= $b['id'] ?>" role="menuitem">
                    <div class="branch-item-main">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="branch-item-icon">
                            <path d="M3 21h18"></path>
                            <path d="M5 21V7l8-4v18"></path>
                            <path d="M19 21V11l-6-3"></path>
                        </svg>
                        <span class="branch-item-name"><?= View::escape($b['name']) ?></span>
                    </div>
                    <div class="branch-item-right">
                        <span class="branch-item-code"><?= View::escape($b['code']) ?></span>
                        <?php if ($isSelected): ?>
                        <svg class="branch-item-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <?php endif; ?>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <select id="headerBranchSelect" style="display:none;" aria-hidden="true">
            <?php if ($isReportOrDashboard): ?>
                <option value="all" <?= $isAllSelected ? 'selected' : '' ?>>All Branches (ALL)</option>
            <?php endif; ?>
            <?php foreach ($userBranches as $b): ?>
                <option value="<?= $b['id'] ?>" <?= (!$isAllSelected && (int)$b['id'] === (int)$activeBranchId) ? 'selected' : '' ?>>
                    <?= View::escape($b['name']) ?> (<?= View::escape($b['code']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
</div>
