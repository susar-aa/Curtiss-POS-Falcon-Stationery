<?php
/**
 * Migration Script: Update Barcode Label Settings for 3-Up Thermal Printing (30mm x 15mm)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $settings = [
        'label_printer_model'     => 'Zenpert / TSC 3-Up',
        'label_width_mm'          => '30',
        'label_height_mm'         => '15',
        'labels_per_row'          => '3',
        'label_roll_width_mm'     => '100',
        'label_column_gap_mm'     => '2',
        'label_row_gap_mm'        => '2',
        'label_margin_left_mm'    => '2',
        'label_margin_top_mm'     => '0',
        'label_barcode_height_mm' => '6.5',
        'label_font_size_name_pt' => '7',
        'label_font_size_price_pt'=> '8.5'
    ];

    foreach ($settings as $key => $val) {
        $existing = Database::fetchOne("SELECT id FROM system_settings WHERE setting_key = :key", [':key' => $key]);
        if ($existing) {
            Database::execute("UPDATE system_settings SET setting_value = :val, updated_at = NOW() WHERE setting_key = :key", [
                ':val' => $val,
                ':key' => $key
            ]);
        } else {
            Database::execute("INSERT INTO system_settings (setting_key, setting_value, setting_group, updated_at) VALUES (:key, :val, 'inventory', NOW())", [
                ':key' => $key,
                ':val' => $val
            ]);
        }
        echo "✔ Setting {$key} => {$val}\n";
    }

    echo "Migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
