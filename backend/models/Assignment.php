<?php
/**
 * Mục đích: Model xử lý các thao tác liên quan đến Assignment (Bài tập)
 */

class Assignment {
    // Kết nối database
    private $conn;
    private $table_name = "assignments";

    // Thuộc tính của Assignment
    public $id;
    public $course_id;
    public $title;
    public $description;
    public $type; // 'homework' or 'quiz'
    public $time_limit; // Thời gian làm bài quiz (phút), NULL = không giới hạn
    public $start_date;
    public $deadline;
    public $max_score;
    public $created_at;
    public $updated_at;
    
    // Thông tin bổ sung
    public $course_name;

    /**
     * Constructor
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Tạo bài tập mới
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET course_id = :course_id,
                    title = :title,
                    description = :description,
                    type = :type,
                    time_limit = :time_limit,
                    start_date = :start_date,
                    deadline = :deadline,
                    max_score = :max_score";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch dữ liệu
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        
        // Debug: Log giá trị type trước khi xử lý
        error_log("Assignment model - type before processing: " . (isset($this->type) ? $this->type : 'NOT SET'));
        
        $this->type = isset($this->type) && in_array($this->type, ['homework', 'quiz']) ? $this->type : 'homework';
        $this->time_limit = isset($this->time_limit) && $this->time_limit > 0 ? intval($this->time_limit) : null;
        $this->start_date = isset($this->start_date) && !empty($this->start_date) ? $this->start_date : null;
        
        // Debug: Log giá trị type sau khi xử lý và trước khi bind
        error_log("Assignment model - type after processing: " . $this->type);
        error_log("Assignment model - time_limit: " . ($this->time_limit ?? 'NULL'));
        
        // Bind giá trị
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        
        // Bind type - đảm bảo giá trị đúng
        $typeValue = in_array($this->type, ['homework', 'quiz'], true) ? $this->type : 'homework';
        error_log("Binding type value: " . $typeValue);
        $stmt->bindValue(":type", $typeValue, PDO::PARAM_STR);
        
        // Bind time_limit - xử lý NULL đúng cách
        if ($this->time_limit === null) {
            $stmt->bindValue(":time_limit", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":time_limit", $this->time_limit, PDO::PARAM_INT);
        }
        
        // Bind start_date - xử lý NULL đúng cách
        if ($this->start_date === null) {
            $stmt->bindValue(":start_date", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":start_date", $this->start_date);
        }
        
        $stmt->bindParam(":deadline", $this->deadline);
        $stmt->bindParam(":max_score", $this->max_score);
        
        try {
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                
                // Debug: Kiểm tra giá trị đã lưu vào database
                $checkQuery = "SELECT type, time_limit FROM " . $this->table_name . " WHERE id = :id";
                $checkStmt = $this->conn->prepare($checkQuery);
                $checkStmt->bindParam(":id", $this->id);
                $checkStmt->execute();
                $checkRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if ($checkRow) {
                    error_log("Assignment saved to DB - type: " . ($checkRow['type'] ?? 'NULL') . ", time_limit: " . ($checkRow['time_limit'] ?? 'NULL'));
                }
                
                return true;
            } else {
                $error = $stmt->errorInfo();
                error_log("Assignment create error: " . print_r($error, true));
                return false;
            }
        } catch (PDOException $e) {
            error_log("Assignment create PDOException: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy tất cả bài tập của 1 lớp học
     * @return PDOStatement
     */
    public function readByCourse() {
        $query = "SELECT 
                    a.id,
                    a.course_id,
                    a.title,
                    a.description,
                    a.type,
                    a.time_limit,
                    a.start_date,
                    a.deadline,
                    a.max_score,
                    a.created_at,
                    c.course_name
                  FROM " . $this->table_name . " a
                  LEFT JOIN courses c ON a.course_id = c.id
                  WHERE a.course_id = :course_id
                  ORDER BY a.deadline ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy chi tiết 1 bài tập
     * @return bool
     */
    public function readOne() {
        $query = "SELECT 
                    a.id,
                    a.course_id,
                    a.title,
                    a.description,
                    a.type,
                    a.time_limit,
                    a.start_date,
                    a.deadline,
                    a.max_score,
                    a.created_at,
                    a.updated_at,
                    c.course_name,
                    c.title as course_title
                  FROM " . $this->table_name . " a
                  LEFT JOIN courses c ON a.course_id = c.id
                  WHERE a.id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->course_id = $row['course_id'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->type = isset($row['type']) ? $row['type'] : 'homework';
            $this->time_limit = isset($row['time_limit']) ? intval($row['time_limit']) : null;
            $this->start_date = isset($row['start_date']) ? $row['start_date'] : null;
            $this->deadline = $row['deadline'];
            $this->max_score = $row['max_score'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            $this->course_name = $row['course_name'];
            
            return true;
        }
        
        return false;
    }

    /**
     * Cập nhật bài tập
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET title = :title,
                      description = :description,
                      type = :type,
                      time_limit = :time_limit,
                      start_date = :start_date,
                      deadline = :deadline,
                      max_score = :max_score
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch dữ liệu
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        
        // Debug: Log giá trị type trước khi xử lý
        error_log("Assignment update - type before processing: " . (isset($this->type) ? $this->type : 'NOT SET'));
        
        $this->type = isset($this->type) && in_array($this->type, ['homework', 'quiz'], true) ? $this->type : 'homework';
        $this->time_limit = isset($this->time_limit) && $this->time_limit > 0 ? intval($this->time_limit) : null;
        $this->start_date = isset($this->start_date) && !empty($this->start_date) ? $this->start_date : null;
        
        // Debug: Log giá trị type sau khi xử lý
        error_log("Assignment update - type after processing: " . $this->type);
        error_log("Assignment update - time_limit: " . ($this->time_limit ?? 'NULL'));
        
        // Bind giá trị
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        
        // Bind type - đảm bảo giá trị đúng
        $typeValue = in_array($this->type, ['homework', 'quiz'], true) ? $this->type : 'homework';
        error_log("Assignment update - Binding type value: " . $typeValue);
        $stmt->bindValue(":type", $typeValue, PDO::PARAM_STR);
        
        // Bind time_limit - xử lý NULL đúng cách
        if ($this->time_limit === null) {
            $stmt->bindValue(":time_limit", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":time_limit", $this->time_limit, PDO::PARAM_INT);
        }
        // Bind start_date - xử lý NULL đúng cách
        if ($this->start_date === null) {
            $stmt->bindValue(":start_date", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":start_date", $this->start_date);
        }
        
        $stmt->bindParam(":deadline", $this->deadline);
        $stmt->bindParam(":max_score", $this->max_score);
        $stmt->bindParam(":id", $this->id);
        
        try {
            if($stmt->execute()) {
                // Debug: Kiểm tra giá trị đã cập nhật vào database
                $checkQuery = "SELECT type, time_limit FROM " . $this->table_name . " WHERE id = :id";
                $checkStmt = $this->conn->prepare($checkQuery);
                $checkStmt->bindParam(":id", $this->id);
                $checkStmt->execute();
                $checkRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if ($checkRow) {
                    error_log("Assignment updated in DB - type: " . ($checkRow['type'] ?? 'NULL') . ", time_limit: " . ($checkRow['time_limit'] ?? 'NULL'));
                }
                
                return true;
            } else {
                $error = $stmt->errorInfo();
                error_log("Assignment update error: " . print_r($error, true));
                return false;
            }
        } catch (PDOException $e) {
            error_log("Assignment update PDOException: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa bài tập
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
     * Đếm số bài tập của 1 khóa học
     * @return int
     */
    public function countByCourse() {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . "
                  WHERE course_id = :course_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    /**
     * Lấy bài tập sắp hết hạn (trong 7 ngày tới)
     * @return PDOStatement
     */
    public function getUpcomingDeadlines() {
        $query = "SELECT 
                    a.id,
                    a.course_id,
                    a.title,
                    a.deadline,
                    c.course_name
                  FROM " . $this->table_name . " a
                  LEFT JOIN courses c ON a.course_id = c.id
                  WHERE a.deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
                  ORDER BY a.deadline ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
}
?>