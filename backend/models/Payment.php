<?php
/**
 * Mục đích: Model xử lý các thao tác liên quan đến Payment (Thanh toán)
 */

class Payment {
    // Kết nối database
    private $conn;
    private $table_name = "payments";

    // Thuộc tính của Payment
    public $id;
    public $enrollment_id;
    public $student_id; // For backward compatibility - from enrollment
    public $course_id; // For backward compatibility - from enrollment
    public $amount;
    public $payment_method; // Changed from payment_gateway to match database schema
    public $payment_gateway; // For backward compatibility - maps to payment_method
    public $transaction_id;
    public $status;
    public $paid_at; // Changed from payment_date to match database schema
    public $payment_date; // For backward compatibility - maps to paid_at
    
    // Thông tin bổ sung (từ JOIN với bảng users và courses)
    public $student_name;
    public $student_email;
    public $course_name;
    public $course_title;

    /**
     * Constructor - Nhận kết nối database
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Tạo payment mới
     * @return bool - true nếu thành công
     */
    public function create() {
        // Kiểm tra connection
        if (!$this->conn) {
            throw new Exception("Database connection is not available");
        }
        
        $query = "INSERT INTO " . $this->table_name . "
                SET enrollment_id = :enrollment_id,
                    amount = :amount,
                    payment_method = :payment_method,
                    transaction_id = :transaction_id,
                    status = :status";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                $error = $this->conn->errorInfo();
                throw new Exception("Failed to prepare statement: " . ($error[2] ?? "Unknown error"));
            }
        
            // Làm sạch dữ liệu
            $this->payment_method = isset($this->payment_gateway) ? htmlspecialchars(strip_tags($this->payment_gateway)) : (isset($this->payment_method) ? htmlspecialchars(strip_tags($this->payment_method)) : null);
            $this->transaction_id = htmlspecialchars(strip_tags($this->transaction_id));
            $this->status = htmlspecialchars(strip_tags($this->status));
            
            // Map student_id/course_id to enrollment_id if needed
            $enrollment_id = isset($this->enrollment_id) ? $this->enrollment_id : null;
            if (!$enrollment_id && isset($this->student_id) && isset($this->course_id)) {
                // Try to find enrollment_id from student_id and course_id
                $find_enrollment = $this->conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ? LIMIT 1");
                $find_enrollment->execute([$this->student_id, $this->course_id]);
                $enrollment = $find_enrollment->fetch(PDO::FETCH_ASSOC);
                if ($enrollment) {
                    $enrollment_id = $enrollment['id'];
                }
            }
            
