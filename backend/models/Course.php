<?php
/**
 * Mục đích: Model xử lý các thao tác liên quan đến Course (Lớp học)
 */

class Course {
    // Kết nối database
    private $conn;
    private $table_name = "courses";

    // Thuộc tính của Course
    public $id;
    public $course_name;
    public $title;
    public $description;
    public $price;
    public $teacher_id;
    public $start_date;
    public $end_date;
    public $status;
    public $thumbnail;
    public $online_link;
    public $created_at;
    public $updated_at;
    
    // Thông tin bổ sung (từ JOIN với bảng users)
    public $teacher_name;
    public $teacher_email;

    /**
     * Constructor - Nhận kết nối database
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Tạo lớp học mới
     * @return bool - true nếu thành công
     */
    public function create() {
        // Kiểm tra connection
        if (!$this->conn) {
            throw new Exception("Database connection is not available");
        }
        
        $query = "INSERT INTO " . $this->table_name . "
                SET course_name = :course_name,
                    title = :title,
                    description = :description,
                    price = :price,
                    teacher_id = :teacher_id,
                    start_date = :start_date,
                    end_date = :end_date,
                    status = :status,
                    thumbnail = :thumbnail,
                    online_link = :online_link";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                $error = $this->conn->errorInfo();
                throw new Exception("Failed to prepare statement: " . ($error[2] ?? "Unknown error"));
            }
        
            // Làm sạch dữ liệu - chỉ xử lý nếu không null
            $this->course_name = $this->course_name !== null ? htmlspecialchars(strip_tags($this->course_name)) : '';
            $this->title = $this->title !== null ? htmlspecialchars(strip_tags($this->title)) : '';
            $this->description = $this->description !== null ? htmlspecialchars(strip_tags($this->description)) : '';
            $this->status = $this->status !== null ? htmlspecialchars(strip_tags($this->status)) : 'upcoming';
            $this->thumbnail = $this->thumbnail !== null ? htmlspecialchars(strip_tags($this->thumbnail)) : '';
            $this->online_link = $this->online_link !== null ? htmlspecialchars(strip_tags($this->online_link)) : '';
            
