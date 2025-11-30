import axios from 'axios'

// Get API URL from environment variable or use default
// If VITE_API_URL is not set or is a local domain, use relative URL (works with ngrok)
const VITE_API_URL = import.meta.env.VITE_API_URL
const isLocalDomain = VITE_API_URL && (
  VITE_API_URL.includes('localhost') || 
  VITE_API_URL.includes('.test') || 
  VITE_API_URL.includes('127.0.0.1')
)

// Use relative URL for ngrok/production, or configured URL for local dev
const API_BASE_URL = (!VITE_API_URL || isLocalDomain)
  ? '/api'  // Relative URL - works with any domain (ngrok, production, etc.)
  : `${VITE_API_URL.replace(/\/+$/, '')}/api`

// Debug: Log API configuration (only in development)
if (import.meta.env.DEV) {
  console.log('🔍 API Config:', {
    VITE_API_URL,
    API_BASE_URL,
    env: import.meta.env
  })
}

// Create axios instance
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use(
  (config) => {
    if (config.method === 'delete') {
      config.method = 'post'
      if (!config.data) {
        config.data = {}
      }
      config.data._method = 'DELETE'
    }
    
    if (config.method === 'put') {
      config.method = 'post'
      if (!config.data) {
        config.data = {}
      }
      if (!config.data._method) {
        config.data._method = 'PUT'
      }
    }
    
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

api.interceptors.response.use(
  (response) => {
    return response.data
  },
  (error) => {
    if (!error.response) {
      return Promise.reject({
        ...error,
        message: 'Không thể kết nối đến server. Vui lòng kiểm tra backend server.',
        status: null,
        isNetworkError: true
      })
    }
    
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    
    return Promise.reject({
      ...error,
      message: error.response?.data?.message || error.message || 'Có lỗi xảy ra',
      status: error.response?.status
    })
  }
)

export const authAPI = {
  getCurrentUser: async () => {
    try {
      const response = await api.get('/get-current-user.php')
      return response
    } catch (error) {
      return {
        success: false,
        message: error.message || 'Không thể lấy thông tin user'
      }
    }
  },
  
  login: async (username, password) => {
    try {
      return await api.post('/auth/login.php', { username, password })
    } catch (error) {
      if (error.response?.data) {
        return {
          success: false,
          message: error.response.data.message || 'Đăng nhập thất bại'
        }
      }
      return {
        success: false,
        message: error.message || 'Đăng nhập thất bại'
      }
    }
  },
  
  register: async (userData) => {
    try {
      return await api.post('/auth/register.php', userData)
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Đăng ký thất bại'
      }
    }
  },
  
  forgotPassword: async (email) => {
    try {
      const response = await api.post('/auth/forgot-password.php', { email })
      return response
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Có lỗi xảy ra khi quên mật khẩu'
      }
    }
  },
  
  googleLogin: async (idToken) => {
    try {
      const response = await api.post('/auth/google-login.php', { id_token: idToken })
      return response
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Đăng nhập Google thất bại'
      }
    }
  },
}

export const coursesAPI = {
  getAll: async (params = {}) => {
    const queryString = new URLSearchParams(params).toString()
    return api.get(`/get-courses.php?${queryString}`)
  },
  
  getById: async (id) => {
    const response = await coursesAPI.getAll()
    if (response.success && response.data) {
      const course = response.data.find(c => c.id === parseInt(id))
      return course ? { success: true, data: course } : { success: false }
    }
    return { success: false }
  },
  
  create: async (courseData) => {
    return api.post('/create-course.php', courseData)
  },
  
  update: async (id, courseData) => {
    return api.post(`/update-course.php?id=${id}`, { ...courseData, id, _method: 'PUT' })
  },
  
  delete: async (id) => {
    return api.delete(`/delete-course.php?id=${id}`)
  },
  
  search: async (keyword) => {
    return api.get(`/courses/search.php?keyword=${encodeURIComponent(keyword)}`)
  },
}

export const lessonsAPI = {
  getByCourse: async (courseId) => {
    return api.get(`/get-lessons.php?course_id=${courseId}`)
  },
  
  getById: async (lessonId) => {
    return api.get(`/get-lessons.php?lesson_id=${lessonId}`)
  },
  
  create: async (lessonData) => {
    return api.post('/create-lesson.php', lessonData)
  },
  
  update: async (id, lessonData) => {
    return api.post(`/update-lesson.php?id=${id}`, { ...lessonData, id, _method: 'PUT' })
  },
  
  delete: async (id) => {
    return api.post(`/delete-lesson.php?id=${id}`, { _method: 'DELETE' })
  },
}

