<?php
/**
 * Mục đích: Model xử lý các thao tác liên quan đến Enrollment (Đăng ký học)
 */

class Enrollment {
    // Kết nối database
    private $conn;
    private $table_name = "enrollments";

    // Thuộc tính của Enrollment
    public $id;
    public $student_id;
    public $course_id;
    public $enrolled_at; // Changed from enrollment_date to match database schema
    public $status;
    
    // Thông tin bổ sung (từ JOIN với bảng users và courses)
    public $student_name;
    public $student_email;
    public $course_name;
    public $course_title;
    public $course_price;

    /**
     * Constructor - Nhận kết nối database
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Tạo enrollment mới
     * @return bool - true nếu thành công
     */
    public function create() {
        // Kiểm tra connection
        if (!$this->conn) {
            throw new Exception("Database connection is not available");
        }
        
        $query = "INSERT INTO " . $this->table_name . "
                SET student_id = :student_id,
                    course_id = :course_id,
                    status = :status";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                $error = $this->conn->errorInfo();
                throw new Exception("Failed to prepare statement: " . ($error[2] ?? "Unknown error"));
            }
        
            // Làm sạch dữ liệu
            $this->status = htmlspecialchars(strip_tags($this->status));
            
            // Bind giá trị
            $stmt->bindParam(":student_id", $this->student_id);
            $stmt->bindParam(":course_id", $this->course_id);
            $stmt->bindParam(":status", $this->status);
            
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            } else {
                $error = $stmt->errorInfo();
                throw new Exception("Failed to execute statement: " . ($error[2] ?? "Unknown error"));
            }
        } catch (PDOException $e) {
            // Kiểm tra nếu là lỗi duplicate entry
            if ($e->getCode() == 23000) {
                throw new Exception("Bạn đã đăng ký khóa học này rồi!");
            }
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    /**
     * Lấy tất cả enrollments (có thông tin student và course)
     * @return PDOStatement - Kết quả query
     */
    public function readAll() {
        $query = "SELECT 
                    e.id,
                    e.student_id,
                    e.course_id,
                    e.enrolled_at,
                    e.status,
                    COALESCE(u.full_name, u.username) as student_name,
                    u.email as student_email,
                    c.course_name,
                    c.title as course_title,
                    c.price as course_price
                  FROM " . $this->table_name . " e
                  LEFT JOIN users u ON e.student_id = u.id
                  LEFT JOIN courses c ON e.course_id = c.id
                  ORDER BY e.enrolled_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy enrollments theo student_id
     * @return PDOStatement
     */
    public function readByStudent() {
        $query = "SELECT 
                    e.id,
                    e.student_id,
                    e.course_id,
                    e.enrolled_at,
                    e.status,
                    c.course_name,
                    c.title as course_title,
                    c.price as course_price,
                    c.thumbnail,
                    c.status as course_status,
                    c.start_date,
                    c.end_date
                  FROM " . $this->table_name . " e
                  LEFT JOIN courses c ON e.course_id = c.id
                  WHERE e.student_id = :student_id
                  ORDER BY e.enrolled_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy enrollments theo course_id
     * @return PDOStatement
     */
    public function readByCourse() {
        $query = "SELECT 
                    e.id,
                    e.student_id,
                    e.course_id,
                    e.enrolled_at,
                    e.status,
                    COALESCE(u.full_name, u.username) as student_name,
                    u.email as student_email
                  FROM " . $this->table_name . " e
                  LEFT JOIN users u ON e.student_id = u.id
                  WHERE e.course_id = :course_id
                  ORDER BY e.enrolled_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy enrollment theo student_id và course_id
     * @return bool - true nếu tìm thấy
     */
    public function readByStudentAndCourse() {
        $query = "SELECT 
                    e.id,
                    e.student_id,
                    e.course_id,
                    e.enrolled_at,
                    e.status
                  FROM " . $this->table_name . " e
                  WHERE e.student_id = :student_id
                    AND e.course_id = :course_id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id = $row['id'];
            $this->student_id = $row['student_id'];
            $this->course_id = $row['course_id'];
            $this->enrollment_date = $row['enrollment_date'];
            $this->status = $row['status'];
            return true;
        }
        
        return false;
    }

    /**
     * Lấy chi tiết 1 enrollment theo ID
     * @return bool - true nếu tìm thấy
     */
    public function readOne() {
        $query = "SELECT 
                    e.id,
                    e.student_id,
                    e.course_id,
                    e.enrolled_at,
                    e.status,
                    COALESCE(u.full_name, u.username) as student_name,
                    u.email as student_email,
                    c.course_name,
                    c.title as course_title,
                    c.price as course_price
                  FROM " . $this->table_name . " e
                  LEFT JOIN users u ON e.student_id = u.id
                  LEFT JOIN courses c ON e.course_id = c.id
                  WHERE e.id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->student_id = $row['student_id'];
            $this->course_id = $row['course_id'];
            $this->enrollment_date = $row['enrollment_date'];
            $this->status = $row['status'];
            
            // Thông tin bổ sung
            $this->student_name = $row['student_name'];
            $this->student_email = $row['student_email'];
            $this->course_name = $row['course_name'];
            $this->course_title = $row['course_title'];
            $this->course_price = $row['course_price'];
            
            return true;
        }
        
        return false;
    }

    /**
     * Cập nhật status của enrollment
     * @return bool - true nếu thành công
     */
    public function updateStatus() {
        $query = "UPDATE " . $this->table_name . "
                  SET status = :status
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch dữ liệu
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Bind giá trị
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Xóa enrollment (cancel enrollment)
     * @return bool - true nếu thành công
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
     * Xóa enrollment theo student_id và course_id
     * @return bool - true nếu thành công
     */
    public function deleteByStudentAndCourse() {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE student_id = :student_id
                    AND course_id = :course_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":course_id", $this->course_id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Kiểm tra enrollment đã tồn tại chưa
     * @return bool - true nếu đã tồn tại
     */
    public function exists() {
        $query = "SELECT id FROM " . $this->table_name . "
                  WHERE student_id = :student_id
                    AND course_id = :course_id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }

    /**
     * Đếm số lượng enrollments theo course
     * @return int
     */
    public function countByCourse() {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . "
                  WHERE course_id = :course_id
                    AND status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>

