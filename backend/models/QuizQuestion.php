<?php
/**
 * Model xử lý các thao tác liên quan đến QuizQuestion (Câu hỏi quiz)
 */

class QuizQuestion {
    private $conn;
    private $table_name = "quiz_questions";

    public $id;
    public $assignment_id;
    public $question_text;
    public $order_number;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Tạo câu hỏi mới
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET assignment_id = :assignment_id,
                    question_text = :question_text,
                    order_number = :order_number";
        
        $stmt = $this->conn->prepare($query);
        
        $this->question_text = htmlspecialchars(strip_tags($this->question_text));
        
        $stmt->bindParam(":assignment_id", $this->assignment_id);
        $stmt->bindParam(":question_text", $this->question_text);
        $stmt->bindParam(":order_number", $this->order_number);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    /**
     * Lấy tất cả câu hỏi của một assignment
     * @return PDOStatement
     */
    public function readByAssignment() {
        $query = "SELECT 
                    id,
                    assignment_id,
                    question_text,
                    order_number,
                    created_at,
                    updated_at
                  FROM " . $this->table_name . "
                  WHERE assignment_id = :assignment_id
                  ORDER BY order_number ASC, id ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":assignment_id", $this->assignment_id);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy chi tiết 1 câu hỏi
     * @return bool
     */
    public function readOne() {
        $query = "SELECT 
                    id,
                    assignment_id,
                    question_text,
                    order_number,
                    created_at,
                    updated_at
                  FROM " . $this->table_name . "
                  WHERE id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->assignment_id = $row['assignment_id'];
            $this->question_text = $row['question_text'];
            $this->order_number = $row['order_number'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }

    /**
     * Cập nhật câu hỏi
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET question_text = :question_text,
                      order_number = :order_number
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->question_text = htmlspecialchars(strip_tags($this->question_text));
        
        $stmt->bindParam(":question_text", $this->question_text);
        $stmt->bindParam(":order_number", $this->order_number);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    /**
     * Xóa câu hỏi
     * @return bool
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    /**
     * Xóa tất cả câu hỏi của một assignment
     * @return bool
     */
    public function deleteByAssignment() {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE assignment_id = :assignment_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":assignment_id", $this->assignment_id);
        
        return $stmt->execute();
    }
}
?>

