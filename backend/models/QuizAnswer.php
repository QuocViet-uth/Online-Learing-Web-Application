<?php
/**
 * Model xử lý các thao tác liên quan đến QuizAnswer (Đáp án quiz)
 */

class QuizAnswer {
    private $conn;
    private $table_name = "quiz_answers";

    public $id;
    public $question_id;
    public $answer_text;
    public $is_correct;
    public $order_number;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Tạo đáp án mới
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET question_id = :question_id,
                    answer_text = :answer_text,
                    is_correct = :is_correct,
                    order_number = :order_number";
        
        $stmt = $this->conn->prepare($query);
        
        $this->answer_text = htmlspecialchars(strip_tags($this->answer_text));
        $this->is_correct = $this->is_correct ? 1 : 0;
        
        $stmt->bindParam(":question_id", $this->question_id);
        $stmt->bindParam(":answer_text", $this->answer_text);
        $stmt->bindParam(":is_correct", $this->is_correct, PDO::PARAM_INT);
        $stmt->bindParam(":order_number", $this->order_number);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    /**
     * Lấy tất cả đáp án của một câu hỏi
     * @return PDOStatement
     */
    public function readByQuestion() {
        $query = "SELECT 
                    id,
                    question_id,
                    answer_text,
                    is_correct,
                    order_number,
                    created_at,
                    updated_at
                  FROM " . $this->table_name . "
                  WHERE question_id = :question_id
                  ORDER BY order_number ASC, id ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":question_id", $this->question_id);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy chi tiết 1 đáp án
     * @return bool
     */
    public function readOne() {
        $query = "SELECT 
                    id,
                    question_id,
                    answer_text,
                    is_correct,
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
            $this->question_id = $row['question_id'];
            $this->answer_text = $row['answer_text'];
            $this->is_correct = (bool)$row['is_correct'];
            $this->order_number = $row['order_number'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }

    /**
     * Cập nhật đáp án
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET answer_text = :answer_text,
                      is_correct = :is_correct,
                      order_number = :order_number
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->answer_text = htmlspecialchars(strip_tags($this->answer_text));
        $this->is_correct = $this->is_correct ? 1 : 0;
        
        $stmt->bindParam(":answer_text", $this->answer_text);
        $stmt->bindParam(":is_correct", $this->is_correct, PDO::PARAM_INT);
        $stmt->bindParam(":order_number", $this->order_number);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    /**
     * Xóa đáp án
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
     * Xóa tất cả đáp án của một câu hỏi
     * @return bool
     */
    public function deleteByQuestion() {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE question_id = :question_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":question_id", $this->question_id);
        
        return $stmt->execute();
    }
}
?>

