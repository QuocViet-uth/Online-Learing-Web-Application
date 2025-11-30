<?php
/**
 * File: models/Submission.php
 * Mục đích: Model xử lý các thao tác liên quan đến Submission (Bài nộp)
 * Người tạo: Thanh
 * Ngày tạo: [Ngày hôm nay]
 */

require_once 'Assignment.php';

class Submission {
    // Kết nối database
    private $conn;
    private $table_name = "submissions";

    // Thuộc tính của Submission
    public $id;
    public $assignment_id;
    public $student_id;
    public $content;
    public $attachment_file;
    public $submit_date;
    public $score;
    public $status;
    public $feedback;
    public $graded_at;
    
    // Thông tin bổ sung
    public $assignment_title;
    public $student_name;
    public $course_name;

    /**
     * Constructor
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    // ==================== CÁC PHƯƠNG THỨC CRUD ====================

    /**
     * Nộp bài (Tạo submission mới)
     * @return bool
     */
    public function create() {
        // Kiểm tra deadline để set status
        $assignment = new Assignment($this->conn);
        $assignment->id = $this->assignment_id;
        $status = 'submitted';
        
        if ($assignment->readOne()) {
            $deadline = strtotime($assignment->deadline);
            $now = time();
            if ($deadline < $now) {
                $status = 'late';
            }
        }
        
        $query = "INSERT INTO " . $this->table_name . "
                SET assignment_id = :assignment_id,
                    student_id = :student_id,
                    content = :content,
                    attachment_file = :attachment_file,
                    status = :status";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch dữ liệu - check for string before strip_tags to avoid PHP 8.1+ deprecation
        $this->content = (!empty($this->content) && is_string($this->content)) ? htmlspecialchars(strip_tags($this->content)) : '';
        $this->attachment_file = (!empty($this->attachment_file) && is_string($this->attachment_file)) ? htmlspecialchars(strip_tags($this->attachment_file)) : null;
        
        // Bind giá trị
        $stmt->bindParam(":assignment_id", $this->assignment_id);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":attachment_file", $this->attachment_file);
        $stmt->bindParam(":status", $status);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            $this->status = $status;
            return true;
        }
        
        return false;
    }

    /**
     * Lấy bài nộp theo assignment
     * * @return PDOStatement
     */
    public function readByAssignment() {
        $query = "SELECT 
                    s.id,
                    s.assignment_id,
                    s.student_id,
                    s.content,
                    s.attachment_file,
                    s.submitted_at as submit_date,
                    s.score,
                    s.status,
                    s.feedback,
                    s.graded_at,
                    COALESCE(u.full_name, u.username) as student_name,
                    u.email as student_email
                  FROM " . $this->table_name . " s
                  LEFT JOIN users u ON s.student_id = u.id
                  WHERE s.assignment_id = :assignment_id
                  ORDER BY s.submitted_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":assignment_id", $this->assignment_id);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy bài nộp theo student
     * @return PDOStatement
     */
    public function readByStudent() {
        $query = "SELECT 
                    s.id,
                    s.assignment_id,
                    s.student_id,
                    s.content,
                    s.attachment_file,
                    s.submitted_at as submit_date,
                    s.score,
                    s.status,
                    s.feedback,
                    s.graded_at,
                    a.title as assignment_title,
                    a.max_score,
                    a.deadline,
                    c.course_name
                  FROM " . $this->table_name . " s
                  LEFT JOIN assignments a ON s.assignment_id = a.id
                  LEFT JOIN courses c ON a.course_id = c.id
                  WHERE s.student_id = :student_id
                  ORDER BY s.submitted_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy chi tiết 1 bài nộp
     * @return bool
     */
    public function readOne() {
        $query = "SELECT 
                    s.id,
                    s.assignment_id,
                    s.student_id,
                    s.content,
                    s.attachment_file,
                    s.submitted_at as submit_date,
                    s.score,
                    s.status,
                    s.feedback,
                    s.graded_at,
                    a.title as assignment_title,
                    a.max_score,
                    a.deadline,
                    COALESCE(u.full_name, u.username) as student_name,
                    u.email as student_email,
                    c.course_name
                  FROM " . $this->table_name . " s
                  LEFT JOIN assignments a ON s.assignment_id = a.id
                  LEFT JOIN users u ON s.student_id = u.id
                  LEFT JOIN courses c ON a.course_id = c.id
                  WHERE s.id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->assignment_id = $row['assignment_id'];
            $this->student_id = $row['student_id'];
            $this->content = $row['content'];
            $this->attachment_file = $row['attachment_file'];
            $this->submit_date = $row['submit_date'];
            $this->score = $row['score'];
            $this->status = $row['status'];
            $this->feedback = $row['feedback'];
            $this->graded_at = $row['graded_at'];
            $this->assignment_title = $row['assignment_title'];
            $this->student_name = $row['student_name'];
            $this->course_name = $row['course_name'];
            
            return true;
        }
        
        return false;
    }

    /**
     * Cập nhật bài nộp (cho phép học viên sửa trước deadline)
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET content = :content,
                      attachment_file = :attachment_file
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch dữ liệu - check for string before strip_tags to avoid PHP 8.1+ deprecation
        $this->content = (!empty($this->content) && is_string($this->content)) ? htmlspecialchars(strip_tags($this->content)) : '';
        $this->attachment_file = (!empty($this->attachment_file) && is_string($this->attachment_file)) ? htmlspecialchars(strip_tags($this->attachment_file)) : null;
        
        // Bind giá trị
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":attachment_file", $this->attachment_file);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Chấm điểm bài nộp (Teacher)
     * @return bool
     */
    public function grade() {
        $query = "UPDATE " . $this->table_name . "
                  SET score = :score,
                      feedback = :feedback,
                      status = 'graded',
                      graded_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch feedback
        $this->feedback = !empty($this->feedback) && is_string($this->feedback) ? htmlspecialchars(strip_tags($this->feedback)) : null;
        
        // Bind giá trị
        $stmt->bindParam(":score", $this->score);
        $stmt->bindParam(":feedback", $this->feedback);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Xóa bài nộp
     * @return bool
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Kiểm tra học viên đã nộp bài chưa
     * @return bool
     */
    public function hasSubmitted() {
        $query = "SELECT id FROM " . $this->table_name . "
                  WHERE assignment_id = :assignment_id
                    AND student_id = :student_id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":assignment_id", $this->assignment_id);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }

    /**
     * Đếm số bài nộp theo assignment
     * @return int
     */
    public function countByAssignment() {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . "
                  WHERE assignment_id = :assignment_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":assignment_id", $this->assignment_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    /**
     * Đếm số bài chưa chấm
     * @return int
     */
    public function countUngraded() {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . "
                  WHERE assignment_id = :assignment_id
                    AND status = 'submitted'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":assignment_id", $this->assignment_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    /**
     * Tính điểm trung bình của 1 assignment
     * @return float
     */
    public function getAverageScore() {
        $query = "SELECT AVG(score) as avg_score 
                  FROM " . $this->table_name . "
                  WHERE assignment_id = :assignment_id
                    AND status = 'graded'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":assignment_id", $this->assignment_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['avg_score'] ? round($row['avg_score'], 2) : 0;
    }
}
?>