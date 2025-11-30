-- Cập nhật descriptions với UTF-8 đúng
UPDATE payment_qr_codes 
SET description = 'Quét mã QR để thanh toán qua ngân hàng' 
WHERE payment_gateway = 'bank_transfer';

UPDATE payment_qr_codes 
SET description = 'Quét mã QR để thanh toán qua MoMo' 
WHERE payment_gateway = 'momo';

UPDATE payment_qr_codes 
SET description = 'Quét mã QR để thanh toán qua VNPay' 
WHERE payment_gateway = 'vnpay';
