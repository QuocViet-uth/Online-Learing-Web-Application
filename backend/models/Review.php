<?php
/**
 * File: models/Review.php
 * Mục đích: Model xử lý reviews (đánh giá khóa học)
 */

class Review {
    private $conn;
    private $table_name = "reviews";
    
    public $id;
    public $course_id;
    public $student_id;
    public $rating;
    public $comment;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Tạo đánh giá mới
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET course_id = :course_id,
                    student_id = :student_id,
                    rating = :rating,
                    comment = :comment,
                    created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->course_id = intval($this->course_id);
        $this->student_id = intval($this->student_id);
        $this->rating = intval($this->rating);
        $this->comment = isset($this->comment) ? trim($this->comment) : null;
        
        // Validate rating
        if ($this->rating < 1 || $this->rating > 5) {
            return false;
        }
        
        $stmt->bindParam(":course_id", $this->course_id, PDO::PARAM_INT);
        $stmt->bindParam(":student_id", $this->student_id, PDO::PARAM_INT);
        $stmt->bindParam(":rating", $this->rating, PDO::PARAM_INT);
        $stmt->bindParam(":comment", $this->comment);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Cập nhật đánh giá
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET rating = :rating,
                      comment = :comment,
                      updated_at = NOW()
                  WHERE id = :id 
                  AND student_id = :student_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->id = intval($this->id);
        $this->student_id = intval($this->student_id);
        $this->rating = intval($this->rating);
        $this->comment = isset($this->comment) ? trim($this->comment) : null;
        
        // Validate rating
        if ($this->rating < 1 || $this->rating > 5) {
            return false;
        }
        
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":student_id", $this->student_id, PDO::PARAM_INT);
        $stmt->bindParam(":rating", $this->rating, PDO::PARAM_INT);
        $stmt->bindParam(":comment", $this->comment);
        
        return $stmt->execute();
    }
    
    /**
     * Xóa đánh giá
     * @return bool
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = :id 
                  AND student_id = :student_id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->id = intval($this->id);
        $this->student_id = intval($this->student_id);
        
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        $stmt->bindParam(":student_id", $this->student_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Lấy đánh giá theo ID
     * @return bool
     */
    public function readOne() {
        $query = "SELECT r.*, 
                         u.username, u.avatar
                  FROM " . $this->table_name . " r
                  INNER JOIN users u ON r.student_id = u.id
                  WHERE r.id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->id = intval($row['id']);
            $this->course_id = intval($row['course_id']);
            $this->student_id = intval($row['student_id']);
            $this->rating = intval($row['rating']);
            $this->comment = $row['comment'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Lấy đánh giá của student cho course
     * @return PDOStatement
     */
    public function getByStudentAndCourse() {
        $query = "SELECT r.*, 
                         u.username, u.full_name, u.avatar
                  FROM " . $this->table_name . " r
                  INNER JOIN users u ON r.student_id = u.id
                  WHERE r.course_id = :course_id 
                  AND r.student_id = :student_id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":course_id", $this->course_id, PDO::PARAM_INT);
        $stmt->bindParam(":student_id", $this->student_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Lấy tất cả đánh giá của một course
     * @param int $limit Số lượng tối đa
     * @param int $offset Vị trí bắt đầu
     * @return PDOStatement
     */
    public function getByCourse($limit = 50, $offset = 0) {
        $query = "SELECT r.*, 
                         u.username, u.full_name, u.avatar
                  FROM " . $this->table_name . " r
                  INNER JOIN users u ON r.student_id = u.id
                  WHERE r.course_id = :course_id
                  ORDER BY r.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":course_id", $this->course_id, PDO::PARAM_INT);
        $stmt->bindValue(":limit", intval($limit), PDO::PARAM_INT);
        $stmt->bindValue(":offset", intval($offset), PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Đếm số lượng đánh giá của một course
     * @return int
     */
    public function countByCourse() {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . "
                  WHERE course_id = :course_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":course_id", $this->course_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? intval($row['total']) : 0;
    }
    
    /**
     * Tính điểm trung bình của một course
     * @return float|null
     */
    public function getAverageRating() {
        $query = "SELECT AVG(rating) as average_rating, COUNT(*) as total_reviews
                  FROM " . $this->table_name . "
                  WHERE course_id = :course_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":course_id", $this->course_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && $row['total_reviews'] > 0) {
            return array(
                'average_rating' => round(floatval($row['average_rating']), 1),
                'total_reviews' => intval($row['total_reviews'])
            );
        }
        
        return array(
            'average_rating' => 0,
            'total_reviews' => 0
        );
    }
    
    /**
     * Phân phối rating (số lượng mỗi sao)
     * @return array
     */
    public function getRatingDistribution() {
        $query = "SELECT rating, COUNT(*) as count
                  FROM " . $this->table_name . "
                  WHERE course_id = :course_id
                  GROUP BY rating
                  ORDER BY rating DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":course_id", $this->course_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $distribution = array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rating = intval($row['rating']);
            $distribution[$rating] = intval($row['count']);
        }
        
        return $distribution;
    }
}
?>

