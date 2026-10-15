<?php
/**
 * View & UI Helper Component for Curtiss POS
 */

class View
{
    /**
     * Render a view file within the standard application shell layout
     */
    public static function render(string $viewPath, array $data = [], string $title = 'Curtiss POS'): void
    {
        extract($data);
        $pageTitle = $title;
        $currentUser = Auth::user();
        
        $viewFile = APP_ROOT . '/views/' . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            Logger::error("View file not found: $viewFile");
            echo "<h1>Error: View file not found ($viewPath)</h1>";
            return;
        }

        // Buffer the view content
        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // Render main layout wrapper
        include APP_ROOT . '/includes/layout.php';
    }

    /**
     * Render layout without header/sidebar (e.g. for Login page)
     */
    public static function renderBlank(string $viewPath, array $data = [], string $title = 'Curtiss POS'): void
    {
        extract($data);
        $pageTitle = $title;
        $viewFile = APP_ROOT . '/views/' . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            echo "<h1>Error: View file not found ($viewPath)</h1>";
            return;
        }

        include $viewFile;
    }

    /**
     * HTML Escape helper
     */
    public static function escape(?string $string): string
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Money / Currency Formatter
     */
    public static function formatMoney(float|int|string|null $amount): string
    {
        $symbol = Settings::get('currency_symbol', 'LKR');
        $val = (float)($amount ?? 0);
        return $symbol . ' ' . number_format($val, 2, '.', ',');
    }

    /**
     * Date Formatter
     */
    public static function formatDate(?string $datetime, string $format = 'Y-m-d H:i'): string
    {
        if (!$datetime) return 'N/A';
        return date($format, strtotime($datetime));
    }
}
