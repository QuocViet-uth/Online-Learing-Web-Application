<?php
/**
 * Mục đích: Model xử lý các thao tác liên quan đến Lesson (Bài giảng)
 */

class Lesson {
    // Kết nối database
    private $conn;
    private $table_name = "lessons";

    public $id;
    public $course_id;
    public $title;
    public $content;
    public $video_url;
    public $attachment_file;
    public $order_number;
    public $duration;
    public $created_at;
    
    // Thông tin bổ sung (từ JOIN)
    public $course_name;

    /**
     * Constructor
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Tạo bài giảng mới
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET course_id = :course_id,
                    title = :title,
                    content = :content,
                    video_url = :video_url,
                    attachment_file = :attachment_file,
                    order_number = :order_number,
                    duration = :duration";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch dữ liệu
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->content = htmlspecialchars(strip_tags($this->content));
        $this->video_url = htmlspecialchars(strip_tags($this->video_url));
        $this->attachment_file = isset($this->attachment_file) ? htmlspecialchars(strip_tags($this->attachment_file)) : null;
        
        // Bind giá trị
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":video_url", $this->video_url);
        $stmt->bindParam(":attachment_file", $this->attachment_file);
        $stmt->bindParam(":order_number", $this->order_number);
        $stmt->bindParam(":duration", $this->duration);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    /**
     * Lấy tất cả bài giảng của 1 lớp học
     * @return PDOStatement
     */
    public function readByCourse() {
        $query = "SELECT 
                    l.id,
                    l.course_id,
                    l.title,
                    l.content,
                    l.video_url,
                    l.attachment_file,
                    l.order_number,
                    l.duration,
                    l.created_at,
                    c.course_name
                  FROM " . $this->table_name . " l
                  LEFT JOIN courses c ON l.course_id = c.id
                  WHERE l.course_id = :course_id
                  ORDER BY l.order_number ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy chi tiết 1 bài giảng
     * @return bool
     */
    public function readOne() {
        $query = "SELECT 
                    l.id,
                    l.course_id,
                    l.title,
                    l.content,
                    l.video_url,
                    l.attachment_file,
                    l.order_number,
                    l.duration,
                    l.created_at,
                    c.course_name,
                    c.title as course_title
                  FROM " . $this->table_name . " l
                  LEFT JOIN courses c ON l.course_id = c.id
                  WHERE l.id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->course_id = $row['course_id'];
            $this->title = $row['title'];
            $this->content = $row['content'];
            $this->video_url = $row['video_url'];
            $this->order_number = $row['order_number'];
            $this->duration = $row['duration'];
            $this->created_at = $row['created_at'];
            $this->course_name = $row['course_name'];
            
            return true;
        }
        
        return false;
    }

    /**
     * Cập nhật bài giảng
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET title = :title,
                      content = :content,
                      video_url = :video_url,
                      attachment_file = :attachment_file,
                      order_number = :order_number,
                      duration = :duration
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch dữ liệu
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->content = htmlspecialchars(strip_tags($this->content));
        $this->video_url = htmlspecialchars(strip_tags($this->video_url));
        $this->attachment_file = isset($this->attachment_file) && !empty($this->attachment_file) 
            ? htmlspecialchars(strip_tags($this->attachment_file)) 
            : null;
        
        // Bind giá trị
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":video_url", $this->video_url);
        $stmt->bindParam(":attachment_file", $this->attachment_file);
        $stmt->bindParam(":order_number", $this->order_number);
        $stmt->bindParam(":duration", $this->duration);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Xóa bài giảng
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
     * Đếm số bài giảng của 1 khóa học
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
     * Lấy tổng thời lượng của 1 khóa học (phút)
     * @return int
     */
    public function getTotalDuration() {
        $query = "SELECT SUM(duration) as total_duration 
                  FROM " . $this->table_name . "
                  WHERE course_id = :course_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_duration'] ? $row['total_duration'] : 0;
    }
}
?>