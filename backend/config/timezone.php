<?php
/**
 * File: config/timezone.php
 * Mục đích: Cấu hình timezone cho toàn bộ ứng dụng
 * 
 * Timezone mặc định: Asia/Ho_Chi_Minh (UTC+7) - Việt Nam
 */

// Timezone mặc định cho ứng dụng
// Có thể override bằng biến môi trường TZ
$default_timezone = getenv('TZ') ?: 'Asia/Ho_Chi_Minh';
define('APP_TIMEZONE', $default_timezone);

// Set timezone cho PHP
date_default_timezone_set(APP_TIMEZONE);

/**
 * Chuyển đổi datetime từ UTC sang timezone của ứng dụng
 * @param string $utc_datetime - Datetime string từ database (UTC)
 * @param string $format - Format output (mặc định: 'Y-m-d H:i:s')
 * @return string - Datetime đã convert sang timezone của ứng dụng
 */
function convertToAppTimezone($utc_datetime, $format = 'Y-m-d H:i:s') {
    if (empty($utc_datetime)) {
        return null;
    }
    
    try {
        $utc = new DateTime($utc_datetime, new DateTimeZone('UTC'));
        $utc->setTimezone(new DateTimeZone(APP_TIMEZONE));
        return $utc->format($format);
    } catch (Exception $e) {
        error_log("Error converting timezone: " . $e->getMessage());
        return $utc_datetime; // Trả về giá trị gốc nếu có lỗi
    }
}

/**
 * Chuyển đổi datetime từ timezone của ứng dụng sang UTC
 * @param string $local_datetime - Datetime string từ input (local timezone)
 * @param string $format - Format output (mặc định: 'Y-m-d H:i:s')
 * @return string - Datetime đã convert sang UTC
 */
function convertToUTC($local_datetime, $format = 'Y-m-d H:i:s') {
    if (empty($local_datetime)) {
        return null;
    }
    
    try {
        $local = new DateTime($local_datetime, new DateTimeZone(APP_TIMEZONE));
        $local->setTimezone(new DateTimeZone('UTC'));
        return $local->format($format);
    } catch (Exception $e) {
        error_log("Error converting to UTC: " . $e->getMessage());
        return $local_datetime; // Trả về giá trị gốc nếu có lỗi
    }
}

/**
 * Format datetime để hiển thị cho người dùng
 * @param string $datetime - Datetime string
 * @param string $format - Format output (mặc định: 'd/m/Y H:i')
 * @return string - Datetime đã format
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) {
        return '';
    }
    
    try {
        // Giả sử datetime từ database là UTC
        $dt = new DateTime($datetime, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
        return $dt->format($format);
    } catch (Exception $e) {
        error_log("Error formatting datetime: " . $e->getMessage());
        return $datetime;
    }
}

/**
 * Lấy thời gian hiện tại theo timezone của ứng dụng
 * @param string $format - Format output (mặc định: 'Y-m-d H:i:s')
 * @return string - Current datetime
 */
function getCurrentDateTime($format = 'Y-m-d H:i:s') {
    return date($format);
}

/**
 * Lấy thời gian hiện tại theo UTC
 * @param string $format - Format output (mặc định: 'Y-m-d H:i:s')
 * @return string - Current datetime in UTC
 */
function getCurrentDateTimeUTC($format = 'Y-m-d H:i:s') {
    $dt = new DateTime('now', new DateTimeZone(APP_TIMEZONE));
    $dt->setTimezone(new DateTimeZone('UTC'));
    return $dt->format($format);
}

/**
 * Format datetime sang ISO 8601 với timezone
 * @param string $datetime - Datetime string (UTC từ database)
 * @return string - ISO 8601 format với timezone
 */
function formatDateTimeISO($datetime) {
    if (empty($datetime)) {
        return null;
    }
    
    try {
        $dt = new DateTime($datetime, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
        return $dt->format('c'); // ISO 8601 format
    } catch (Exception $e) {
        error_log("Error formatting datetime to ISO: " . $e->getMessage());
        return $datetime;
    }
}