export const assignmentsAPI = {
  getByCourse: async (courseId) => {
    return api.get(`/assignments.php?course_id=${courseId}`)
  },
  
  getById: async (id) => {
    return api.get(`/assignments.php?id=${id}`)
  },
  
  create: async (assignmentData) => {
    return api.post('/create-assignment.php', assignmentData)
  },
  
  update: async (id, assignmentData) => {
    const url = `/update-assignment.php?id=${id}`
    const data = { ...assignmentData, id, _method: 'PUT' }
    return api.post(url, data)
  },
  
  delete: async (id) => {
    // Use POST with _method=DELETE for PHP compatibility (interceptor will handle this)
    return api.post(`/delete-assignment.php?id=${id}`, { _method: 'DELETE' })
  },
  
  getQuizQuestions: async (assignmentId) => {
    return api.get(`/get-quiz-questions.php?assignment_id=${assignmentId}`)
  },
  
  submitQuiz: async (assignmentId, data) => {
    return api.post(`/submit-quiz.php?assignment_id=${assignmentId}`, data)
  },
  
  getMyQuizSubmission: async (assignmentId, studentId) => {
    return api.get(`/get-my-quiz-submission.php?assignment_id=${assignmentId}&student_id=${studentId}`)
  },
}

export const submissionsAPI = {
  getByAssignment: async (assignmentId) => {
    return api.get(`/submissions.php?assignment_id=${assignmentId}`)
  },
  
  getByStudent: async (studentId) => {
    return api.get(`/submissions.php?student_id=${studentId}`)
  },
  
  getMySubmission: async (assignmentId, studentId) => {
    return api.get(`/get-my-submission.php?assignment_id=${assignmentId}&student_id=${studentId}`)
  },
  
  submit: async (submissionData) => {
    return api.post('/submit-assignment.php', submissionData)
  },
  
  update: async (submissionData) => {
    return api.post('/submit-assignment.php', { ...submissionData, _method: 'PUT' })
  },
  
  grade: async (submissionId, gradeData) => {
    return api.post(`/grade-submission.php?id=${submissionId}`, { ...gradeData, submission_id: submissionId, _method: 'PUT' })
  },
  
  getByAssignment: async (assignmentId) => {
    return api.get(`/get-assignment-submissions.php?assignment_id=${assignmentId}`)
  },
}

export const gradesAPI = {
  getByStudent: async (studentId, courseId = null) => {
    const params = courseId 
      ? `student_id=${studentId}&course_id=${courseId}`
      : `student_id=${studentId}`
    return api.get(`/grades.php?${params}`)
  },
}

export const enrollmentsAPI = {
  enroll: async (courseId, studentId) => {
    return api.post('/enrollments/enroll.php', { course_id: courseId, student_id: studentId })
  },
  
  cancel: async (courseId, studentId) => {
    return api.post('/enrollments/cancel.php', { course_id: courseId, student_id: studentId, _method: 'DELETE' })
  },
  
  getMyCourses: async (studentId) => {
    return api.get(`/enrollments/my-courses.php?student_id=${studentId}`)
  },
  
  getByCourse: async (courseId) => {
    return api.get(`/enrollments/get-by-course.php?course_id=${courseId}`)
  },
  
  removeStudent: async (courseId, studentId) => {
    return api.post('/enrollments/cancel.php', { course_id: courseId, student_id: studentId, _method: 'DELETE' })
  },
}

export const progressAPI = {
  markComplete: async (studentId, courseId, lessonId, isCompleted = true) => {
    return api.post('/mark-lesson-complete.php', {
      student_id: studentId,
      course_id: courseId,
      lesson_id: lessonId,
      is_completed: isCompleted
    })
  },
  
  getByStudentAndCourse: async (studentId, courseId) => {
    return api.get(`/get-student-progress.php?student_id=${studentId}&course_id=${courseId}`)
  },
  
  getStudentCoursePerformance: async (studentId, courseId) => {
    return api.get(`/get-student-course-performance.php?student_id=${studentId}&course_id=${courseId}`)
  },
  
  update: async (progressData) => {
    return api.post('/progress/update.php', progressData)
  },
  
  getByCourse: async (courseId) => {
    return api.get(`/progress.php?course_id=${courseId}`)
  },
}

export const usersAPI = {
  getAll: async () => {
    return api.get('/users.php')
  },
  
  getById: async (id) => {
    return api.get(`/users.php?id=${id}`)
  },
  
  create: async (userData) => {
    return api.post('/create-user.php', userData)
  },
  
  update: async (id, userData) => {
    return api.post(`/update-user.php?id=${id}`, { ...userData, id, _method: 'PUT' })
  },
  
  delete: async (id) => {
    return api.post(`/delete-user.php?id=${id}`, { _method: 'DELETE' })
  },
}

export const chatAPI = {
  getMessagesByCourse: async (courseId, senderId) => {
    const params = new URLSearchParams()
    params.append('course_id', courseId)
    if (senderId) params.append('sender_id', senderId)
    return api.get(`/chat.php?${params.toString()}`)
  },
  
  getTeacherCourseChats: async (teacherId) => {
    return api.get(`/get-teacher-course-chats.php?teacher_id=${teacherId}`)
  },
  
  getConversation: async (senderId, receiverId, courseId = null) => {
    const params = new URLSearchParams()
    params.append('sender_id', senderId)
    params.append('receiver_id', receiverId)
    if (courseId) params.append('course_id', courseId)
    return api.get(`/chat.php?${params.toString()}`)
  },
  
  getConversations: async (userId) => {
    const params = new URLSearchParams()
    params.append('sender_id', userId)
    params.append('conversations_only', 'true')
    return api.get(`/chat.php?${params.toString()}`)
  },
  
  sendMessage: async (messageData) => {
    return api.post('/chat.php', messageData)
  },
  
  markAsRead: async (messageId) => {
    return api.put('/chat.php', { message_id: messageId })
  },
}