            // Bind giá trị
            $stmt->bindParam(":enrollment_id", $enrollment_id);
            $stmt->bindParam(":amount", $this->amount);
            $stmt->bindParam(":payment_method", $this->payment_method);
            $stmt->bindParam(":transaction_id", $this->transaction_id);
            $stmt->bindParam(":status", $this->status);
            
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            } else {
                $error = $stmt->errorInfo();
                throw new Exception("Failed to execute statement: " . ($error[2] ?? "Unknown error"));
            }
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    /**
     * Lấy tất cả payments (có thông tin student và course)
     * @return PDOStatement - Kết quả query
     */
    public function readAll() {
        $query = "SELECT 
                    p.id,
                    p.enrollment_id,
                    e.student_id,
                    e.course_id,
                    p.amount,
                    p.payment_method as payment_gateway,
                    p.transaction_id,
                    p.status,
                    p.paid_at as payment_date,
                    COALESCE(u.full_name, u.username) as student_name,
                    u.email as student_email,
                    c.course_name,
                    c.title as course_title
                  FROM " . $this->table_name . " p
                  LEFT JOIN enrollments e ON p.enrollment_id = e.id
                  LEFT JOIN users u ON e.student_id = u.id
                  LEFT JOIN courses c ON e.course_id = c.id
                  ORDER BY p.paid_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy payments theo student_id
     * @return PDOStatement
     */
    public function readByStudent() {
        $query = "SELECT 
                    p.id,
                    p.enrollment_id,
                    e.student_id,
                    e.course_id,
                    p.amount,
                    p.payment_method as payment_gateway,
                    p.transaction_id,
                    p.status,
                    p.paid_at as payment_date,
                    c.course_name,
                    c.title as course_title
                  FROM " . $this->table_name . " p
                  LEFT JOIN enrollments e ON p.enrollment_id = e.id
                  LEFT JOIN courses c ON e.course_id = c.id
                  WHERE e.student_id = :student_id
                  ORDER BY p.paid_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy payments theo course_id (cho teacher)
     * @return PDOStatement
     */
    public function readByCourse() {
        $query = "SELECT 
                    p.id,
                    p.enrollment_id,
                    e.student_id,
                    e.course_id,
                    p.amount,
                    p.payment_method as payment_gateway,
                    p.transaction_id,
                    p.status,
                    p.paid_at as payment_date,
                    COALESCE(u.full_name, u.username) as student_name,
                    u.email as student_email
                  FROM " . $this->table_name . " p
                  LEFT JOIN enrollments e ON p.enrollment_id = e.id
                  LEFT JOIN users u ON e.student_id = u.id
                  WHERE e.course_id = :course_id
                  ORDER BY p.paid_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy payments theo status
     * @return PDOStatement
     */
    public function readByStatus() {
        $query = "SELECT 
                    p.id,
                    p.enrollment_id,
                    e.student_id,
                    e.course_id,
                    p.amount,
                    p.payment_method as payment_gateway,
                    p.transaction_id,
                    p.status,
                    p.paid_at as payment_date,
                    COALESCE(u.full_name, u.username) as student_name,
                    c.course_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN enrollments e ON p.enrollment_id = e.id
                  LEFT JOIN users u ON e.student_id = u.id
                  LEFT JOIN courses c ON e.course_id = c.id
                  WHERE p.status = :status
                  ORDER BY p.paid_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $this->status);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy chi tiết 1 payment theo ID
     * @return bool - true nếu tìm thấy
     */
    public function readOne() {
        $query = "SELECT 
                    p.id,
                    p.enrollment_id,
                    e.student_id,
                    e.course_id,
                    p.amount,
                    p.payment_method as payment_gateway,
                    p.transaction_id,
                    p.status,
                    p.paid_at as payment_date,
                    COALESCE(u.full_name, u.username) as student_name,
                    u.email as student_email,
                    c.course_name,
                    c.title as course_title
                  FROM " . $this->table_name . " p
                  LEFT JOIN enrollments e ON p.enrollment_id = e.id
                  LEFT JOIN users u ON e.student_id = u.id
                  LEFT JOIN courses c ON e.course_id = c.id
                  WHERE p.id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->enrollment_id = $row['enrollment_id'];
            $this->student_id = $row['student_id'];
            $this->course_id = $row['course_id'];
            $this->amount = $row['amount'];
            $this->payment_method = $row['payment_gateway'];
            $this->payment_gateway = $row['payment_gateway']; // For backward compatibility
            $this->transaction_id = $row['transaction_id'];
            $this->status = $row['status'];
            $this->paid_at = $row['payment_date'];
            $this->payment_date = $row['payment_date']; // For backward compatibility
            
            // Thông tin bổ sung
            $this->student_name = $row['student_name'];
            $this->student_email = $row['student_email'];
            $this->course_name = $row['course_name'];
            $this->course_title = $row['course_title'];
            
            return true;
        }
        
        return false;
    }

    /**
     * Cập nhật status của payment
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
     * Xóa payment
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
     * Đếm tổng số payments
     * @return int
     */
    public function countAll() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    /**
     * Đếm số payments theo status
     * @return int
     */
    public function countByStatus() {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . "
                  WHERE status = :status";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $this->status);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    /**
     * Tính tổng doanh thu theo status
     * @return float
     */
    public function sumByStatus() {
        $query = "SELECT SUM(amount) as total 
                  FROM " . $this->table_name . "
                  WHERE status = :status";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $this->status);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    /**
     * Kiểm tra transaction_id đã tồn tại chưa
     * @return bool - true nếu đã tồn tại
     */
    public function transactionExists() {
        $query = "SELECT id FROM " . $this->table_name . "
                  WHERE transaction_id = :transaction_id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $this->transaction_id = htmlspecialchars(strip_tags($this->transaction_id));
        $stmt->bindParam(":transaction_id", $this->transaction_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }
}
?>

