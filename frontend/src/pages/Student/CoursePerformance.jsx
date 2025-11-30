import { useState, useEffect } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import { 
  FiArrowLeft, 
  FiBook, 
  FiFileText, 
  FiTrendingUp,
  FiClock,
  FiAward,
  FiCheckCircle,
  FiXCircle,
  FiAlertCircle,
  FiUser
} from 'react-icons/fi'
import { progressAPI } from '../../services/api'
import { useAuth } from '../../contexts/AuthContext'
import toast from 'react-hot-toast'

const StudentCoursePerformance = () => {
  const { id: courseId } = useParams()
  const navigate = useNavigate()
  const { user } = useAuth()
  const [loading, setLoading] = useState(true)
  const [performanceData, setPerformanceData] = useState(null)

  useEffect(() => {
    if (courseId && user && user.id) {
      loadPerformance()
    }
  }, [courseId, user])

  const loadPerformance = async () => {
    // Kiểm tra xem user có phải student không
    if (!user || user.role !== 'student') {
      toast.error('Chỉ học viên mới có thể xem hiệu suất học tập')
      navigate(-1)
      return
    }

    try {
      setLoading(true)
      const response = await progressAPI.getStudentCoursePerformance(user.id, parseInt(courseId))
      
      if (response && response.success && response.data) {
        setPerformanceData(response.data)
      } else {
        toast.error(response?.message || 'Không thể tải dữ liệu hiệu suất')
        setPerformanceData(null)
      }
    } catch (error) {
      console.error('Error loading performance:', error)
      toast.error('Không thể tải dữ liệu hiệu suất')
      setPerformanceData(null)
    } finally {
      setLoading(false)
    }
  }

  const getProgressColor = (progress) => {
    if (progress >= 80) return 'text-green-600 bg-green-50'
    if (progress >= 50) return 'text-yellow-600 bg-yellow-50'
    return 'text-red-600 bg-red-50'
  }

  const getGradeColor = (grade, maxGrade) => {
    if (grade === null || grade === undefined) return 'text-gray-500'
    const percentage = (grade / maxGrade) * 100
    if (percentage >= 80) return 'text-green-600 font-semibold'
    if (percentage >= 60) return 'text-yellow-600 font-semibold'
    return 'text-red-600 font-semibold'
  }

  const formatDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('vi-VN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  if (loading) {
    return (
      <div className="pt-16 md:pt-20 min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="spinner"></div>
      </div>
    )
  }

  if (!performanceData) {
    return (
      <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
        <div className="container-custom py-8">
          <div className="text-center py-12">
            <p className="text-gray-600">Không thể tải dữ liệu hiệu suất</p>
            <button
              onClick={() => navigate(-1)}
              className="mt-4 btn btn-primary"
            >
              Quay lại
            </button>
          </div>
        </div>
      </div>
    )
  }

  const { course, enrollment, statistics, lessons, assignments } = performanceData

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        {/* Header */}
        <div className="flex items-center justify-between mb-6">
          <div className="flex items-center space-x-4">
            <button
              onClick={() => navigate(-1)}
              className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <FiArrowLeft className="w-6 h-6" />
            </button>
            <div>
              <h1 className="text-3xl font-bold mb-2">Hiệu suất học tập</h1>
              <p className="text-gray-600">{course.title}</p>
            </div>
          </div>
        </div>

        {/* Statistics Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="card"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600 mb-1">Tiến độ tổng thể</p>
                <p className={`text-3xl font-bold ${getProgressColor(statistics.overall_progress)} px-3 py-2 rounded-lg`}>
                  {statistics.overall_progress.toFixed(1)}%
                </p>
              </div>
              <FiTrendingUp className="w-10 h-10 text-primary-600" />
            </div>
            <div className="mt-4 w-full bg-gray-200 rounded-full h-2">
              <div
                className={`h-2 rounded-full transition-all ${
                  statistics.overall_progress >= 80
                    ? 'bg-green-600'
                    : statistics.overall_progress >= 50
                    ? 'bg-yellow-600'
                    : 'bg-red-600'
                }`}
                style={{ width: `${statistics.overall_progress}%` }}
              ></div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="card"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600 mb-1">Bài học</p>
                <p className="text-2xl font-bold">
                  {statistics.lessons.completed} / {statistics.lessons.total}
                </p>
                <p className="text-sm text-blue-600 font-semibold mt-1">
                  {statistics.lessons.progress_percent.toFixed(1)}%
                </p>
              </div>
              <FiBook className="w-10 h-10 text-blue-600" />
            </div>
            <div className="mt-4 w-full bg-gray-200 rounded-full h-2">
              <div
                className="bg-blue-600 h-2 rounded-full transition-all"
                style={{ width: `${statistics.lessons.progress_percent}%` }}
              ></div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="card"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600 mb-1">Bài tập</p>
                <p className="text-2xl font-bold">
                  {statistics.assignments.submitted} / {statistics.assignments.total}
                </p>
                <p className="text-sm text-purple-600 font-semibold mt-1">
                  {statistics.assignments.progress_percent.toFixed(1)}%
                </p>
              </div>
              <FiFileText className="w-10 h-10 text-purple-600" />
            </div>
            <div className="mt-4 w-full bg-gray-200 rounded-full h-2">
              <div
                className="bg-purple-600 h-2 rounded-full transition-all"
                style={{ width: `${statistics.assignments.progress_percent}%` }}
              ></div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            className="card"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600 mb-1">Điểm trung bình</p>
                {statistics.average_grade !== null ? (
                  <p className={`text-3xl font-bold ${getGradeColor(statistics.average_grade, 10)}`}>
                    {statistics.average_grade.toFixed(1)}
                  </p>
                ) : (
                  <p className="text-2xl font-bold text-gray-400">Chưa có</p>
                )}
              </div>
              <FiAward className="w-10 h-10 text-yellow-600" />
            </div>
          </motion.div>
        </div>

        {/* Lessons Progress */}
        <div className="card mb-6">
          <h2 className="text-xl font-bold mb-4 flex items-center">
            <FiBook className="mr-2" />
            Tiến độ bài học
          </h2>
          {lessons.length === 0 ? (
            <p className="text-gray-600 text-center py-8">Chưa có bài học nào</p>
          ) : (
            <div className="space-y-2">
              {lessons.map((lesson, index) => (
                <div
                  key={lesson.id}
                  className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  <div className="flex items-center space-x-4 flex-1">
                    <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold ${
                      lesson.is_completed ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'
                    }`}>
                      {index + 1}
                    </div>
                    <div className="flex-1">
                      <p className="font-medium">{lesson.title}</p>
                      <div className="flex items-center space-x-4 text-sm text-gray-600 mt-1">
                        <span>{lesson.duration || 0} phút</span>
                        {lesson.last_accessed && (
                          <span className="flex items-center">
                            <FiClock className="w-3 h-3 mr-1" />
                            {formatDate(lesson.last_accessed)}
                          </span>
                        )}
                      </div>
                    </div>
                  </div>
                  <div>
                    {lesson.is_completed ? (
                      <FiCheckCircle className="w-6 h-6 text-green-600" />
                    ) : (
                      <FiXCircle className="w-6 h-6 text-gray-400" />
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Assignments Progress */}
        <div className="card">
          <h2 className="text-xl font-bold mb-4 flex items-center">
            <FiFileText className="mr-2" />
            Tiến độ bài tập
          </h2>
          {assignments.length === 0 ? (
            <p className="text-gray-600 text-center py-8">Chưa có bài tập nào</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th className="text-left p-4 font-semibold">Bài tập</th>
                    <th className="text-left p-4 font-semibold">Hạn nộp</th>
                    <th className="text-left p-4 font-semibold">Trạng thái</th>
                    <th className="text-left p-4 font-semibold">Điểm số</th>
                  </tr>
                </thead>
                <tbody>
                  {assignments.map((assignment) => (
                    <tr
                      key={assignment.id}
                      className="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                    >
                      <td className="p-4">
                        <p className="font-medium">{assignment.title}</p>
                        <p className="text-sm text-gray-500">Điểm tối đa: {assignment.max_score}</p>
                      </td>
                      <td className="p-4">
                        <div className="flex items-center space-x-2 text-sm text-gray-600">
                          <FiClock className="w-4 h-4" />
                          <span>{formatDate(assignment.deadline)}</span>
                        </div>
                      </td>
                      <td className="p-4">
                        {assignment.submission_id ? (
                          assignment.submission_status === 'graded' ? (
                            <span className="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 flex items-center gap-1 w-fit">
                              <FiCheckCircle className="w-3 h-3" />
                              Đã chấm
                            </span>
                          ) : assignment.submission_status === 'late' ? (
                            <span className="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 flex items-center gap-1 w-fit">
                              <FiAlertCircle className="w-3 h-3" />
                              Nộp muộn
                            </span>
                          ) : (
                            <span className="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 flex items-center gap-1 w-fit">
                              <FiFileText className="w-3 h-3" />
                              Đã nộp
                            </span>
                          )
                        ) : (
                          <span className="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                            Chưa nộp
                          </span>
                        )}
                      </td>
                      <td className="p-4">
                        {assignment.score !== null ? (
                          <div className="flex items-center space-x-2">
                            <FiAward className={`w-5 h-5 ${getGradeColor(assignment.score, assignment.max_score)}`} />
                            <span className={getGradeColor(assignment.score, assignment.max_score)}>
                              {assignment.score.toFixed(1)} / {assignment.max_score}
                            </span>
                          </div>
                        ) : (
                          <span className="text-gray-400">-</span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default StudentCoursePerformance

