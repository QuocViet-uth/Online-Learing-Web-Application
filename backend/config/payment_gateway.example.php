<?php
/**
 * File: config/payment_gateway.example.php
 * Đây là file ví dụ cấu hình payment gateway
 * 
 * HƯỚNG DẪN:
 * 1. Copy file này thành payment_gateway.php
 * 2. Điền thông tin thật từ VNPay và MoMo
 * 3. KHÔNG commit file payment_gateway.php vào git (đã có trong .gitignore)
 */

return array(
    'vnpay' => array(
        'enabled' => true,
        
        // ⬇️ ĐIỀN THÔNG TIN VNPAY VÀO ĐÂY ⬇️
        'tmn_code' => 'YOUR_VNPAY_TMN_CODE',        // Ví dụ: '2QXUI4J4'
        'secret_key' => 'YOUR_VNPAY_SECRET_KEY',    // Ví dụ: 'RAOCTRKLRBIGFYNZZTUNZXCGUXMCOZRB'
        
        // URL thanh toán (không cần thay đổi nếu dùng sandbox)
        'url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html', // Sandbox
        // 'url' => 'https://www.vnpayment.vn/paymentv2/vpcpay.html', // Production (uncomment khi chuyển sang production)
        
        // Callback URL - Thay đổi domain của bạn
        'return_url' => 'http://localhost:8000/api/payment-callback/vnpay', // Local
        // 'return_url' => 'https://yourdomain.com/api/payment-callback/vnpay', // Production
        
        // Các thông tin khác (không cần thay đổi)
        'version' => '2.1.0',
        'command' => 'pay',
        'currency' => 'VND',
        'locale' => 'vn'
    ),
    
    'momo' => array(
        'enabled' => true,
        
        // ⬇️ ĐIỀN THÔNG TIN MOMO VÀO ĐÂY ⬇️
        'partner_code' => 'YOUR_MOMO_PARTNER_CODE',  // Ví dụ: 'MOMOBKUN20180529'
        'access_key' => 'YOUR_MOMO_ACCESS_KEY',       // Ví dụ: 'klm05TvNBzhg7h7j'
        'secret_key' => 'YOUR_MOMO_SECRET_KEY',      // Ví dụ: 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa'
        
        // Endpoint API (không cần thay đổi nếu dùng sandbox)
        'endpoint' => 'https://test-payment.momo.vn/v2/gateway/api/create', // Sandbox
        // 'endpoint' => 'https://payment.momo.vn/v2/gateway/api/create', // Production (uncomment khi chuyển sang production)
        
        // Callback URLs - Thay đổi domain của bạn
        'return_url' => 'http://localhost:8000/api/payment-callback/momo', // Local
        // 'return_url' => 'https://yourdomain.com/api/payment-callback/momo', // Production
        
        'notify_url' => 'http://localhost:8000/api/payment-callback/momo-notify', // Local
        // 'notify_url' => 'https://yourdomain.com/api/payment-callback/momo-notify', // Production
        
        // Các thông tin khác (không cần thay đổi)
        'store_id' => 'MomoTestStore',
        'lang' => 'vi'
    ),
    
    'bank_transfer' => array(
        'enabled' => true,
        // Bank transfer không cần API, chỉ cần QR code và thông tin tài khoản
        // QR code được quản lý trong Admin panel
    )
);

