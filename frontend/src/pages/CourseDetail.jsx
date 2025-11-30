import { useState, useEffect } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import { 
  FiPlay, 
  FiClock, 
  FiUsers, 
  FiStar, 
  FiCheck, 
  FiBook, 
  FiFileText,
  FiCalendar,
  FiMessageCircle,
  FiEdit,
  FiVideo,
  FiTrendingUp,
  FiAlertCircle
} from 'react-icons/fi'
import { coursesAPI, lessonsAPI, assignmentsAPI, enrollmentsAPI, submissionsAPI, reviewsAPI } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import toast from 'react-hot-toast'
import CourseChat from '../components/Chat/CourseChat'
import SubmitAssignmentModal from '../components/Assignment/SubmitAssignmentModal'
import QuizTakeModal from '../components/Assignment/QuizTakeModal'
import ReviewForm from '../components/Review/ReviewForm'
import ReviewList from '../components/Review/ReviewList'
import { formatDateTime, formatDate, isPast } from '../utils/dateTime'

const CourseDetail = () => {
  const { id } = useParams()
  const navigate = useNavigate()
  const { user, isAuthenticated } = useAuth()
  const [course, setCourse] = useState(null)
  const [lessons, setLessons] = useState([])
  const [assignments, setAssignments] = useState([])
  const [submissions, setSubmissions] = useState({}) // { assignmentId: submission }
  const [loading, setLoading] = useState(true)
  const [activeTab, setActiveTab] = useState('overview')
  const [enrolling, setEnrolling] = useState(false)
  const [chatOpen, setChatOpen] = useState(false)
  const [submitModalOpen, setSubmitModalOpen] = useState(false)
  const [quizModalOpen, setQuizModalOpen] = useState(false)
  const [selectedAssignment, setSelectedAssignment] = useState(null)
  const [reviewStats, setReviewStats] = useState(null)
  const [myReview, setMyReview] = useState(null)
  const [showReviewForm, setShowReviewForm] = useState(false)
  const [isEnrolled, setIsEnrolled] = useState(false)

  useEffect(() => {
    loadCourseData()
  }, [id, user])

  const loadCourseData = async () => {
    try {
      setLoading(true)
      
      // Lấy course data
      const courseRes = await coursesAPI.getById(id)
      if (courseRes.success && courseRes.data) {
        setCourse(courseRes.data)
        // Ưu tiên sử dụng lessons từ course object (từ get-courses.php)
        if (courseRes.data.lessons?.data && Array.isArray(courseRes.data.lessons.data)) {
          setLessons(courseRes.data.lessons.data)
        }
      }
      
      // Lấy lessons từ API riêng nếu chưa có từ course
      if (!courseRes.data?.lessons?.data || courseRes.data.lessons.data.length === 0) {
        try {
          const lessonsRes = await lessonsAPI.getByCourse(id)
          // API interceptor trả về response.data, nên lessonsRes đã là { success: true, data: [...] }
          if (lessonsRes && lessonsRes.success && lessonsRes.data) {
            const lessonsData = Array.isArray(lessonsRes.data) ? lessonsRes.data : []
            if (lessonsData.length > 0) {
              setLessons(lessonsData)
            }
          }
        } catch (lessonsError) {
          console.error('Error loading lessons:', lessonsError)
          // Không hiển thị toast vì có thể course không có lessons
        }
      }
      
      // Lấy assignments
      try {
        const assignmentsRes = await assignmentsAPI.getByCourse(id)
        if (assignmentsRes && assignmentsRes.success && assignmentsRes.data) {
          const assignmentsList = Array.isArray(assignmentsRes.data) ? assignmentsRes.data : []
          setAssignments(assignmentsList)
          
          // Load submissions cho mỗi assignment nếu user là student
          if (user && user.role === 'student' && user.id) {
            const submissionsMap = {}
            for (const assignment of assignmentsList) {
              try {
                if (assignment.type === 'quiz') {
                  // Load quiz submission
                  const submissionRes = await assignmentsAPI.getMyQuizSubmission(assignment.id, user.id)
                  if (submissionRes && submissionRes.success && submissionRes.data) {
                    submissionsMap[assignment.id] = submissionRes.data
                  }
                } else {
                  // Load homework submission
                  const submissionRes = await submissionsAPI.getMySubmission(assignment.id, user.id)
                  if (submissionRes.data && submissionRes.data.success && submissionRes.data.data) {
                    submissionsMap[assignment.id] = submissionRes.data.data
                  }
                }
              } catch (err) {
                console.error(`Error loading submission for assignment ${assignment.id}:`, err)
              }
            }
            setSubmissions(submissionsMap)
          }
        }
      } catch (assignmentsError) {
        console.error('Error loading assignments:', assignmentsError)
        // Không hiển thị toast vì có thể course không có assignments
      }
      
      // Kiểm tra enrollment nếu user là student
      if (user && user.role === 'student' && user.id) {
        try {
          const enrollRes = await enrollmentsAPI.getByCourse(id)
          const enrollmentsData = enrollRes?.data || enrollRes
          
          if (enrollRes && enrollRes.success && enrollmentsData) {
            const enrollmentsList = Array.isArray(enrollmentsData) 
              ? enrollmentsData 
              : (Array.isArray(enrollmentsData.data) ? enrollmentsData.data : [])
            
            const myEnrollment = enrollmentsList.find(e => {
              const studentId = e.student_id || e.studentId
              const status = e.status
              return studentId === user.id && status === 'active'
            })
            
            setIsEnrolled(!!myEnrollment)
            
            if (myEnrollment) {
              try {
                const reviewRes = await reviewsAPI.getByStudent(id, user.id)
                if (reviewRes && reviewRes.success && reviewRes.data) {
                  setMyReview(reviewRes.data)
                } else {
                  setMyReview(null)
                }
              } catch (error) {
                console.error('Error loading my review:', error)
                setMyReview(null)
              }
            } else {
              setMyReview(null)
            }
          } else {
            setIsEnrolled(false)
            setMyReview(null)
          }
        } catch (error) {
          console.error('Error checking enrollment:', error)
          setIsEnrolled(false)
          setMyReview(null)
        }
      } else {
        setIsEnrolled(false)
      }
      
      // Load review stats - ưu tiên dùng từ course.reviews nếu có, nếu không thì gọi API riêng
      // Sử dụng courseRes.data thay vì course state vì state có thể chưa được cập nhật
      const courseData = courseRes?.data || course
      if (courseData && courseData.reviews && courseData.reviews.average_rating !== undefined) {
        // Sử dụng dữ liệu từ course.reviews để đồng bộ với danh sách
        setReviewStats({
          average_rating: courseData.reviews.average_rating,
          total_reviews: courseData.reviews.total_reviews
        })
        // Vẫn gọi API để lấy distribution nếu cần
        try {
          const statsRes = await reviewsAPI.getStats(id)
          if (statsRes && statsRes.success && statsRes.data) {
            setReviewStats(statsRes.data) // Cập nhật với distribution
          }
        } catch (error) {
          console.error('Error loading review stats:', error)
        }
      } else {
        // Nếu không có trong course data, gọi API
        try {
          const statsRes = await reviewsAPI.getStats(id)
          if (statsRes && statsRes.success && statsRes.data) {
            setReviewStats(statsRes.data)
          }
        } catch (error) {
          console.error('Error loading review stats:', error)
        }
      }
    } catch (error) {
      console.error('Error loading course data:', error)
      toast.error('Không thể tải thông tin khóa học')
    } finally {
      setLoading(false)
    }
  }

  const handleEnroll = async () => {
    if (!isAuthenticated) {
      navigate('/login')
      return
    }

    // Kiểm tra user có id không
    if (!user || !user.id) {
      toast.error('Vui lòng đăng nhập lại')
      return
    }

    // Kiểm tra giá khóa học
    const coursePrice = parseFloat(course.price) || 0
    
    // Nếu khóa học có giá (> 0), redirect đến trang thanh toán
    if (coursePrice > 0) {
      navigate(`/checkout/${id}`)
      return
    }

    // Nếu miễn phí (price = 0), đăng ký trực tiếp không cần thanh toán
    setEnrolling(true)
    try {
      const result = await enrollmentsAPI.enroll(id, user.id)
      if (result.success) {
        toast.success('Đăng ký khóa học miễn phí thành công!')
        // Reload course data để cập nhật trạng thái enrolled
        await loadCourseData()
        // Navigate đến trang học
        navigate(`/courses/${id}/learn`)
      } else {
        // Nếu backend yêu cầu thanh toán, redirect đến checkout
        if (result.requires_payment) {
          toast.info('Khóa học này có phí. Đang chuyển đến trang thanh toán...')
          navigate(`/checkout/${id}`)
        } else {
          toast.error(result.message || 'Đăng ký thất bại')
        }
      }
    } catch (error) {
      console.error('Enroll error:', error)
      // Nếu lỗi do yêu cầu thanh toán, redirect
      if (error.response?.data?.requires_payment) {
        navigate(`/checkout/${id}`)
      } else {
        toast.error(error.message || 'Có lỗi xảy ra khi đăng ký')
      }
    } finally {
      setEnrolling(false)
    }
  }


  const handleReviewUpdate = () => {
    // Reload stats after review update
    reviewsAPI.getStats(id).then(res => {
      if (res.success && res.data) {
        setReviewStats(res.data)
      }
    })
    
    // Reload my review
    if (user && user.role === 'student' && isEnrolled) {
      reviewsAPI.getByStudent(id, user.id).then(res => {
        if (res.success && res.data) {
          setMyReview(res.data)
        } else {
          setMyReview(null)
        }
      })
    }
  }

  const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND',
    }).format(price)
  }

  if (loading) {
    return (
      <div className="pt-16 md:pt-20 min-h-screen flex items-center justify-center">
        <div className="spinner"></div>
      </div>
    )
  }

  if (!course) {
    return (
      <div className="pt-16 md:pt-20 min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center max-w-md">
          <div className="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <FiBook className="w-12 h-12 text-gray-400" />
          </div>
          <p className="text-gray-600 text-lg mb-2 font-medium">Không tìm thấy khóa học</p>
          <p className="text-gray-500 text-sm mb-6">Khóa học này có thể đã bị xóa hoặc không tồn tại</p>
          <Link to="/courses" className="btn btn-primary inline-flex items-center">
            <FiBook className="mr-2" />
            Quay lại danh sách
          </Link>
        </div>
      </div>
    )
  }

  const totalDuration = lessons.reduce((sum, lesson) => sum + (lesson.duration || 0), 0)

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-primary-600 to-secondary-600 text-white">
        <div className="container-custom py-8 sm:py-12 px-3 sm:px-4">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
            {/* Main Content */}
            <div className="lg:col-span-2">
              <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold mb-3 sm:mb-4">{course.title}</h1>
              <p className="text-base sm:text-lg md:text-xl text-gray-100 mb-4 sm:mb-6 line-clamp-2 sm:line-clamp-none">{course.description}</p>
              <div className="flex flex-wrap gap-2 sm:gap-4 text-xs sm:text-sm">
                <div className="flex items-center">
                  <FiUsers className="mr-2" />
                  <span>Giảng viên: {course.teacher_name || 'N/A'}</span>
                </div>
                <div className="flex items-center">
                  <FiStar className="mr-2" />
                  <span>
                    {reviewStats ? (
                      <>
                        {reviewStats.average_rating.toFixed(1)} ({reviewStats.total_reviews} {reviewStats.total_reviews === 1 ? 'đánh giá' : 'đánh giá'})
                      </>
                    ) : (
                      'Chưa có đánh giá'
                    )}
                  </span>
                </div>
                <div className="flex items-center">
                  <FiClock className="mr-2" />
                  <span>{totalDuration} phút</span>
                </div>
                <div className="flex items-center">
                  <FiBook className="mr-2" />
                  <span>{lessons.length} bài học</span>
                </div>
                {course.online_link && (
                  <div className="flex items-center">
                    <FiVideo className="mr-2" />
                    <span>Có học online</span>
                  </div>
                )}
              </div>
            </div>

            {/* Sidebar - Desktop */}
            <div className="hidden lg:block">
              <div className="bg-white/10 backdrop-blur-md rounded-xl p-6 sticky top-24">
                <div className="text-center mb-6">
                  <div className="text-3xl font-bold mb-2">{formatPrice(course.price)}</div>
                  {course.price === 0 && (
                    <span className="text-green-300 font-semibold">Miễn phí</span>
                  )}
                </div>
              {/* Hiển thị nút chỉnh sửa cho teacher, nút đăng ký cho student (chỉ khi chưa đăng ký) */}
              {user && user.role === 'teacher' ? (
                <Link
                  to={`/teacher/courses/${id}/manage`}
                  className="w-full btn bg-white text-primary-600 hover:bg-gray-100 py-3 text-lg font-semibold mb-3 flex items-center justify-center"
                >
                  <FiEdit className="inline mr-2" />
                  Chỉnh sửa khóa học
                </Link>
              ) : (
                !isEnrolled && (
                  <button
                    onClick={handleEnroll}
                    disabled={enrolling}
                    className="w-full btn bg-white text-primary-600 hover:bg-gray-100 py-3 text-lg font-semibold mb-3"
                  >
                    {enrolling ? (
                      <span className="flex items-center justify-center">
                        <div className="spinner mr-2"></div>
                        Đang xử lý...
                      </span>
                    ) : (
                      <>
                        <FiPlay className="inline mr-2" />
                        {course.price === 0 ? 'Đăng ký miễn phí' : 'Mua khóa học'}
                      </>
                    )}
                  </button>
                )
              )}
              {/* Chỉ hiển thị nút chat và hiệu suất cho student đã đăng ký */}
              {user && user.role === 'student' && isEnrolled && (
                <>
                  <Link
                    to={`/student/courses/${id}/performance`}
                    className="w-full btn bg-primary-600 text-white hover:bg-primary-700 py-2 text-sm mb-3 flex items-center justify-center"
                  >
                    <FiTrendingUp className="inline mr-2" />
                    Xem hiệu suất học tập
                  </Link>
                  <button
                    onClick={() => setChatOpen(true)}
                    className="w-full btn btn-outline py-2 text-sm mb-4"
                  >
                    <FiMessageCircle className="inline mr-2" />
                    Chat với giảng viên
                  </button>
                </>
              )}
                <div className="space-y-2 text-sm">
                  <div className="flex items-center">
                    <FiCheck className="mr-2" />
                    <span>Truy cập trọn đời</span>
                  </div>
                  <div className="flex items-center">
                    <FiCheck className="mr-2" />
                    <span>Chứng chỉ hoàn thành</span>
                  </div>
                  <div className="flex items-center">
                    <FiCheck className="mr-2" />
                    <span>Hỗ trợ 24/7</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="container-custom py-6 sm:py-8 px-3 sm:px-4">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2">
            {/* Tabs */}
            <div className="bg-white rounded-xl shadow-sm mb-4 sm:mb-6">
              <div className="flex border-b border-gray-200 overflow-x-auto custom-scrollbar">
                {[
                  { id: 'overview', label: 'Tổng quan' },
                  { id: 'curriculum', label: 'Giáo trình' },
                  { id: 'assignments', label: 'Bài tập' },
                  { id: 'reviews', label: 'Đánh giá' },
                ].map((tab) => (
                  <button
                    key={tab.id}
                    onClick={() => setActiveTab(tab.id)}
                    className={`flex-shrink-0 px-4 sm:px-6 py-3 sm:py-4 text-sm sm:text-base font-medium transition-colors whitespace-nowrap ${
                      activeTab === tab.id
                        ? 'text-primary-600 border-b-2 border-primary-600'
                        : 'text-gray-600 hover:text-gray-900'
                    }`}
                  >
                    {tab.label}
                  </button>
                ))}
              </div>

              <div className="p-4 sm:p-6">
                {activeTab === 'overview' && (
                  <div>
                    <h3 className="text-xl font-bold mb-4">Về khóa học này</h3>
                    <div className="prose max-w-none">
                      <p className="text-gray-700 mb-4">{course.description}</p>
                      <h4 className="font-semibold mb-2">Bạn sẽ học được gì:</h4>
                      <ul className="list-disc list-inside space-y-2 text-gray-700">
                        <li>Kiến thức cơ bản và nâng cao</li>
                        <li>Thực hành với các dự án thực tế</li>
                        <li>Kỹ năng cần thiết cho công việc</li>
                        <li>Best practices và tips từ chuyên gia</li>
                      </ul>
                    </div>
                  </div>
                )}

                {activeTab === 'curriculum' && (
                  <div>
                    <h3 className="text-xl font-bold mb-4">Giáo trình khóa học</h3>
                    <div className="space-y-2">
                      {lessons.length === 0 ? (
                        <div className="text-center py-8">
                          <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <FiFileText className="w-8 h-8 text-gray-400" />
                          </div>
                          <p className="text-gray-600">Chưa có bài học nào</p>
                        </div>
                      ) : (
                        lessons.map((lesson, index) => (
                          <motion.div
                            key={lesson.id}
                            initial={{ opacity: 0, x: -20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.3, delay: index * 0.05 }}
                            whileHover={{ x: 4, transition: { duration: 0.2 } }}
                            className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer group"
                          >
                            <div className="flex items-center space-x-4">
                              <motion.div 
                                className="w-8 h-8 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center font-semibold"
                                whileHover={{ scale: 1.1, rotate: 360 }}
                                transition={{ duration: 0.4 }}
                              >
                                {index + 1}
                              </motion.div>
                              <div>
                                <h4 className="font-medium group-hover:text-primary-600 transition-colors">{lesson.title}</h4>
                                <p className="text-sm text-gray-600">
                                  {lesson.duration || 0} phút
                                </p>
                              </div>
                            </div>
                            {isAuthenticated && (
                              <Link
                                to={`/courses/${id}/learn?lesson=${lesson.id}`}
                                className="text-primary-600 hover:text-primary-700"
                              >
                                <motion.div
                                  whileHover={{ scale: 1.2 }}
                                  whileTap={{ scale: 0.9 }}
                                >
                                  <FiPlay className="w-5 h-5" />
                                </motion.div>
                              </Link>
                            )}
                          </motion.div>
                        ))
                      )}
                    </div>
                  </div>
                )}

                {activeTab === 'assignments' && (
                  <div>
                    <h3 className="text-xl font-bold mb-4">Bài tập và kiểm tra</h3>
                    <div className="space-y-4">
                      {assignments.length === 0 ? (
                        <div className="text-center py-8">
                          <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <FiFileText className="w-8 h-8 text-gray-400" />
                          </div>
                          <p className="text-gray-600">Chưa có bài tập nào</p>
                        </div>
                      ) : (
                        <motion.div
                          initial="hidden"
                          animate="visible"
                          variants={{
                            hidden: { opacity: 0 },
                            visible: {
                              opacity: 1,
                              transition: {
                                staggerChildren: 0.1
                              }
                            }
                          }}
                        >
                          {assignments.map((assignment) => {
                          const submission = submissions[assignment.id]
                          const isPastDeadline = isPast(assignment.deadline)
                          const isStudent = user && user.role === 'student'
                          
                          return (
                            <motion.div
                              key={assignment.id}
                              variants={{
                                hidden: { opacity: 0, y: 20 },
                                visible: { opacity: 1, y: 0 }
                              }}
                              whileHover={{ y: -4, transition: { duration: 0.2 } }}
                              className="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
                            >
                              <div className="flex items-start justify-between mb-2">
                                <div className="flex-1">
                                  <h4 className="font-semibold">{assignment.title}</h4>
                                  <p className="text-sm text-gray-600 mt-1">
                                    {assignment.description}
                                  </p>
                                </div>
                                <span className={`badge ml-2 ${
                                  assignment.type === 'quiz' ? 'badge-warning' : 'badge-primary'
                                }`}>
                                  {assignment.type === 'quiz' ? 'Quiz' : 'Bài tập về nhà'}
                                </span>
                              </div>
                              
                              <div className="flex items-center justify-between mt-4 text-sm text-gray-600">
                                <div className="flex items-center gap-4 flex-wrap">
                                  {assignment.start_date && (
                                    <div className="flex items-center">
                                      <FiCalendar className="mr-2" />
                                      <span>Bắt đầu: {formatDateTime(assignment.start_date)}</span>
                                    </div>
                                  )}
                                  <div className="flex items-center">
                                    <FiCalendar className="mr-2" />
                                    <span>
                                      Hạn nộp: {formatDateTime(assignment.deadline)}
                                    </span>
                                    {isPastDeadline && (
                                      <span className="ml-2 text-red-600 font-medium">(Đã quá hạn)</span>
                                    )}
                                  </div>
                                  <span>Điểm tối đa: {assignment.max_score}</span>
                                  {assignment.type === 'quiz' && assignment.time_limit && (
                                    <div className="flex items-center">
                                      <FiClock className="mr-2" />
                                      <span>Thời gian: {assignment.time_limit} phút</span>
                                    </div>
                                  )}
                                </div>
                              </div>

                              {/* Submission status */}
                              {isStudent && (
                                <div className="mt-4 pt-4 border-t border-gray-200">
                                  {assignment.type === 'quiz' ? (
                                    // Quiz UI
                                    submission ? (
                                      <div className="space-y-2">
                                        <div className="flex items-center justify-between">
                                          <div className="flex items-center gap-2">
                                            <FiCheck className="w-5 h-5 text-green-600" />
                                            <span className="text-sm font-medium text-green-600">
                                              Đã làm: {formatDateTime(submission.submitted_at || submission.submit_date)}
                                            </span>
                                          </div>
                                          <span className={`text-xs px-2 py-1 rounded ${
                                            submission.status === 'submitted' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'
                                          }`}>
                                            {submission.status === 'submitted' ? 'Đã hoàn thành' : 'Đang chấm'}
                                          </span>
                                        </div>
                                        {submission.score !== null && (
                                          <div className="bg-gray-50 rounded-lg p-3">
                                            <div className="flex items-center justify-between">
                                              <span className="text-sm font-medium text-gray-700">Điểm số:</span>
                                              <span className="text-lg font-bold text-primary-600">
                                                {submission.score.toFixed(2)}/{assignment.max_score}
                                              </span>
                                            </div>
                                            {submission.correct_count !== undefined && (
                                              <p className="text-xs text-gray-600 mt-1">
                                                Đúng: {submission.correct_count}/{submission.total_questions} câu
                                              </p>
                                            )}
                                          </div>
                                        )}
                                      </div>
                                    ) : !isPastDeadline ? (
                                      <button
                                        onClick={() => {
                                          setSelectedAssignment(assignment)
                                          setQuizModalOpen(true)
                                        }}
                                        className="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center justify-center gap-2"
                                      >
                                        <FiFileText className="w-4 h-4" />
                                        Làm bài quiz
                                      </button>
                                    ) : (
                                      <p className="text-sm text-red-600">Đã quá hạn làm bài</p>
                                    )
                                  ) : (
                                    // Homework UI
                                    submission ? (
                                      <div className="space-y-2">
                                        <div className="flex items-center justify-between">
                                          <div className="flex items-center gap-2">
                                            <FiCheck className="w-5 h-5 text-green-600" />
                                            <span className="text-sm font-medium text-green-600">
                                              Đã nộp: {formatDateTime(submission.submit_date)}
                                            </span>
                                          </div>
                                          <span className={`text-xs px-2 py-1 rounded ${
                                            submission.status === 'graded' ? 'bg-green-100 text-green-700' :
                                            submission.status === 'late' ? 'bg-red-100 text-red-700' :
                                            'bg-blue-100 text-blue-700'
                                          }`}>
                                            {submission.status === 'graded' ? 'Đã chấm' :
                                             submission.status === 'late' ? 'Nộp muộn' :
                                             'Đã nộp'}
                                          </span>
                                        </div>
                                        {submission.status === 'graded' && submission.score !== null && (
                                          <div className="bg-gray-50 rounded-lg p-3">
                                            <div className="flex items-center justify-between">
                                              <span className="text-sm font-medium text-gray-700">Điểm số:</span>
                                              <span className="text-lg font-bold text-primary-600">
                                                {submission.score}/{assignment.max_score}
                                              </span>
                                            </div>
                                            {submission.feedback && (
                                              <div className="mt-2">
                                                <span className="text-sm font-medium text-gray-700">Nhận xét:</span>
                                                <p className="text-sm text-gray-600 mt-1">{submission.feedback}</p>
                                              </div>
                                            )}
                                          </div>
                                        )}
                                        {submission.status !== 'graded' && !isPastDeadline && (
                                          <button
                                            onClick={() => {
                                              setSelectedAssignment(assignment)
                                              setSubmitModalOpen(true)
                                            }}
                                            className="mt-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm"
                                          >
                                            Cập nhật bài nộp
                                          </button>
                                        )}
                                      </div>
                                    ) : (
                                      <button
                                        onClick={() => {
                                          setSelectedAssignment(assignment)
                                          setSubmitModalOpen(true)
                                        }}
                                        className="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center justify-center gap-2"
                                      >
                                        <FiFileText className="w-4 h-4" />
                                        Nộp bài
                                      </button>
                                    )
                                  )}
                                </div>
                              )}
                            </motion.div>
                          )
                        })}
                        </motion.div>
                      )}
                    </div>
                  </div>
                )}

                {activeTab === 'reviews' && (
                  <div>
                    <h3 className="text-xl font-bold mb-4">Đánh giá từ học viên</h3>
                    
                    {/* Review Form for enrolled students only */}
                    {user && user.role === 'student' && isEnrolled && (
                      <div className="mb-6">
                        {showReviewForm || !myReview ? (
                          <ReviewForm
                            courseId={id}
                            studentId={user.id}
                            existingReview={myReview}
                            onSuccess={(reviewData) => {
                              setMyReview(reviewData)
                              setShowReviewForm(false)
                              handleReviewUpdate()
                            }}
                            onCancel={() => {
                              if (myReview) {
                                setShowReviewForm(false)
                              }
                            }}
                          />
                        ) : (
                          <div className="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
                            <div className="flex items-center justify-between">
                              <div>
                                <p className="font-semibold mb-2">Đánh giá của bạn</p>
                                <div className="flex items-center gap-2">
                                  {[1, 2, 3, 4, 5].map((star) => (
                                    <FiStar
                                      key={star}
                                      className={`w-5 h-5 ${
                                        star <= myReview.rating
                                          ? 'text-yellow-400 fill-yellow-400'
                                          : 'text-gray-300'
                                      }`}
                                    />
                                  ))}
                                  <span className="ml-2 text-gray-600">{myReview.rating} sao</span>
                                </div>
                                {myReview.comment && (
                                  <p className="text-gray-700 mt-2">{myReview.comment}</p>
                                )}
                              </div>
                              <button
                                onClick={() => setShowReviewForm(true)}
                                className="px-4 py-2 text-primary-600 border border-primary-600 rounded-lg hover:bg-primary-50 transition-colors"
                              >
                                Chỉnh sửa
                              </button>
                            </div>
                          </div>
                        )}
                      </div>
                    )}
                    
                    {/* Message for non-enrolled students */}
                    {user && user.role === 'student' && !isEnrolled && (
                      <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <p className="text-yellow-800">
                          Bạn cần đăng ký khóa học để có thể đánh giá.
                        </p>
                      </div>
                    )}
                    
                    {/* Reviews List */}
                    <ReviewList
                      courseId={id}
                      onReviewUpdate={handleReviewUpdate}
                    />
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Sidebar - Mobile/Tablet */}
          <div className="lg:hidden">
            <div className="bg-white rounded-xl shadow-sm p-6 sticky top-24">
              <div className="text-center mb-6">
                <div className="text-3xl font-bold text-gray-900 mb-2">
                  {formatPrice(course.price)}
                </div>
                {course.price === 0 && (
                  <span className="text-green-600 font-semibold">Miễn phí</span>
                )}
              </div>
              {/* Hiển thị nút chỉnh sửa cho teacher, nút đăng ký cho student (chỉ khi chưa đăng ký) */}
              {user && user.role === 'teacher' ? (
                <Link
                  to={`/teacher/courses/${id}/manage`}
                  className="w-full btn btn-primary py-3 text-lg font-semibold mb-4 flex items-center justify-center"
                >
                  <FiEdit className="inline mr-2" />
                  Chỉnh sửa khóa học
                </Link>
              ) : (
                <>
                  {!isEnrolled && (
                    <button
                      onClick={handleEnroll}
                      disabled={enrolling}
                      className="w-full btn btn-primary py-3 text-lg font-semibold mb-3"
                    >
                      {enrolling ? (
                        <span className="flex items-center justify-center">
                          <div className="spinner mr-2"></div>
                          Đang xử lý...
                        </span>
                      ) : (
                        <>
                          <FiPlay className="inline mr-2" />
                          {course.price === 0 ? 'Đăng ký miễn phí' : 'Mua khóa học'}
                        </>
                      )}
                    </button>
                  )}
                  {user && user.role === 'student' && isEnrolled && (
                    <Link
                      to={`/student/courses/${id}/performance`}
                      className="w-full btn bg-primary-600 text-white hover:bg-primary-700 py-2 text-sm mb-4 flex items-center justify-center"
                    >
                      <FiTrendingUp className="inline mr-2" />
                      Xem hiệu suất học tập
                    </Link>
                  )}
                </>
              )}
              <div className="space-y-2 text-sm text-gray-700">
                <div className="flex items-center">
                  <FiCheck className="mr-2 text-green-600" />
                  <span>Truy cập trọn đời</span>
                </div>
                <div className="flex items-center">
                  <FiCheck className="mr-2 text-green-600" />
                  <span>Chứng chỉ hoàn thành</span>
                </div>
                <div className="flex items-center">
                  <FiCheck className="mr-2 text-green-600" />
                  <span>Hỗ trợ 24/7</span>
                </div>
              </div>
            </div>
          </div>

          {/* Desktop Sidebar - Additional Info */}
          <div className="hidden lg:block space-y-6">
            <div className="bg-white rounded-xl shadow-sm p-6">
              <h3 className="font-semibold mb-4">Thông tin khóa học</h3>
              <div className="space-y-3 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-600">Trạng thái:</span>
                  <span className="font-medium capitalize">{course.status}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Ngày bắt đầu:</span>
                  <span className="font-medium">
                    {course.start_date
                      ? formatDate(course.start_date)
                      : 'Chưa xác định'}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Ngày kết thúc:</span>
                  <span className="font-medium">
                    {course.end_date
                      ? formatDate(course.end_date)
                      : 'Chưa xác định'}
                  </span>
                </div>
                {course.online_link && (
                  <div className="flex justify-between items-center pt-2 border-t border-gray-100">
                    <span className="text-gray-600">Link học online:</span>
                    <a
                      href={course.online_link}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="font-medium text-primary-600 hover:text-primary-700 flex items-center text-xs"
                    >
                      <FiVideo className="mr-1" />
                      Tham gia
                    </a>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
      
      {/* Chat Component - Chỉ hiển thị cho student */}
      {user && user.role === 'student' && (
        <CourseChat
          courseId={parseInt(id)}
          courseName={course.title}
          isOpen={chatOpen}
          onClose={() => setChatOpen(false)}
        />
      )}

      {/* Submit Assignment Modal */}
      {submitModalOpen && selectedAssignment && user && user.role === 'student' && selectedAssignment.type !== 'quiz' && (
        <SubmitAssignmentModal
          isOpen={submitModalOpen}
          onClose={() => {
            setSubmitModalOpen(false)
            setSelectedAssignment(null)
          }}
          onSuccess={(submission) => {
            // Update submissions state
            setSubmissions(prev => ({
              ...prev,
              [selectedAssignment.id]: submission
            }))
            // Reload assignments to get updated data
            loadCourseData()
          }}
          assignment={selectedAssignment}
          studentId={user.id}
        />
      )}

      {/* Quiz Take Modal */}
      {quizModalOpen && selectedAssignment && user && user.role === 'student' && selectedAssignment.type === 'quiz' && (
        <QuizTakeModal
          isOpen={quizModalOpen}
          onClose={() => {
            setQuizModalOpen(false)
            setSelectedAssignment(null)
          }}
          onSuccess={(submission) => {
            // Update submissions state
            setSubmissions(prev => ({
              ...prev,
              [selectedAssignment.id]: submission
            }))
            // Reload assignments to get updated data
            loadCourseData()
          }}
          assignment={selectedAssignment}
          studentId={user.id}
        />
      )}

    </div>
  )
}

export default CourseDetail

