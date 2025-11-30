<?php
/**
 * Service xử lý tích hợp với các payment gateway
 */

class PaymentGatewayService {
    private $db;
    
    // VNPay Config
    private $vnpay_url = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
    private $vnpay_tmn_code = ''; // Cần cấu hình
    private $vnpay_secret_key = ''; // Cần cấu hình
    private $vnpay_return_url = ''; // URL callback
    
    // MoMo Config
    private $momo_endpoint = 'https://test-payment.momo.vn/v2/gateway/api/create';
    private $momo_partner_code = ''; // Cần cấu hình
    private $momo_access_key = ''; // Cần cấu hình
    private $momo_secret_key = ''; // Cần cấu hình
    private $momo_return_url = ''; // URL callback
    private $momo_notify_url = ''; // URL notify
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadConfig();
    }
    
    /**
     * Load config từ database hoặc file config
     */
    private function loadConfig() {
        // Load từ config file
        $configFile = __DIR__ . '/../config/payment_gateway.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            
            // VNPay config
            if (isset($config['vnpay'])) {
                $this->vnpay_tmn_code = $config['vnpay']['tmn_code'] ?? '';
                $this->vnpay_secret_key = $config['vnpay']['secret_key'] ?? '';
                $this->vnpay_url = $config['vnpay']['url'] ?? $this->vnpay_url;
                $this->vnpay_return_url = $config['vnpay']['return_url'] ?? '';
            }
            
            // MoMo config
            if (isset($config['momo'])) {
                $this->momo_partner_code = $config['momo']['partner_code'] ?? '';
                $this->momo_access_key = $config['momo']['access_key'] ?? '';
                $this->momo_secret_key = $config['momo']['secret_key'] ?? '';
                $this->momo_endpoint = $config['momo']['endpoint'] ?? $this->momo_endpoint;
                $this->momo_return_url = $config['momo']['return_url'] ?? '';
                $this->momo_notify_url = $config['momo']['notify_url'] ?? '';
            }
        }
    }
    
    /**
     * Tạo payment URL cho VNPay
     */
    public function createVNPayPayment($paymentData) {
        // Kiểm tra config
        if (empty($this->vnpay_tmn_code) || empty($this->vnpay_secret_key)) {
            return array(
                'success' => false,
                'message' => 'VNPay chưa được cấu hình. Vui lòng cấu hình tmn_code và secret_key trong file config.'
            );
        }
        
        if (empty($this->vnpay_return_url)) {
            return array(
                'success' => false,
                'message' => 'VNPay return_url chưa được cấu hình.'
            );
        }
        
        $vnp_TxnRef = $paymentData['transaction_id'];
        $vnp_OrderInfo = $paymentData['order_info'] ?? 'Thanh toan khoa hoc';
        $vnp_OrderType = 'other';
        $vnp_Amount = $paymentData['amount'] * 100; // VNPay yêu cầu số tiền nhân 100
        $vnp_Locale = 'vn';
        $vnp_BankCode = '';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $this->vnpay_tmn_code,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $this->vnpay_return_url,
            "vnp_TxnRef" => $vnp_TxnRef,
        );
        
        if (!empty($vnp_BankCode)) {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }
        
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        
        $vnp_Url = $this->vnpay_url . "?" . $query;
        if (!empty($this->vnpay_secret_key)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $this->vnpay_secret_key);
            $vnp_Url .= '&vnp_SecureHash=' . $vnpSecureHash;
        }
        
        return array(
            'success' => true,
            'payment_url' => $vnp_Url,
            'transaction_id' => $vnp_TxnRef
        );
    }
    
    /**
     * Tạo payment request cho MoMo
     */
    public function createMoMoPayment($paymentData) {
        // Kiểm tra config
        if (empty($this->momo_partner_code) || empty($this->momo_access_key) || empty($this->momo_secret_key)) {
            return array(
                'success' => false,
                'message' => 'MoMo chưa được cấu hình. Vui lòng cấu hình partner_code, access_key và secret_key trong file config.'
            );
        }
        
        if (empty($this->momo_return_url) || empty($this->momo_notify_url)) {
            return array(
                'success' => false,
                'message' => 'MoMo return_url hoặc notify_url chưa được cấu hình.'
            );
        }
        
        $orderId = $paymentData['transaction_id'];
        $orderInfo = $paymentData['order_info'] ?? 'Thanh toan khoa hoc';
        $amount = $paymentData['amount'];
        $ipnUrl = $this->momo_notify_url;
        $redirectUrl = $this->momo_return_url;
        
        $requestId = time() . "";
        $requestType = "captureWallet";
        $extraData = "";
        
        // Tạo raw hash
        $rawHash = "accessKey=" . $this->momo_access_key . 
                   "&amount=" . $amount . 
                   "&extraData=" . $extraData . 
                   "&ipnUrl=" . $ipnUrl . 
                   "&orderId=" . $orderId . 
                   "&orderInfo=" . $orderInfo . 
                   "&partnerCode=" . $this->momo_partner_code . 
                   "&redirectUrl=" . $redirectUrl . 
                   "&requestId=" . $requestId . 
                   "&requestType=" . $requestType;
        
        $signature = hash_hmac("sha256", $rawHash, $this->momo_secret_key);
        
        $data = array(
            'partnerCode' => $this->momo_partner_code,
            'partnerName' => "Online Learning",
            "storeId" => "MomoTestStore",
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        );
        
        // Gọi API MoMo
        $ch = curl_init($this->momo_endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $result = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($curlError) {
            error_log("MoMo API curl error: " . $curlError);
            return array(
                'success' => false,
                'message' => 'Lỗi kết nối đến MoMo: ' . $curlError
            );
        }
        
        if ($httpCode == 200) {
            $resultData = json_decode($result, true);
            if (isset($resultData['payUrl'])) {
                return array(
                    'success' => true,
                    'payment_url' => $resultData['payUrl'],
                    'transaction_id' => $orderId
                );
            } else {
                $errorMessage = $resultData['message'] ?? 'Không thể tạo payment request từ MoMo';
                error_log("MoMo API error: " . json_encode($resultData));
                return array(
                    'success' => false,
                    'message' => $errorMessage
                );
            }
        } else {
            error_log("MoMo API HTTP error: " . $httpCode . " - " . $result);
            return array(
                'success' => false,
                'message' => 'Lỗi từ MoMo API (HTTP ' . $httpCode . ')'
            );
        }
    }
    
    /**
     * Xác thực callback từ VNPay
     */
    public function verifyVNPayCallback($data) {
        $vnp_SecureHash = $data['vnp_SecureHash'] ?? '';
        unset($data['vnp_SecureHash']);
        
        ksort($data);
        $i = 0;
        $hashData = "";
        foreach ($data as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        
        $secureHash = hash_hmac('sha512', $hashData, $this->vnpay_secret_key);
        
        if ($secureHash == $vnp_SecureHash) {
            return array(
                'success' => true,
                'transaction_id' => $data['vnp_TxnRef'] ?? '',
                'amount' => ($data['vnp_Amount'] ?? 0) / 100,
                'response_code' => $data['vnp_ResponseCode'] ?? '',
                'transaction_status' => $data['vnp_ResponseCode'] == '00' ? 'success' : 'failed'
            );
        }
        
        return array('success' => false, 'message' => 'Invalid signature');
    }
    
    /**
     * Xác thực callback từ MoMo
     */
    public function verifyMoMoCallback($data) {
        $accessKey = $data['accessKey'] ?? '';
        $amount = $data['amount'] ?? 0;
        $extraData = $data['extraData'] ?? '';
        $message = $data['message'] ?? '';
        $orderId = $data['orderId'] ?? '';
        $orderInfo = $data['orderInfo'] ?? '';
        $orderType = $data['orderType'] ?? '';
        $partnerCode = $data['partnerCode'] ?? '';
        $payType = $data['payType'] ?? '';
        $requestId = $data['requestId'] ?? '';
        $responseTime = $data['responseTime'] ?? '';
        $resultCode = $data['resultCode'] ?? '';
        $transId = $data['transId'] ?? '';
        $signature = $data['signature'] ?? '';
        
        $rawHash = "accessKey=" . $accessKey . 
                   "&amount=" . $amount . 
                   "&extraData=" . $extraData . 
                   "&message=" . $message . 
                   "&orderId=" . $orderId . 
                   "&orderInfo=" . $orderInfo . 
                   "&orderType=" . $orderType . 
                   "&partnerCode=" . $partnerCode . 
                   "&payType=" . $payType . 
                   "&requestId=" . $requestId . 
                   "&responseTime=" . $responseTime . 
                   "&resultCode=" . $resultCode . 
                   "&transId=" . $transId;
        
        $checkSignature = hash_hmac("sha256", $rawHash, $this->momo_secret_key);
        
        if ($checkSignature == $signature && $resultCode == 0) {
            return array(
                'success' => true,
                'transaction_id' => $orderId,
                'amount' => $amount,
                'transaction_status' => 'success'
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Invalid signature or payment failed',
            'transaction_status' => 'failed'
        );
    }
}
?>

