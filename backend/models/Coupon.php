<?php
/**
 * Mục đích: Model xử lý các thao tác liên quan đến Coupon (Mã giảm giá)
 */

class Coupon {
    // Kết nối database
    private $conn;
    private $table_name = "coupons";

    // Thuộc tính của Coupon
    public $id;
    public $code;
    public $discount_percent;
    public $description;
    public $valid_from;
    public $valid_until;
    public $max_uses;
    public $used_count;
    public $status;
    public $created_at;
    public $updated_at;

    /**
     * Constructor - Nhận kết nối database
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Tạo mã giảm giá mới
     * @return bool - true nếu thành công
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET code = :code,
                    discount_percent = :discount_percent,
                    description = :description,
                    valid_from = :valid_from,
                    valid_until = :valid_until,
                    max_uses = :max_uses,
                    used_count = 0,
                    status = :status";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch dữ liệu
        $this->code = htmlspecialchars(strip_tags($this->code));
        $this->description = $this->description !== null ? htmlspecialchars(strip_tags($this->description)) : null;
        $this->status = $this->status !== null ? htmlspecialchars(strip_tags($this->status)) : 'active';
        $this->max_uses = $this->max_uses !== null ? intval($this->max_uses) : null;
        
        // Bind giá trị
        $stmt->bindParam(":code", $this->code);
        $stmt->bindParam(":discount_percent", $this->discount_percent);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":valid_from", $this->valid_from);
        $stmt->bindParam(":valid_until", $this->valid_until);
        $stmt->bindParam(":max_uses", $this->max_uses);
        $stmt->bindParam(":status", $this->status);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    /**
     * Lấy thông tin 1 coupon theo ID
     * @return bool - true nếu tìm thấy
     */
    public function readOne() {
        $query = "SELECT id, code, discount_percent, description, valid_from, valid_until, 
                         max_uses, used_count, status, created_at, updated_at
                  FROM " . $this->table_name . "
                  WHERE id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->code = $row['code'];
            $this->discount_percent = $row['discount_percent'];
            $this->description = $row['description'];
            $this->valid_from = $row['valid_from'];
            $this->valid_until = $row['valid_until'];
            $this->max_uses = $row['max_uses'];
            $this->used_count = $row['used_count'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }

    /**
     * Lấy coupon theo mã code
     * @return bool - true nếu tìm thấy
     */
    public function readByCode() {
        $query = "SELECT id, code, discount_percent, description, valid_from, valid_until, 
                         max_uses, used_count, status, created_at, updated_at
                  FROM " . $this->table_name . "
                  WHERE code = :code
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":code", $this->code);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id = $row['id'];
            $this->code = $row['code'];
            $this->discount_percent = $row['discount_percent'];
            $this->description = $row['description'];
            $this->valid_from = $row['valid_from'];
            $this->valid_until = $row['valid_until'];
            $this->max_uses = $row['max_uses'];
            $this->used_count = $row['used_count'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }

    /**
     * Lấy tất cả coupons
     * @return PDOStatement - Kết quả query
     */
    public function readAll() {
        $query = "SELECT id, code, discount_percent, description, valid_from, valid_until, 
                         max_uses, used_count, status, created_at, updated_at
                  FROM " . $this->table_name . "
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Kiểm tra mã code đã tồn tại chưa
     * @return bool - true nếu đã tồn tại
     */
    public function codeExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE code = :code LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":code", $this->code);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            return true;
        }
        
        return false;
    }

    /**
     * Cập nhật thông tin coupon
     * @return bool - true nếu thành công
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET code = :code,
                      discount_percent = :discount_percent,
                      description = :description,
                      valid_from = :valid_from,
                      valid_until = :valid_until,
                      max_uses = :max_uses,
                      status = :status
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch dữ liệu
        $this->code = htmlspecialchars(strip_tags($this->code));
        $this->description = $this->description !== null ? htmlspecialchars(strip_tags($this->description)) : null;
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->max_uses = $this->max_uses !== null ? intval($this->max_uses) : null;
        
        // Bind giá trị
        $stmt->bindParam(":code", $this->code);
        $stmt->bindParam(":discount_percent", $this->discount_percent);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":valid_from", $this->valid_from);
        $stmt->bindParam(":valid_until", $this->valid_until);
        $stmt->bindParam(":max_uses", $this->max_uses);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Xóa coupon
     * @return bool - true nếu thành công
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Tự động cập nhật trạng thái coupon dựa trên ngày hết hạn
     * @return int - Số lượng coupon đã được cập nhật
     */
    public function updateStatusAutomatically() {
        $current_date = date('Y-m-d');
        $updated_count = 0;
        
        try {
            // Cập nhật các coupon đã hết hạn
            $query = "UPDATE " . $this->table_name . " 
                     SET status = 'expired' 
                     WHERE status = 'active' 
                     AND valid_until < :current_date";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":current_date", $current_date);
            $stmt->execute();
            $updated_count = $stmt->rowCount();
            
            return $updated_count;
        } catch (PDOException $e) {
            error_log("Error updating coupon status automatically: " . $e->getMessage());
            return 0;
        }
    }
}
?>

