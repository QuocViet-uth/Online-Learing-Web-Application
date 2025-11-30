<?php
/**
 * Mục đích: Model xử lý các thao tác liên quan đến User
 */

class User {
    // Kết nối database
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $full_name;
    public $date_of_birth;
    public $gender;
    public $school;
    public $password;
    public $email;
    public $phone;
    public $avatar;
    public $role;
    public $created_at;
    public $updated_at;

    /**
     * Constructor - Nhận kết nối database
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Tạo user mới (Đăng ký)
     * @return bool - true nếu thành công, false nếu thất bại
     */
    public function create() {
        // Kiểm tra và tạo cột gender nếu chưa có
        $this->ensureGenderColumn();
        
        // Query SQL - SQLite compatible syntax
        $query = "INSERT INTO " . $this->table_name . "
                SET username = :username,
                    full_name = :full_name,
                    date_of_birth = :date_of_birth,
                    gender = :gender,
                    school = :school,
                    password = :password,
                    email = :email,
                    phone = :phone,
                    avatar = :avatar,
                    role = :role";
        
        // Chuẩn bị câu truy vấn
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch dữ liệu (防止 XSS)
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->full_name = isset($this->full_name) ? htmlspecialchars(strip_tags($this->full_name)) : null;
        $this->date_of_birth = isset($this->date_of_birth) ? $this->date_of_birth : null;
        $this->gender = isset($this->gender) && in_array($this->gender, ['male', 'female', 'other']) ? $this->gender : null;
        $this->school = isset($this->school) ? htmlspecialchars(strip_tags($this->school)) : null;
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->role = htmlspecialchars(strip_tags($this->role));
        
        // Mã hóa mật khẩu bằng bcrypt
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        // Bind các giá trị
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":date_of_birth", $this->date_of_birth);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":school", $this->school);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":avatar", $this->avatar);
        $stmt->bindParam(":role", $this->role);
        
        // Thực thi query
        if($stmt->execute()) {
            // Lưu ID của user vừa tạo
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    /**
     * Đăng nhập
     * @return bool - true nếu đúng username và password
     */
    public function login() {
        // Kiểm tra kết nối database
        if (!$this->conn) {
            error_log("User::login() - Database connection is null");
            return false;
        }
        
        // Query lấy thông tin user theo username
        $query = "SELECT id, username, full_name, password, email, role, avatar
                  FROM " . $this->table_name . "
                  WHERE username = :username
                  LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            // Làm sạch username
            $this->username = htmlspecialchars(strip_tags($this->username));
            
            // Bind username
            $stmt->bindParam(":username", $this->username);
            
            // Thực thi
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("User::login() - PDO error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("User::login() - General error: " . $e->getMessage());
            return false;
        }
        
        // Lấy dữ liệu
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Kiểm tra user có tồn tại không
        if($row) {
            // Kiểm tra password
            if(password_verify($this->password, $row['password'])) {
                // Đúng password → Lưu thông tin vào object
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->full_name = isset($row['full_name']) ? $row['full_name'] : null;
                $this->email = $row['email'];
                $this->role = $row['role'];
                $this->avatar = isset($row['avatar']) ? $row['avatar'] : 'default-avatar.png';
                return true;
            }
        }
        
        return false;
    }

    /**
     * Lấy thông tin 1 user theo ID
     * @return bool - true nếu tìm thấy user
     */
    public function readOne() {
        // Đảm bảo cột gender tồn tại
        $this->ensureGenderColumn();
        
        $query = "SELECT id, username, full_name, date_of_birth, gender, school, email, phone, avatar, role, 
                         created_at, updated_at
                  FROM " . $this->table_name . "
                  WHERE id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Gán giá trị vào các thuộc tính
            $this->username = $row['username'];
            $this->full_name = isset($row['full_name']) ? $row['full_name'] : null;
            $this->date_of_birth = isset($row['date_of_birth']) ? $row['date_of_birth'] : null;
            $this->gender = isset($row['gender']) ? $row['gender'] : null;
            $this->school = isset($row['school']) ? $row['school'] : null;
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->avatar = $row['avatar'];
            $this->role = $row['role'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }

    /**
     * Lấy tất cả users (Cho Admin)
     * @return PDOStatement - Kết quả query
     */
    public function readAll() {
        // Đảm bảo cột gender tồn tại
        $this->ensureGenderColumn();
        
        $query = "SELECT id, username, full_name, date_of_birth, gender, school, email, phone, avatar, role, 
                         created_at, updated_at
                  FROM " . $this->table_name . "
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Lấy users theo role (Admin, Teacher, Student)
     * @return PDOStatement - Kết quả query
     */
    public function readByRole() {
        $query = "SELECT id, username, email, phone, avatar, role, 
                         created_at
                  FROM " . $this->table_name . "
                  WHERE role = :role
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":role", $this->role);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Cập nhật thông tin user
     * @return bool - true nếu cập nhật thành công
     */
    public function update() {
        // Kiểm tra và tạo cột gender nếu chưa có
        $this->ensureGenderColumn();
        
        // Cập nhật tất cả các trường (vì đã được set trong update-user.php)
        $query = "UPDATE " . $this->table_name . "
                  SET username = :username,
                      full_name = :full_name,
                      date_of_birth = :date_of_birth,
                      gender = :gender,
                      school = :school,
                      email = :email,
                      phone = :phone,
                      avatar = :avatar,
                      role = :role
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Làm sạch và chuẩn bị dữ liệu
        $this->username = htmlspecialchars(strip_tags($this->username));
        
        // Xử lý full_name - giữ nguyên giá trị nếu là string, chỉ set null nếu thực sự là null
        if ($this->full_name !== null) {
            $this->full_name = htmlspecialchars(strip_tags(trim($this->full_name)));
            // Nếu sau khi trim là empty string, set thành null
            if ($this->full_name === '') {
                $this->full_name = null;
            }
        }
        
        $this->date_of_birth = $this->date_of_birth ?: null;
        $this->gender = isset($this->gender) && in_array($this->gender, ['male', 'female', 'other']) ? $this->gender : null;
        $this->school = $this->school ? htmlspecialchars(strip_tags($this->school)) : null;
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->avatar = htmlspecialchars(strip_tags($this->avatar));
        $this->role = htmlspecialchars(strip_tags($this->role));
        
        // Bind giá trị - sử dụng PDO::PARAM_NULL cho null values
        $stmt->bindValue(":username", $this->username);
        
        if ($this->full_name === null) {
            $stmt->bindValue(":full_name", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":full_name", $this->full_name);
        }
        
        if ($this->date_of_birth === null) {
            $stmt->bindValue(":date_of_birth", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":date_of_birth", $this->date_of_birth);
        }
        
        if ($this->gender === null) {
            $stmt->bindValue(":gender", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":gender", $this->gender);
        }
        
        if ($this->school === null) {
            $stmt->bindValue(":school", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":school", $this->school);
        }
        
        $stmt->bindValue(":email", $this->email);
        $stmt->bindValue(":phone", $this->phone);
        $stmt->bindValue(":avatar", $this->avatar);
        $stmt->bindValue(":role", $this->role);
        $stmt->bindValue(":id", $this->id);
        
        // Log để debug
        error_log("User::update() - ID: {$this->id}");
        error_log("User::update() - full_name: " . var_export($this->full_name, true));
        error_log("User::update() - username: " . var_export($this->username, true));
        error_log("User::update() - email: " . var_export($this->email, true));
        
        if($stmt->execute()) {
            $rowCount = $stmt->rowCount();
            error_log("User::update() - Rows affected: $rowCount");
            return true;
        } else {
            // Log lỗi nếu có
            $errorInfo = $stmt->errorInfo();
            error_log("User::update() failed - Error: " . var_export($errorInfo, true));
        }
        
        return false;
    }

    /**
     * Đảm bảo cột gender tồn tại trong bảng users
     * Tự động tạo cột nếu chưa có
     */
    private function ensureGenderColumn() {
        try {
            // Kiểm tra xem cột gender có tồn tại không
            $checkStmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name . " LIKE 'gender'");
            if ($checkStmt->rowCount() == 0) {
                // Kiểm tra xem cột date_of_birth có tồn tại không để quyết định vị trí
                $checkDobStmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name . " LIKE 'date_of_birth'");
                $hasDobColumn = $checkDobStmt->rowCount() > 0;
                
                // Tạo cột gender nếu chưa có
                if ($hasDobColumn) {
                    $alterQuery = "ALTER TABLE " . $this->table_name . " 
                                   ADD COLUMN gender ENUM('male', 'female', 'other') NULL 
                                   AFTER date_of_birth";
                } else {
                    // Nếu không có date_of_birth, thêm sau full_name hoặc ở cuối
                    $alterQuery = "ALTER TABLE " . $this->table_name . " 
                                   ADD COLUMN gender ENUM('male', 'female', 'other') NULL 
                                   AFTER full_name";
                }
                
                $this->conn->exec($alterQuery);
            }
        } catch (PDOException $e) {
            // Bỏ qua lỗi nếu không thể tạo cột (có thể do quyền hạn hoặc cột đã tồn tại)
            // Chỉ log nếu không phải lỗi "duplicate column"
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                error_log("Warning: Could not ensure gender column exists: " . $e->getMessage());
            }
        } catch (Exception $e) {
            error_log("Warning: Could not ensure gender column exists: " . $e->getMessage());
        }
    }

    /**
     * Đổi mật khẩu
     * @return bool - true nếu đổi thành công
     */
    public function changePassword() {
        $query = "UPDATE " . $this->table_name . "
                  SET password = :password
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Mã hóa password mới
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Xóa user
     * @return bool - true nếu xóa thành công
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
     * Kiểm tra username đã tồn tại chưa
     * @return bool - true nếu đã tồn tại
     */
    public function usernameExists() {
        $query = "SELECT id FROM " . $this->table_name . "
                  WHERE username = :username
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $this->username = htmlspecialchars(strip_tags($this->username));
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }

    /**
     * Lấy thông tin user theo email
     * @return bool - true nếu tìm thấy user
     */
    public function readByEmail() {
        $query = "SELECT id, username, full_name, date_of_birth, gender, school, email, phone, avatar, role, 
                         created_at, updated_at
                  FROM " . $this->table_name . "
                  WHERE email = :email
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->full_name = isset($row['full_name']) ? $row['full_name'] : null;
            $this->date_of_birth = isset($row['date_of_birth']) ? $row['date_of_birth'] : null;
            $this->gender = isset($row['gender']) ? $row['gender'] : null;
            $this->school = isset($row['school']) ? $row['school'] : null;
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->avatar = $row['avatar'];
            $this->role = $row['role'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }

    /**
     * Kiểm tra email đã tồn tại chưa
     * @return bool - true nếu đã tồn tại
     */
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . "
                  WHERE email = :email
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }

    /**
     * Đếm số lượng users theo role
     * @return int - Số lượng users
     */
    public function countByRole() {
        $query = "SELECT COUNT(*) as total
                  FROM " . $this->table_name . "
                  WHERE role = :role";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":role", $this->role);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>