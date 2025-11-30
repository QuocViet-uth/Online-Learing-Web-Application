<?php
/**
 * Script kiểm tra ngrok có đang chạy không
 * Chạy: php scripts/check-ngrok.php
 */

echo "=== KIỂM TRA NGROK ===\n\n";

$ngrokApi = 'http://127.0.0.1:4040/api/tunnels';

// Kiểm tra ngrok có đang chạy không
$ch = curl_init($ngrokApi);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError || $httpCode !== 200) {
    echo "❌ Ngrok KHÔNG đang chạy!\n\n";
    echo "📝 Hướng dẫn:\n";
    echo "   1. Mở terminal mới\n";
    echo "   2. Chạy: ngrok http 8000\n";
    echo "   3. Chạy lại script này để kiểm tra\n";
    exit(1);
}

$data = json_decode($response, true);

if (!$data || !isset($data['tunnels']) || empty($data['tunnels'])) {
    echo "⚠️  Ngrok đang chạy nhưng chưa có tunnel nào.\n";
    echo "   Đảm bảo đã expose port: ngrok http 8000\n";
    exit(1);
}

$tunnel = $data['tunnels'][0];
$publicUrl = $tunnel['public_url'] ?? null;

if (!$publicUrl) {
    echo "❌ Không tìm thấy public URL.\n";
    exit(1);
}

echo "✅ Ngrok đang chạy!\n\n";
echo "📋 Thông tin:\n";
echo "   Public URL: $publicUrl\n";
echo "   Local: {$tunnel['config']['addr']}\n";
echo "   Protocol: {$tunnel['proto']}\n\n";

// Kiểm tra server có đang chạy không
$localUrl = str_replace('https://', 'http://', $publicUrl);
$ch = curl_init($localUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$serverResponse = curl_exec($ch);
$serverHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($serverHttpCode >= 200 && $serverHttpCode < 400) {
    echo "✅ Server đang chạy và có thể truy cập qua ngrok!\n";
} else {
    echo "⚠️  Server có thể không chạy hoặc có lỗi.\n";
    echo "   HTTP Code: $serverHttpCode\n";
    echo "   Kiểm tra server có đang chạy trên port 8000 không.\n";
}

echo "\n";

// Kiểm tra callback URLs
echo "🔗 Callback URLs:\n";
echo "   VNPay: $publicUrl/api/payment-callback/vnpay\n";
echo "   MoMo Return: $publicUrl/api/payment-callback/momo\n";
echo "   MoMo Notify: $publicUrl/api/payment-callback/momo-notify\n\n";

// Kiểm tra config file
$configFile = __DIR__ . '/../config/payment_gateway.php';
if (file_exists($configFile)) {
    $config = require $configFile;
    
    echo "📝 Kiểm tra config file:\n";
    
    $vnpayUrl = $config['vnpay']['return_url'] ?? '';
    $momoReturn = $config['momo']['return_url'] ?? '';
    $momoNotify = $config['momo']['notify_url'] ?? '';
    
    if (strpos($vnpayUrl, $publicUrl) !== false) {
        echo "   ✅ VNPay return_url đã cấu hình đúng\n";
    } else {
        echo "   ⚠️  VNPay return_url chưa khớp với ngrok URL\n";
        echo "      Hiện tại: $vnpayUrl\n";
        echo "      Nên là: $publicUrl/api/payment-callback/vnpay\n";
    }
    
    if (strpos($momoReturn, $publicUrl) !== false) {
        echo "   ✅ MoMo return_url đã cấu hình đúng\n";
    } else {
        echo "   ⚠️  MoMo return_url chưa khớp với ngrok URL\n";
        echo "      Hiện tại: $momoReturn\n";
        echo "      Nên là: $publicUrl/api/payment-callback/momo\n";
    }
    
    if (strpos($momoNotify, $publicUrl) !== false) {
        echo "   ✅ MoMo notify_url đã cấu hình đúng\n";
    } else {
        echo "   ⚠️  MoMo notify_url chưa khớp với ngrok URL\n";
        echo "      Hiện tại: $momoNotify\n";
        echo "      Nên là: $publicUrl/api/payment-callback/momo-notify\n";
    }
} else {
    echo "⚠️  Không tìm thấy file config: $configFile\n";
}

echo "\n";