export const notificationsAPI = {
  getNotifications: async (receiverId, limit = 50, offset = 0, unreadOnly = false) => {
    const params = new URLSearchParams()
    params.append('receiver_id', receiverId)
    params.append('limit', limit)
    params.append('offset', offset)
    if (unreadOnly) params.append('unread_only', 'true')
    return api.get(`/get-notifications.php?${params.toString()}`)
  },
  
  markAsRead: async (notificationId, receiverId) => {
    return api.post('/mark-notification-read.php', {
      notification_id: notificationId,
      receiver_id: receiverId
    })
  },
  
  markAllAsRead: async (receiverId) => {
    return api.post('/mark-all-notifications-read.php', {
      receiver_id: receiverId
    })
  },
}

export const paymentQRCodesAPI = {
  getAll: async () => {
    return api.get('/payment-qr-codes.php')
  },
  
  getById: async (id) => {
    return api.get(`/payment-qr-codes.php?id=${id}`)
  },
  
  getByGateway: async (gateway) => {
    return api.get(`/payment-qr-codes.php?payment_gateway=${gateway}`)
  },
  
  create: async (qrCodeData) => {
    return api.post('/payment-qr-codes.php', qrCodeData)
  },
  
  update: async (id, qrCodeData) => {
    return api.post('/payment-qr-codes.php', { ...qrCodeData, id, _method: 'PUT' })
  },
  
  delete: async (id) => {
    return api.post('/payment-qr-codes.php', { id, _method: 'DELETE' })
  },
}

export const paymentsAPI = {
  getAll: async (params = {}) => {
    const queryString = new URLSearchParams(params).toString()
    return api.get(`/payments.php?${queryString}`)
  },
  
  getById: async (id) => {
    return api.get(`/payments.php?id=${id}`)
  },
  
  create: async (paymentData) => {
    return api.post('/create-payment.php', paymentData)
  },
  
  confirm: async (paymentId) => {
    return api.post('/confirm-payment.php', { payment_id: paymentId })
  },
  
  cancel: async (paymentId, studentId) => {
    return api.post('/cancel-payment.php', { payment_id: paymentId, student_id: studentId })
  },
  
  delete: async (paymentId) => {
    return api.post(`/delete-payment.php?id=${paymentId}`, { id: paymentId, _method: 'DELETE' })
  },
  
  getHistory: async (studentId) => {
    return api.get(`/payments.php?student_id=${studentId}`)
  },
}

export const statsAPI = {
  getAll: async () => {
    return api.get('/get-stats.php')
  },
  
  getStudentDashboard: async (studentId) => {
    return api.get(`/get-student-dashboard-stats.php?student_id=${studentId}`)
  },
  
  getTeacherDashboard: async (teacherId) => {
    return api.get(`/get-teacher-dashboard-stats.php?teacher_id=${teacherId}`)
  },
}

export const couponsAPI = {
  getAll: async () => {
    return api.get('/coupons.php')
  },
  
  getById: async (id) => {
    return api.get(`/coupons.php?id=${id}`)
  },
  
  getByCode: async (code) => {
    return api.get(`/coupons.php?code=${encodeURIComponent(code)}`)
  },
  
  create: async (couponData) => {
    return api.post('/create-coupon.php', couponData)
  },
  
  update: async (id, couponData) => {
    // Use POST with _method=PUT for PHP compatibility
    return api.post(`/update-coupon.php?id=${id}`, { ...couponData, id, _method: 'PUT' })
  },
  
  delete: async (id) => {
    // Use POST with _method=DELETE for PHP compatibility
    return api.post(`/delete-coupon.php?id=${id}`, { _method: 'DELETE' })
  },
}

export const reviewsAPI = {
  getByCourse: async (courseId, limit = 50, offset = 0) => {
    return api.get(`/reviews.php?course_id=${courseId}&limit=${limit}&offset=${offset}`)
  },
  
  getByStudent: async (courseId, studentId) => {
    return api.get(`/reviews.php?course_id=${courseId}&student_id=${studentId}`)
  },
  
  getStats: async (courseId) => {
    return api.get(`/reviews.php?course_id=${courseId}&stats_only=true`)
  },
  
  create: async (reviewData) => {
    return api.post('/reviews.php', reviewData)
  },
  
  update: async (id, reviewData) => {
    return api.put('/reviews.php', { id, ...reviewData })
  },
  
  delete: async (id, studentId) => {
    return api.delete(`/reviews.php?id=${id}&student_id=${studentId}`)
  },
}

export const performanceAPI = {
  getByCourse: async (courseId) => {
    return api.get(`/get-course-performance.php?course_id=${courseId}`)
  },
}

export default api

