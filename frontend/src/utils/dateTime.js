/**
 * File: utils/dateTime.js
 * Mục đích: Utility functions để format date/time trong frontend
 * Timezone: Asia/Ho_Chi_Minh (UTC+7)
 */

// Timezone mặc định cho ứng dụng
const APP_TIMEZONE = 'Asia/Ho_Chi_Minh';

/**
 * Format datetime để hiển thị cho người dùng
 * @param {string|Date} dateString - Datetime string hoặc Date object
 * @param {object} options - Options cho toLocaleString
 * @returns {string} - Formatted datetime string
 */
export const formatDateTime = (dateString, options = {}) => {
  if (!dateString) return '';
  
  try {
    const date = new Date(dateString);
    
    // Kiểm tra nếu date không hợp lệ
    if (isNaN(date.getTime())) {
      return dateString; // Trả về giá trị gốc nếu không parse được
    }
    
    const defaultOptions = {
      timeZone: APP_TIMEZONE,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      ...options
    };
    
    return date.toLocaleString('vi-VN', defaultOptions);
  } catch (error) {
    console.error('Error formatting datetime:', error);
    return dateString;
  }
};

/**
 * Format date để hiển thị (không có giờ)
 * @param {string|Date} dateString - Datetime string hoặc Date object
 * @param {object} options - Options cho toLocaleDateString
 * @returns {string} - Formatted date string
 */
export const formatDate = (dateString, options = {}) => {
  if (!dateString) return '';
  
  try {
    const date = new Date(dateString);
    
    if (isNaN(date.getTime())) {
      return dateString;
    }
    
    const defaultOptions = {
      timeZone: APP_TIMEZONE,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      ...options
    };
    
    return date.toLocaleDateString('vi-VN', defaultOptions);
  } catch (error) {
    console.error('Error formatting date:', error);
    return dateString;
  }
};

/**
 * Format time để hiển thị (chỉ giờ:phút)
 * @param {string|Date} dateString - Datetime string hoặc Date object
 * @returns {string} - Formatted time string (HH:mm)
 */
export const formatTime = (dateString) => {
  if (!dateString) return '';
  
  try {
    const date = new Date(dateString);
    
    if (isNaN(date.getTime())) {
      return dateString;
    }
    
    return date.toLocaleTimeString('vi-VN', {
      timeZone: APP_TIMEZONE,
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (error) {
    console.error('Error formatting time:', error);
    return dateString;
  }
};

/**
 * Format relative time (ví dụ: "2 giờ trước", "3 ngày trước")
 * @param {string|Date} dateString - Datetime string hoặc Date object
 * @returns {string} - Relative time string
 */
export const formatRelativeTime = (dateString) => {
  if (!dateString) return '';
  
  try {
    const date = new Date(dateString);
    
    if (isNaN(date.getTime())) {
      return dateString;
    }
    
    const now = new Date();
    const diffMs = now - date;
    const diffSecs = Math.floor(diffMs / 1000);
    const diffMins = Math.floor(diffSecs / 60);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);
    
    if (diffSecs < 60) {
      return 'Vừa xong';
    } else if (diffMins < 60) {
      return `${diffMins} phút trước`;
    } else if (diffHours < 24) {
      return `${diffHours} giờ trước`;
    } else if (diffDays < 7) {
      return `${diffDays} ngày trước`;
    } else {
      return formatDate(dateString);
    }
  } catch (error) {
    console.error('Error formatting relative time:', error);
    return dateString;
  }
};

/**
 * Format datetime cho input type="datetime-local"
 * @param {string|Date} dateString - Datetime string hoặc Date object
 * @returns {string} - Format: YYYY-MM-DDTHH:mm
 */
export const formatDateTimeLocal = (dateString) => {
  if (!dateString) return '';
  
  try {
    const date = new Date(dateString);
    
    if (isNaN(date.getTime())) {
      return '';
    }
    
    // Convert sang local timezone và format
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
  } catch (error) {
    console.error('Error formatting datetime local:', error);
    return '';
  }
};

/**
 * So sánh hai datetime
 * @param {string|Date} date1 - Datetime 1
 * @param {string|Date} date2 - Datetime 2
 * @returns {number} - Negative nếu date1 < date2, 0 nếu bằng, positive nếu date1 > date2
 */
export const compareDates = (date1, date2) => {
  try {
    const d1 = new Date(date1);
    const d2 = new Date(date2);
    
    if (isNaN(d1.getTime()) || isNaN(d2.getTime())) {
      return 0;
    }
    
    return d1 - d2;
  } catch (error) {
    console.error('Error comparing dates:', error);
    return 0;
  }
};

/**
 * Kiểm tra xem datetime có trong quá khứ không
 * @param {string|Date} dateString - Datetime string hoặc Date object
 * @returns {boolean} - true nếu trong quá khứ
 */
export const isPast = (dateString) => {
  if (!dateString) return false;
  
  try {
    const date = new Date(dateString);
    const now = new Date();
    
    if (isNaN(date.getTime())) {
      return false;
    }
    
    return date < now;
  } catch (error) {
    console.error('Error checking if past:', error);
    return false;
  }
};

/**
 * Kiểm tra xem datetime có trong tương lai không
 * @param {string|Date} dateString - Datetime string hoặc Date object
 * @returns {boolean} - true nếu trong tương lai
 */
export const isFuture = (dateString) => {
  if (!dateString) return false;
  
  try {
    const date = new Date(dateString);
    const now = new Date();
    
    if (isNaN(date.getTime())) {
      return false;
    }
    
    return date > now;
  } catch (error) {
    console.error('Error checking if future:', error);
    return false;
  }
};

/**
 * Lấy thời gian hiện tại theo timezone của ứng dụng
 * @returns {Date} - Current date object
 */
export const getCurrentDateTime = () => {
  return new Date();
};

export default {
  formatDateTime,
  formatDate,
  formatTime,
  formatRelativeTime,
  formatDateTimeLocal,
  compareDates,
  isPast,
  isFuture,
  getCurrentDateTime,
  APP_TIMEZONE
};