            // Bind giá trị
            $stmt->bindParam(":course_name", $this->course_name);
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":price", $this->price);
            $stmt->bindParam(":teacher_id", $this->teacher_id);
            $stmt->bindParam(":start_date", $this->start_date);
            $stmt->bindParam(":end_date", $this->end_date);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":thumbnail", $this->thumbnail);
            $stmt->bindParam(":online_link", $this->online_link);
            
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
     * Lấy tất cả lớp học (có thông tin giảng viên)
     * @return PDOStatement - Kết quả query
     */
    public function readAll() {
        $query = "SELECT 
                    c.id,
                    c.course_name,
                    c.title,
                    c.description,
                    c.price,
                    c.teacher_id,
                    c.start_date,
                    c.end_date,
                    c.status,
                    c.thumbnail,
                    c.online_link,
                    c.created_at,
                    c.updated_at,
                    u.username as teacher_name,
                    u.full_name as teacher_full_name,
                    u.email as teacher_email,
                    u.avatar as teacher_avatar
                  FROM " . $this->table_name . " c
                  LEFT JOIN users u ON c.teacher_id = u.id
                  ORDER BY c.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy lớp học theo status (upcoming, active, closed)
     * @return PDOStatement
     */
    public function readByStatus() {
        $query = "SELECT 
                    c.id,
                    c.course_name,
                    c.title,
                    c.description,
                    c.price,
                    c.teacher_id,
                    c.start_date,
                    c.end_date,
                    c.status,
                    c.thumbnail,
                    c.online_link,
                    c.created_at,
                    u.username as teacher_name,
                    u.full_name as teacher_full_name,
                    u.avatar as teacher_avatar
                  FROM " . $this->table_name . " c
                  LEFT JOIN users u ON c.teacher_id = u.id
                  WHERE c.status = :status
                  ORDER BY c.start_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $this->status);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy lớp học theo giảng viên
     * @return PDOStatement
     */
    public function readByTeacher() {
        $query = "SELECT 
                    c.id,
                    c.course_name,
                    c.title,
                    c.description,
                    c.price,
                    c.teacher_id,
                    c.start_date,
                    c.end_date,
                    c.status,
                    c.thumbnail,
                    c.online_link,
                    c.created_at,
                    c.updated_at,
                    u.username as teacher_name,
                    u.full_name as teacher_full_name,
                    u.email as teacher_email,
                    u.avatar as teacher_avatar
                  FROM " . $this->table_name . " c
                  LEFT JOIN users u ON c.teacher_id = u.id
                  WHERE c.teacher_id = :teacher_id
                  ORDER BY c.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":teacher_id", $this->teacher_id);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy chi tiết 1 lớp học theo ID
     * @return bool - true nếu tìm thấy
     */
    public function readOne() {
        $query = "SELECT 
                    c.id,
                    c.course_name,
                    c.title,
                    c.description,
                    c.price,
                    c.teacher_id,
                    c.start_date,
                    c.end_date,
                    c.status,
                    c.thumbnail,
                    c.online_link,
                    c.created_at,
                    c.updated_at,
                    u.username as teacher_name,
                    u.email as teacher_email,
                    u.phone as teacher_phone
                  FROM " . $this->table_name . " c
                  LEFT JOIN users u ON c.teacher_id = u.id
                  WHERE c.id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->course_name = $row['course_name'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->price = $row['price'];
            $this->teacher_id = $row['teacher_id'];
            $this->start_date = $row['start_date'];
            $this->end_date = $row['end_date'];
            $this->status = $row['status'];
            $this->thumbnail = $row['thumbnail'];
            $this->online_link = isset($row['online_link']) ? $row['online_link'] : null;
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            // Thông tin giảng viên
            $this->teacher_name = $row['teacher_name'];
            $this->teacher_email = $row['teacher_email'];
            
            return true;
        }
        
        return false;
    }

    /**
     * Cập nhật thông tin lớp học
     * @return bool - true nếu thành công
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET course_name = :course_name,
                      title = :title,
                      description = :description,
                      price = :price,
                      start_date = :start_date,
                      end_date = :end_date,
                      status = :status,
                      thumbnail = :thumbnail,
                      online_link = :online_link
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch dữ liệu
        $this->course_name = htmlspecialchars(strip_tags($this->course_name));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->thumbnail = htmlspecialchars(strip_tags($this->thumbnail));
        $this->online_link = $this->online_link !== null ? htmlspecialchars(strip_tags($this->online_link)) : '';
        
        // Bind giá trị
        $stmt->bindParam(":course_name", $this->course_name);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":start_date", $this->start_date);
        $stmt->bindParam(":end_date", $this->end_date);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":thumbnail", $this->thumbnail);
        $stmt->bindParam(":online_link", $this->online_link);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Xóa lớp học
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
     * Đếm tổng số lớp học
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
     * Đếm số lớp học theo status
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
     * Đếm số lớp học của 1 giảng viên
     * @return int
     */
    public function countByTeacher() {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . "
                  WHERE teacher_id = :teacher_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":teacher_id", $this->teacher_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    /**
     * Tìm kiếm lớp học theo tên hoặc tiêu đề
     * @param string $keyword - Từ khóa tìm kiếm
     * @return PDOStatement
     */
    public function search($keyword) {
        $query = "SELECT 
                    c.id,
                    c.course_name,
                    c.title,
                    c.description,
                    c.price,
                    c.status,
                    c.start_date,
                    COALESCE(u.full_name, u.username) as teacher_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN users u ON c.teacher_id = u.id
                  WHERE c.course_name LIKE :keyword
                     OR c.title LIKE :keyword
                     OR c.description LIKE :keyword
                  ORDER BY c.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $keyword = "%{$keyword}%";
        $stmt->bindParam(":keyword", $keyword);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Tự động cập nhật trạng thái khóa học dựa trên ngày bắt đầu và kết thúc
     * Logic:
     * - Nếu ngày hiện tại < start_date: status = 'upcoming'
     * - Nếu start_date <= ngày hiện tại <= end_date: status = 'active'
     * - Nếu ngày hiện tại > end_date: status = 'closed'
     * @return int - Số lượng khóa học đã được cập nhật
     */
    public function updateStatusAutomatically() {
        // Kiểm tra database connection
        if (!$this->conn) {
            error_log("Cannot update course status: Database connection is not available");
            return 0;
        }
        
        $current_date = date('Y-m-d');
        $updated_count = 0;
        
        try {
            // Lấy tất cả khóa học có start_date và end_date
            $query = "SELECT id, start_date, end_date, status 
                      FROM " . $this->table_name . " 
                      WHERE start_date IS NOT NULL OR end_date IS NOT NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $course_id = $row['id'];
                $start_date = $row['start_date'];
                $end_date = $row['end_date'];
                $current_status = $row['status'];
                
                // Xác định trạng thái mới
                $new_status = null;
                
                if ($start_date && $end_date) {
                    // Có cả ngày bắt đầu và kết thúc
                    if ($current_date < $start_date) {
                        $new_status = 'upcoming';
                    } elseif ($current_date >= $start_date && $current_date <= $end_date) {
                        $new_status = 'active';
                    } else {
                        $new_status = 'closed';
                    }
                } elseif ($start_date) {
                    // Chỉ có ngày bắt đầu
                    if ($current_date < $start_date) {
                        $new_status = 'upcoming';
                    } else {
                        $new_status = 'active';
                    }
                } elseif ($end_date) {
                    // Chỉ có ngày kết thúc
                    if ($current_date <= $end_date) {
                        $new_status = 'active';
                    } else {
                        $new_status = 'closed';
                    }
                }
                
                // Chỉ cập nhật nếu trạng thái thay đổi
                if ($new_status && $new_status !== $current_status) {
                    $update_query = "UPDATE " . $this->table_name . " 
                                     SET status = :status 
                                     WHERE id = :id";
                    $update_stmt = $this->conn->prepare($update_query);
                    $update_stmt->bindParam(":status", $new_status);
                    $update_stmt->bindParam(":id", $course_id);
                    
                    if ($update_stmt->execute()) {
                        $updated_count++;
                    }
                }
            }
            
            return $updated_count;
        } catch (PDOException $e) {
            error_log("Error updating course status automatically: " . $e->getMessage());
            return 0;
        } catch (Exception $e) {
            error_log("Error updating course status automatically (general): " . $e->getMessage());
            return 0;
        }
    }
}
?>