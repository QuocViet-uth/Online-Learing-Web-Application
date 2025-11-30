<?php
/**
 * File: api/get-stats.php
 * Mục đích: API lấy thống kê tổng quan cho admin dashboard
 * Method: GET
 * Response: JSON với các số liệu thống kê
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể kết nối database"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

try {
    // 1. Tổng số users
    $stmt_users = $db->query("SELECT COUNT(*) as total FROM users");
    $total_users = $stmt_users->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 2. Tổng số courses
    $stmt_courses = $db->query("SELECT COUNT(*) as total FROM courses");
    $total_courses = $stmt_courses->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 3. Tổng doanh thu (từ payments với payment_status = 'completed')
    $stmt_revenue = $db->query("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE payment_status = 'completed'
    ");
    $total_revenue = floatval($stmt_revenue->fetch(PDO::FETCH_ASSOC)['total']);
    
    // 4. Tổng số enrollments (active)
    $stmt_enrollments = $db->query("
        SELECT COUNT(*) as total 
        FROM enrollments 
        WHERE status = 'active'
    ");
    $total_enrollments = $stmt_enrollments->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 5. Tính tăng trưởng (so sánh tháng này với tháng trước)
    // Tăng trưởng users trong tháng này (SQLite compatible)
    $stmt_users_this_month = $db->query("
        SELECT COUNT(*) as total 
        FROM users 
        WHERE strftime('%m', created_at) = strftime('%m', 'now') 
        AND strftime('%Y', created_at) = strftime('%Y', 'now')
    ");
    $users_this_month = $stmt_users_this_month->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt_users_last_month = $db->query("
        SELECT COUNT(*) as total 
        FROM users 
        WHERE strftime('%m', created_at) = strftime('%m', date('now', '-1 month'))
        AND strftime('%Y', created_at) = strftime('%Y', date('now', '-1 month'))
    ");
    $users_last_month = $stmt_users_last_month->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Tính phần trăm tăng trưởng
    $growth = 0;
    if ($users_last_month > 0) {
        $growth = (($users_this_month - $users_last_month) / $users_last_month) * 100;
    } elseif ($users_this_month > 0) {
        $growth = 100; // Tăng 100% nếu tháng trước = 0 và tháng này > 0
    }
    
    // 6. Số courses mới trong tháng này (SQLite compatible)
    $stmt_courses_this_month = $db->query("
        SELECT COUNT(*) as total 
        FROM courses 
        WHERE strftime('%m', created_at) = strftime('%m', 'now') 
        AND strftime('%Y', created_at) = strftime('%Y', 'now')
    ");
    $courses_this_month = $stmt_courses_this_month->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 7. Doanh thu tháng này (SQLite compatible)
    $stmt_revenue_this_month = $db->query("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE payment_status = 'completed'
        AND strftime('%m', payment_date) = strftime('%m', 'now') 
        AND strftime('%Y', payment_date) = strftime('%Y', 'now')
    ");
    $revenue_this_month = floatval($stmt_revenue_this_month->fetch(PDO::FETCH_ASSOC)['total']);
    
    // 8. Số teachers
    $stmt_teachers = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'teacher'");
    $total_teachers = $stmt_teachers->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 9. Số students
    $stmt_students = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
    $total_students = $stmt_students->fetch(PDO::FETCH_ASSOC)['total'];
    
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Lấy thống kê thành công",
        "data" => array(
            "total_users" => intval($total_users),
            "total_courses" => intval($total_courses),
            "total_revenue" => $total_revenue,
            "total_enrollments" => intval($total_enrollments),
            "growth" => round($growth, 2),
            "users_this_month" => intval($users_this_month),
            "courses_this_month" => intval($courses_this_month),
            "revenue_this_month" => $revenue_this_month,
            "total_teachers" => intval($total_teachers),
            "total_students" => intval($total_students)
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (PDOException $e) {
    error_log("Get Stats API - Exception: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server khi lấy thống kê: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

