<?php
/**
 * Mục đích: Model xử lý các thao tác liên quan đến PaymentQRCode (Mã QR thanh toán)
 */

class PaymentQRCode {
    // Kết nối database
    private $conn;
    private $table_name = "payment_qr_codes";

    // Thuộc tính của PaymentQRCode
    public $id;
    public $payment_gateway;
    public $qr_code_image;
    public $account_number;
    public $account_name;
    public $bank_name;
    public $phone_number;
    public $description;
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
     * Tạo QR code mới
     * @return bool - true nếu thành công
     */
    public function create() {
        // Kiểm tra connection
        if (!$this->conn) {
            throw new Exception("Database connection is not available");
        }
        
        $query = "INSERT INTO " . $this->table_name . "
                SET payment_gateway = :payment_gateway,
                    qr_code_image = :qr_code_image,
                    account_number = :account_number,
                    account_name = :account_name,
                    bank_name = :bank_name,
                    phone_number = :phone_number,
                    description = :description,
                    status = :status";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                $error = $this->conn->errorInfo();
                throw new Exception("Failed to prepare statement: " . ($error[2] ?? "Unknown error"));
            }
        
            // Làm sạch dữ liệu
            $this->payment_gateway = htmlspecialchars(strip_tags($this->payment_gateway));
            $this->qr_code_image = htmlspecialchars(strip_tags($this->qr_code_image));
            $this->account_number = isset($this->account_number) ? htmlspecialchars(strip_tags($this->account_number)) : null;
            $this->account_name = isset($this->account_name) ? htmlspecialchars(strip_tags($this->account_name)) : null;
            $this->bank_name = isset($this->bank_name) ? htmlspecialchars(strip_tags($this->bank_name)) : null;
            $this->phone_number = isset($this->phone_number) ? htmlspecialchars(strip_tags($this->phone_number)) : null;
            $this->description = isset($this->description) ? htmlspecialchars(strip_tags($this->description)) : null;
            $this->status = htmlspecialchars(strip_tags($this->status));
            
            // Bind giá trị
            $stmt->bindParam(":payment_gateway", $this->payment_gateway);
            $stmt->bindParam(":qr_code_image", $this->qr_code_image);
            $stmt->bindParam(":account_number", $this->account_number);
            $stmt->bindParam(":account_name", $this->account_name);
            $stmt->bindParam(":bank_name", $this->bank_name);
            $stmt->bindParam(":phone_number", $this->phone_number);
            $stmt->bindParam(":description", $this->description);
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
     * Lấy tất cả QR codes
     * @return PDOStatement - Kết quả query
     */
    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . "
                  ORDER BY payment_gateway ASC, created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy QR code theo payment_gateway và status active
     * @return bool - true nếu tìm thấy
     */
    public function readByGateway() {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE payment_gateway = :payment_gateway
                    AND status = 'active'
                  ORDER BY created_at DESC
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":payment_gateway", $this->payment_gateway);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id = $row['id'];
            $this->payment_gateway = $row['payment_gateway'];
            $this->qr_code_image = $row['qr_code_image'];
            $this->account_number = $row['account_number'];
            $this->account_name = $row['account_name'];
            $this->bank_name = $row['bank_name'];
            $this->phone_number = $row['phone_number'];
            $this->description = $row['description'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }

    /**
     * Lấy chi tiết 1 QR code theo ID
     * @return bool - true nếu tìm thấy
     */
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->payment_gateway = $row['payment_gateway'];
            $this->qr_code_image = $row['qr_code_image'];
            $this->account_number = $row['account_number'];
            $this->account_name = $row['account_name'];
            $this->bank_name = $row['bank_name'];
            $this->phone_number = $row['phone_number'];
            $this->description = $row['description'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }

    /**
     * Cập nhật QR code
     * @return bool - true nếu thành công
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET payment_gateway = :payment_gateway,
                      qr_code_image = :qr_code_image,
                      account_number = :account_number,
                      account_name = :account_name,
                      bank_name = :bank_name,
                      phone_number = :phone_number,
                      description = :description,
                      status = :status
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch dữ liệu
        $this->payment_gateway = htmlspecialchars(strip_tags($this->payment_gateway));
        $this->qr_code_image = htmlspecialchars(strip_tags($this->qr_code_image));
        $this->account_number = isset($this->account_number) ? htmlspecialchars(strip_tags($this->account_number)) : null;
        $this->account_name = isset($this->account_name) ? htmlspecialchars(strip_tags($this->account_name)) : null;
        $this->bank_name = isset($this->bank_name) ? htmlspecialchars(strip_tags($this->bank_name)) : null;
        $this->phone_number = isset($this->phone_number) ? htmlspecialchars(strip_tags($this->phone_number)) : null;
        $this->description = isset($this->description) ? htmlspecialchars(strip_tags($this->description)) : null;
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Bind giá trị
        $stmt->bindParam(":payment_gateway", $this->payment_gateway);
        $stmt->bindParam(":qr_code_image", $this->qr_code_image);
        $stmt->bindParam(":account_number", $this->account_number);
        $stmt->bindParam(":account_name", $this->account_name);
        $stmt->bindParam(":bank_name", $this->bank_name);
        $stmt->bindParam(":phone_number", $this->phone_number);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Xóa QR code
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
}
?>

