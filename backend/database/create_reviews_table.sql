-- ============================================
-- BẢNG: REVIEWS (Đánh giá khóa học)
-- ============================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5) COMMENT 'Điểm đánh giá từ 1-5 sao',
    comment TEXT DEFAULT NULL COMMENT 'Bình luận đánh giá',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Một học viên chỉ có thể đánh giá một khóa học một lần
    UNIQUE KEY unique_course_student (course_id, student_id),
    
    -- Indexes để tăng tốc độ truy vấn
    INDEX idx_course_id (course_id),
    INDEX idx_student_id (student_id),
    INDEX idx_rating (rating),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

