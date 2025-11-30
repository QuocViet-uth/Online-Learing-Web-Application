-- ============================================
-- Tạo bảng payment_qr_codes
-- ============================================
CREATE TABLE IF NOT EXISTS payment_qr_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_gateway ENUM('momo', 'vnpay', 'bank_transfer') NOT NULL COMMENT 'Phương thức thanh toán',
    qr_code_image VARCHAR(500) NOT NULL COMMENT 'URL hoặc path đến ảnh QR code',
    account_number VARCHAR(100) DEFAULT NULL COMMENT 'Số tài khoản (cho bank transfer)',
    account_name VARCHAR(255) DEFAULT NULL COMMENT 'Tên chủ tài khoản',
    bank_name VARCHAR(255) DEFAULT NULL COMMENT 'Tên ngân hàng (cho bank transfer)',
    phone_number VARCHAR(20) DEFAULT NULL COMMENT 'Số điện thoại (cho MoMo)',
    description TEXT COMMENT 'Mô tả thêm',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Trạng thái',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_payment_gateway (payment_gateway),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Thêm dữ liệu mẫu QR codes
-- ============================================
INSERT INTO payment_qr_codes (payment_gateway, qr_code_image, account_number, account_name, bank_name, phone_number, description, status) VALUES
('bank_transfer', 'https://img.vietqr.io/image/VCB-9704229207136-compact.png', '9704229207136', 'NGUYEN VAN A', 'Vietcombank', NULL, 'Quét mã QR để thanh toán qua ngân hàng', 'active'),
('momo', 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=2|99|0123456789|NGUYEN VAN A|admin@example.com|0|0|50000|', NULL, 'NGUYEN VAN A', NULL, '0123456789', 'Quét mã QR để thanh toán qua MoMo', 'active'),
('vnpay', 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=https://vnpay.vn/qr/pay?account=0123456789', NULL, 'NGUYEN VAN A', 'VNPay', '0123456789', 'Quét mã QR để thanh toán qua VNPay', 'active');
