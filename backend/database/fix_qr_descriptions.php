<?php
/**
 * Fix QR code descriptions với UTF-8 đúng
 */

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Không thể kết nối database\n");
}

// Set charset UTF-8
$db->exec("SET NAMES utf8mb4");

try {
    // Cập nhật descriptions
    $updates = [
        ['gateway' => 'bank_transfer', 'desc' => 'Quét mã QR để thanh toán qua ngân hàng'],
        ['gateway' => 'momo', 'desc' => 'Quét mã QR để thanh toán qua MoMo'],
        ['gateway' => 'vnpay', 'desc' => 'Quét mã QR để thanh toán qua VNPay']
    ];
    
    $stmt = $db->prepare("UPDATE payment_qr_codes SET description = ? WHERE payment_gateway = ?");
    
    foreach ($updates as $update) {
        $stmt->execute([$update['desc'], $update['gateway']]);
        echo "✓ Đã cập nhật {$update['gateway']}: {$update['desc']}\n";
    }
    
    echo "\n✅ Hoàn thành!\n";
    
    // Hiển thị kết quả
    echo "\nKết quả:\n";
    $result = $db->query("SELECT payment_gateway, description FROM payment_qr_codes");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['payment_gateway']}: {$row['description']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}
?>
