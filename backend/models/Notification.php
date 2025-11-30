<?php
/**
 * File: models/Notification.php
 * Mục đích: Model xử lý notifications (thông báo)
 */

class Notification {
    private $conn;
    private $table_name = "notifications";
    
    // Properties
    public $id;
    public $sender_id;
    public $user_id; // Changed from receiver_id to match database schema
    public $receiver_id; // For backward compatibility - maps to user_id
    public $course_id;
    public $title;
    public $content;
    public $is_read;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Tạo notification mới
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id = :user_id,
                    title = :title,
                    content = :content,
                    type = :type,
                    is_read = :is_read,
                    related_id = :related_id,
                    related_type = :related_type,
                    created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize - map receiver_id to user_id for compatibility
        if (isset($this->receiver_id) && $this->receiver_id > 0) {
            $this->user_id = intval($this->receiver_id);
        } elseif (isset($this->user_id) && $this->user_id > 0) {
            $this->user_id = intval($this->user_id);
        } else {
            $this->user_id = null;
        }
        $this->title = isset($this->title) ? trim($this->title) : '';
        $this->content = isset($this->content) ? trim($this->content) : '';
        $this->is_read = isset($this->is_read) ? (bool)$this->is_read : false;
        $type = isset($this->course_id) && $this->course_id > 0 ? 'course' : 'info';
        $related_id = isset($this->course_id) && $this->course_id > 0 ? intval($this->course_id) : null;
        $related_type = isset($this->course_id) && $this->course_id > 0 ? 'course' : null;
        
