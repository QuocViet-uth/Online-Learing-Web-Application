<?php
/**
 * Model xử lý các thao tác liên quan đến Chat (Tin nhắn)
 */

class Chat {
    // Kết nối database
    private $conn;
    private $table_name = "chats";

    public $id;
    public $sender_id;
    public $receiver_id;
    public $course_id;
    public $message; // Changed from content to match database schema
    public $content; // For backward compatibility - maps to message
    public $is_read;
    public $sent_at; // For backward compatibility - maps to created_at
    
    // Thông tin bổ sung (từ JOIN)
    public $sender_name;
    public $sender_avatar;
    public $receiver_name;
    public $receiver_avatar;
    public $course_name;

    /**
     * Constructor
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Gửi tin nhắn mới
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET sender_id = :sender_id,
                    receiver_id = :receiver_id,
                    course_id = :course_id,
                    message = :message,
                    is_read = FALSE";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch dữ liệu - map content to message for backward compatibility
        $message = isset($this->message) ? $this->message : (isset($this->content) ? $this->content : '');
        $message = htmlspecialchars(strip_tags($message));
        
        // Xử lý null values - cho phép receiver_id NULL cho group chat
        $receiver_id = (isset($this->receiver_id) && $this->receiver_id !== null && $this->receiver_id !== '' && $this->receiver_id > 0) ? intval($this->receiver_id) : null;
        $course_id = (isset($this->course_id) && $this->course_id !== null && $this->course_id !== '' && $this->course_id > 0) ? intval($this->course_id) : null;
        
        // Bind giá trị
        $stmt->bindParam(":sender_id", $this->sender_id, PDO::PARAM_INT);
        if ($receiver_id !== null) {
            $stmt->bindParam(":receiver_id", $receiver_id, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":receiver_id", null, PDO::PARAM_NULL);
        }
        if ($course_id !== null) {
            $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":course_id", null, PDO::PARAM_NULL);
        }
        $stmt->bindParam(":message", $message);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    /**
     * Lấy tin nhắn giữa 2 người (trong hoặc ngoài context của course)
     * @return PDOStatement
     */
    public function getConversation() {
        $user1_id = $this->sender_id;
        $user2_id = $this->receiver_id;
        $course_id = $this->course_id;
        
        $query = "SELECT 
                    c.id,
                    c.sender_id,
                    c.receiver_id,
                    c.course_id,
                    c.message as content,
                    c.is_read,
                    c.created_at as sent_at,
                    s.username as sender_name,
                    s.avatar as sender_avatar,
                    s.role as sender_role,
                    r.username as receiver_name,
                    r.avatar as receiver_avatar,
                    co.course_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN users s ON c.sender_id = s.id
                  LEFT JOIN users r ON c.receiver_id = r.id
                  LEFT JOIN courses co ON c.course_id = co.id
                  WHERE ((c.sender_id = ? AND c.receiver_id = ?) 
                     OR (c.sender_id = ? AND c.receiver_id = ?))";
        
        if ($course_id) {
            $query .= " AND c.course_id = ?";
        } else {
            $query .= " AND c.course_id IS NULL";
        }
        
        $query .= " ORDER BY c.created_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user1_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $user2_id, PDO::PARAM_INT);
        $stmt->bindParam(3, $user2_id, PDO::PARAM_INT);
        $stmt->bindParam(4, $user1_id, PDO::PARAM_INT);
        if ($course_id) {
            $stmt->bindParam(5, $course_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy tất cả cuộc trò chuyện của 1 user (danh sách người đã chat)
     * @return PDOStatement
     */
    public function getConversations() {
        // Lấy danh sách courses có chat (group chat - receiver_id IS NULL)
        $user_id = $this->sender_id;
        $query = "SELECT 
                    c.course_id,
                    co.course_name,
                    co.title as course_title,
                    co.thumbnail as course_thumbnail,
                    MAX(c.created_at) as last_message_time,
                    (SELECT message FROM " . $this->table_name . " c2
                     WHERE c2.course_id = c.course_id
                     AND c2.receiver_id IS NULL
                     ORDER BY c2.created_at DESC LIMIT 1) as last_message,
                    (SELECT COUNT(*) FROM " . $this->table_name . " c3
                     WHERE c3.course_id = c.course_id
                     AND c3.receiver_id IS NULL
                     AND c3.sender_id != ?
                     AND c3.is_read = FALSE) as unread_count
                  FROM " . $this->table_name . " c
                  LEFT JOIN courses co ON c.course_id = co.id
                  WHERE (c.sender_id = ? OR c.receiver_id = ?)
                    AND c.course_id IS NOT NULL
                    AND c.receiver_id IS NULL
                  GROUP BY c.course_id, co.course_name, co.title, co.thumbnail
                  ORDER BY last_message_time DESC";
        
        $stmt = $this->conn->prepare($query);
        // Bind 3 lần cho 3 chỗ dùng user_id
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(3, $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy tin nhắn trong context của 1 course
     * @return PDOStatement
     */
    public function getByCourse() {
        $query = "SELECT 
                    c.id,
                    c.sender_id,
                    c.receiver_id,
                    c.course_id,
                    c.message as content,
                    c.is_read,
                    c.created_at as sent_at,
                    s.username as sender_name,
                    s.avatar as sender_avatar,
                    s.role as sender_role,
                    r.username as receiver_name,
                    r.avatar as receiver_avatar
                  FROM " . $this->table_name . " c
                  LEFT JOIN users s ON c.sender_id = s.id
                  LEFT JOIN users r ON c.receiver_id = r.id
                  WHERE c.course_id = :course_id
                    AND c.receiver_id IS NULL
                  ORDER BY c.created_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Đánh dấu tin nhắn đã đọc
     * @return bool
     */
    public function markAsRead() {
        $query = "UPDATE " . $this->table_name . "
                  SET is_read = TRUE
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    /**
     * Đánh dấu tất cả tin nhắn từ 1 người đã đọc
     * @return bool
     */
    public function markConversationAsRead() {
        $query = "UPDATE " . $this->table_name . "
                  SET is_read = TRUE
                  WHERE receiver_id = :receiver_id
                  AND sender_id = :sender_id";
        
        if ($this->course_id) {
            $query .= " AND course_id = :course_id";
        } else {
            $query .= " AND course_id IS NULL";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":receiver_id", $this->receiver_id);
        $stmt->bindParam(":sender_id", $this->sender_id);
        if ($this->course_id) {
            $stmt->bindParam(":course_id", $this->course_id);
        }
        
        return $stmt->execute();
    }

    /**
     * Đếm số tin nhắn chưa đọc
     * @return int
     */
    public function countUnread() {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . "
                  WHERE receiver_id = :receiver_id
                  AND is_read = FALSE";
        
        if ($this->course_id) {
            $query .= " AND course_id = :course_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":receiver_id", $this->receiver_id);
        if ($this->course_id) {
            $stmt->bindParam(":course_id", $this->course_id);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ? $row['total'] : 0;
    }
}

