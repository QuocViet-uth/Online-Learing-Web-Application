<?php
/**
 * Script lấy URL ngrok từ ngrok API
 * Chạy: php scripts/get-ngrok-url.php
 * 
 * Yêu cầu: ngrok phải đang chạy trên port 4040
 */

echo "=== LẤY URL NGROK ===\n\n";

$ngrokApi = 'http://127.0.0.1:4040/api/tunnels';
$response = @file_get_contents($ngrokApi);

if (!$response) {
    echo "❌ Không thể kết nối đến ngrok API.\n";
    echo "   Đảm bảo ngrok đang chạy: ngrok http 8000\n";
    exit(1);
}

$data = json_decode($response, true);

if (!$data || !isset($data['tunnels']) || empty($data['tunnels'])) {
    echo "❌ Không tìm thấy tunnel nào.\n";
    echo "   Đảm bảo ngrok đang chạy và đã expose port 8000.\n";
    exit(1);
}

// Lấy tunnel đầu tiên (thường là HTTPS)
$tunnel = $data['tunnels'][0];
$publicUrl = $tunnel['public_url'] ?? null;

if (!$publicUrl) {
    echo "❌ Không tìm thấy public URL.\n";
    exit(1);
}

echo "✅ Ngrok đang chạy!\n\n";
echo "📋 Thông tin:\n";
echo "   Public URL: $publicUrl\n";
echo "   Local URL: {$tunnel['config']['addr']}\n";
echo "   Protocol: {$tunnel['proto']}\n\n";

echo "🔗 URLs cần cập nhật:\n\n";

echo "--- VNPay ---\n";
echo "Return URL:\n";
echo "   $publicUrl/api/payment-callback/vnpay\n\n";

echo "--- MoMo ---\n";
echo "Return URL:\n";
echo "   $publicUrl/api/payment-callback/momo\n";
echo "Notify URL (IPN):\n";
echo "   $publicUrl/api/payment-callback/momo-notify\n\n";

echo "📝 Cập nhật vào file: backend/config/payment_gateway.php\n";
echo "   'return_url' => '$publicUrl/api/payment-callback/vnpay',\n";
echo "   'return_url' => '$publicUrl/api/payment-callback/momo',\n";
echo "   'notify_url' => '$publicUrl/api/payment-callback/momo-notify',\n\n";

echo "💡 Tip: Bạn có thể copy các URL trên và paste vào config file.\n";
echo "\n";

