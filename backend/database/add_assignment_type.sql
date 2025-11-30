-- Thêm cột type vào bảng assignments
ALTER TABLE assignments 
ADD COLUMN type ENUM('homework', 'quiz') NOT NULL DEFAULT 'homework' 
AFTER course_id;

-- Cập nhật index nếu cần
ALTER TABLE assignments 
ADD INDEX idx_type (type);