        // Bind values
        if ($this->user_id) {
            $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":user_id", null, PDO::PARAM_NULL);
        }
        
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":type", $type);
        $stmt->bindParam(":is_read", $this->is_read, PDO::PARAM_BOOL);
        
        if ($related_id) {
            $stmt->bindParam(":related_id", $related_id, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":related_id", null, PDO::PARAM_NULL);
        }
        
        if ($related_type) {
            $stmt->bindParam(":related_type", $related_type);
        } else {
            $stmt->bindValue(":related_type", null, PDO::PARAM_NULL);
        }
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Lấy notifications của một user
     * @param int $receiver_id
     * @param int $limit
     * @param int $offset
     * @return PDOStatement
     */
    public function getByReceiver($receiver_id, $limit = 50, $offset = 0) {
        $query = "SELECT 
                    n.id,
                    n.user_id,
                    n.title,
                    n.content,
                    n.type,
                    n.is_read,
                    n.related_id,
                    n.related_type,
                    n.created_at,
                    c.course_name
                  FROM " . $this->table_name . " n
                  LEFT JOIN courses c ON n.related_id = c.id AND n.related_type = 'course'
                  WHERE n.user_id = :user_id
                  ORDER BY n.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $receiver_id, PDO::PARAM_INT);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Đếm số notifications chưa đọc của một user
     * @param int $receiver_id
     * @return int
     */
    public function countUnread($receiver_id) {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $receiver_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? intval($row['total']) : 0;
    }
    
    /**
     * Đánh dấu notification là đã đọc
     * @param int $notification_id
     * @param int $receiver_id (để đảm bảo security)
     * @return bool
     */
    public function markAsRead($notification_id, $receiver_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $notification_id, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $receiver_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Đánh dấu tất cả notifications của user là đã đọc
     * @param int $receiver_id
     * @return bool
     */
    public function markAllAsRead($receiver_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $receiver_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Tạo notifications cho group chat (batch insert để tối ưu)
     * @param int $sender_id
     * @param int $course_id
     * @param string $title
     * @param string $content
     * @param array $receiver_ids (danh sách receiver_id cần gửi notification)
     * @return int Số lượng notifications đã tạo
     */
    public function createBatchForGroupChat($sender_id, $course_id, $title, $content, $receiver_ids) {
        try {
            if (empty($receiver_ids) || !is_array($receiver_ids)) {
                return 0;
            }
            
            // Loại bỏ sender_id khỏi danh sách
            $receiver_ids = array_filter($receiver_ids, function($id) use ($sender_id) {
                return intval($id) > 0 && intval($id) != $sender_id;
            });
            
            if (empty($receiver_ids)) {
                return 0;
            }
            
            // Batch insert để tối ưu performance - chia nhỏ để tránh lỗi SQL quá dài
            $batch_size = 100; // Insert 100 records mỗi lần
            $total_inserted = 0;
            
            for ($i = 0; $i < count($receiver_ids); $i += $batch_size) {
                $batch = array_slice($receiver_ids, $i, $batch_size);
                
                $query = "INSERT INTO " . $this->table_name . " 
                          (user_id, title, content, type, is_read, related_id, related_type, created_at)
                          VALUES ";
                
                $values = array();
                $params = array();
                $param_index = 0;
                
                foreach ($batch as $receiver_id) {
                    $receiver_id = intval($receiver_id);
                    $values[] = "(:user_id_$param_index, :title, :content, 'course', 0, :course_id, 'course', NOW())";
                    $params[":user_id_$param_index"] = $receiver_id;
                    $param_index++;
                }
                
                $query .= implode(", ", $values);
                
                $stmt = $this->conn->prepare($query);
                if (!$stmt) {
                    error_log("Failed to prepare statement in createBatchForGroupChat");
                    continue; // Skip this batch and continue
                }
                
                $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
                $stmt->bindParam(":title", $title);
                $stmt->bindParam(":content", $content);
                
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                }
                
                if ($stmt->execute()) {
                    $total_inserted += count($batch);
                } else {
                    $error_info = $stmt->errorInfo();
                    $error_msg = "Failed to execute batch insert: " . json_encode($error_info);
                    error_log($error_msg);
                }
            }
            
            return $total_inserted;
        } catch (Exception $e) {
            error_log("Exception in createBatchForGroupChat: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return 0; // Return 0 instead of throwing to not break the main flow
        }
    }
    
    /**
     * Tạo notifications cho tất cả students đã đăng ký course
     * @param int $sender_id (teacher_id)
     * @param int $course_id
     * @param string $title
     * @param string $content
     * @return int Số lượng notifications đã tạo
     */
    public function createForCourseStudents($sender_id, $course_id, $title, $content) {
        try {
            // Validate input
            if (empty($sender_id) || empty($course_id) || empty($title) || empty($content)) {
                error_log("Invalid parameters for createForCourseStudents: sender_id={$sender_id}, course_id={$course_id}");
                return 0;
            }
            
            // Lấy danh sách students đã đăng ký course (active)
            $query = "SELECT student_id FROM enrollments 
                      WHERE course_id = :course_id 
                      AND status = 'active'";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                error_log("Failed to prepare statement in createForCourseStudents");
                return 0;
            }
            
            $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                error_log("Failed to execute query in createForCourseStudents");
                return 0;
            }
            
            $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($enrollments)) {
                return 0;
            }
            
            $receiver_ids = array();
            foreach ($enrollments as $enrollment) {
                $student_id = intval($enrollment['student_id']);
                // Loại bỏ sender_id nếu có
                if ($student_id > 0 && $student_id != $sender_id) {
                    $receiver_ids[] = $student_id;
                }
            }
            
            if (empty($receiver_ids)) {
                return 0;
            }
            
            return $this->createBatchForGroupChat($sender_id, $course_id, $title, $content, $receiver_ids);
        } catch (Exception $e) {
            error_log("Exception in createForCourseStudents: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return 0; // Return 0 instead of throwing to not break the main flow
        }
    }
    
    /**
     * Tạo notification cho tất cả users (teacher và student)
     * @param int $sender_id (admin_id)
     * @param string $title
     * @param string $content
     * @return int Số lượng notifications đã tạo
     */
    public function createForAllUsers($sender_id, $title, $content) {
        // Lấy danh sách tất cả users (teacher và student)
        $query = "SELECT id FROM users 
                  WHERE role IN ('teacher', 'student') 
                  AND id != :sender_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":sender_id", $sender_id, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($users)) {
            return 0;
        }
        
        $receiver_ids = array();
        foreach ($users as $user) {
            $user_id = intval($user['id']);
            if ($user_id > 0) {
                $receiver_ids[] = $user_id;
            }
        }
        
        if (empty($receiver_ids)) {
            return 0;
        }
        
        // Batch insert - chia nhỏ để tránh lỗi SQL quá dài
        $batch_size = 100; // Insert 100 records mỗi lần
        $total_inserted = 0;
        
        for ($i = 0; $i < count($receiver_ids); $i += $batch_size) {
            $batch = array_slice($receiver_ids, $i, $batch_size);
            
            $query = "INSERT INTO " . $this->table_name . " 
                      (user_id, title, content, type, is_read, created_at)
                      VALUES ";
            
            $values = array();
            $params = array();
            $param_index = 0;
            
            foreach ($batch as $receiver_id) {
                $receiver_id = intval($receiver_id);
                $values[] = "(:user_id_$param_index, :title, :content, 'info', 0, NOW())";
                $params[":user_id_$param_index"] = $receiver_id;
                $param_index++;
            }
            
            $query .= implode(", ", $values);
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":content", $content);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            
            if ($stmt->execute()) {
                $total_inserted += count($batch);
            }
        }
        
        return $total_inserted;
    }
}
