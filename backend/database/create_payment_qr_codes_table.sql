-- ============================================
-- BẢNG: PAYMENT_QR_CODES (Mã QR thanh toán)
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

