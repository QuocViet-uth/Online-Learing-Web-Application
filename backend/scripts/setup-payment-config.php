<?php
/**
 * Script helper để cấu hình payment gateway nhanh
 * Chạy: php scripts/setup-payment-config.php
 */

echo "=== CẤU HÌNH PAYMENT GATEWAY ===\n\n";

$configFile = __DIR__ . '/../config/payment_gateway.php';
$exampleFile = __DIR__ . '/../config/payment_gateway.example.php';

// Kiểm tra file config đã tồn tại chưa
if (file_exists($configFile)) {
    echo "⚠️  File config đã tồn tại: $configFile\n";
    echo "Bạn có muốn cập nhật không? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) !== 'y' && trim($line) !== 'Y') {
        echo "Hủy bỏ.\n";
        exit(0);
    }
}

echo "\n--- Cấu hình VNPay ---\n";
echo "Nhập TMN Code (để trống nếu chưa có): ";
$handle = fopen("php://stdin", "r");
$vnpay_tmn = trim(fgets($handle));

echo "Nhập Secret Key (để trống nếu chưa có): ";
$vnpay_secret = trim(fgets($handle));

echo "Return URL (mặc định: http://localhost:8000/api/payment-callback/vnpay): ";
$vnpay_return = trim(fgets($handle));
if (empty($vnpay_return)) {
    $vnpay_return = 'http://localhost:8000/api/payment-callback/vnpay';
}

echo "\n--- Cấu hình MoMo ---\n";
echo "Nhập Partner Code (để trống nếu chưa có): ";
$momo_partner = trim(fgets($handle));

echo "Nhập Access Key (để trống nếu chưa có): ";
$momo_access = trim(fgets($handle));

echo "Nhập Secret Key (để trống nếu chưa có): ";
$momo_secret = trim(fgets($handle));

echo "Return URL (mặc định: http://localhost:8000/api/payment-callback/momo): ";
$momo_return = trim(fgets($handle));
if (empty($momo_return)) {
    $momo_return = 'http://localhost:8000/api/payment-callback/momo';
}

echo "Notify URL (mặc định: http://localhost:8000/api/payment-callback/momo-notify): ";
$momo_notify = trim(fgets($handle));
if (empty($momo_notify)) {
    $momo_notify = 'http://localhost:8000/api/payment-callback/momo-notify';
}

// Tạo nội dung config
$configContent = <<<PHP
<?php
/**
 * File: config/payment_gateway.php
 * Mục đích: Cấu hình các payment gateway
 * 
 * LƯU Ý: Đây là file config mẫu. Trong thực tế, nên lưu các thông tin nhạy cảm
 * trong biến môi trường hoặc database, không commit vào git.
 */

return array(
    'vnpay' => array(
        'enabled' => true,
        'tmn_code' => getenv('VNPAY_TMN_CODE') ?: '{$vnpay_tmn}',
        'secret_key' => getenv('VNPAY_SECRET_KEY') ?: '{$vnpay_secret}',
        'url' => getenv('VNPAY_URL') ?: 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        'return_url' => getenv('VNPAY_RETURN_URL') ?: '{$vnpay_return}',
        'version' => '2.1.0',
        'command' => 'pay',
        'currency' => 'VND',
        'locale' => 'vn'
    ),
    
    'momo' => array(
        'enabled' => true,
        'partner_code' => getenv('MOMO_PARTNER_CODE') ?: '{$momo_partner}',
        'access_key' => getenv('MOMO_ACCESS_KEY') ?: '{$momo_access}',
        'secret_key' => getenv('MOMO_SECRET_KEY') ?: '{$momo_secret}',
        'endpoint' => getenv('MOMO_ENDPOINT') ?: 'https://test-payment.momo.vn/v2/gateway/api/create',
        'return_url' => getenv('MOMO_RETURN_URL') ?: '{$momo_return}',
        'notify_url' => getenv('MOMO_NOTIFY_URL') ?: '{$momo_notify}',
        'store_id' => 'MomoTestStore',
        'lang' => 'vi'
    ),
    
    'bank_transfer' => array(
        'enabled' => true,
    )
);
PHP;

// Ghi file
file_put_contents($configFile, $configContent);

echo "\n✅ Đã tạo file config: $configFile\n";
echo "\n📝 Tóm tắt cấu hình:\n";
echo "VNPay:\n";
echo "  - TMN Code: " . (!empty($vnpay_tmn) ? substr($vnpay_tmn, 0, 4) . "****" : "CHƯA CẤU HÌNH") . "\n";
echo "  - Secret Key: " . (!empty($vnpay_secret) ? "ĐÃ CẤU HÌNH" : "CHƯA CẤU HÌNH") . "\n";
echo "  - Return URL: $vnpay_return\n";
echo "\nMoMo:\n";
echo "  - Partner Code: " . (!empty($momo_partner) ? substr($momo_partner, 0, 4) . "****" : "CHƯA CẤU HÌNH") . "\n";
echo "  - Access Key: " . (!empty($momo_access) ? "ĐÃ CẤU HÌNH" : "CHƯA CẤU HÌNH") . "\n";
echo "  - Secret Key: " . (!empty($momo_secret) ? "ĐÃ CẤU HÌNH" : "CHƯA CẤU HÌNH") . "\n";
echo "  - Return URL: $momo_return\n";
echo "  - Notify URL: $momo_notify\n";

echo "\n💡 Tiếp theo:\n";
echo "1. Chạy script test: php scripts/test-payment-config.php\n";
echo "2. Xem hướng dẫn chi tiết: backend/HUONG_DAN_CAU_HINH_PAYMENT.md\n";
echo "\n";

