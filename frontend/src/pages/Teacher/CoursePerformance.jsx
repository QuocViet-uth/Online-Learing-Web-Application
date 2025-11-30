import { useState, useEffect } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import { 
  FiArrowLeft, 
  FiUser, 
  FiBook, 
  FiFileText, 
  FiTrendingUp,
  FiClock,
  FiAward
} from 'react-icons/fi'
import { performanceAPI } from '../../services/api'
import toast from 'react-hot-toast'

const CoursePerformance = ({ courseId: propCourseId }) => {
  const { id: paramCourseId } = useParams()
  const courseId = propCourseId || paramCourseId
  const navigate = useNavigate()
  const [loading, setLoading] = useState(true)
  const [courseData, setCourseData] = useState(null)
  const [students, setStudents] = useState([])
  const [statistics, setStatistics] = useState(null)

  useEffect(() => {
    if (courseId) {
      loadPerformance()
    }
  }, [courseId])

  const loadPerformance = async () => {
    try {
      setLoading(true)
      const response = await performanceAPI.getByCourse(courseId)
      
      
      if (response && response.success && response.data) {
        setCourseData(response.data.course)
        setStatistics(response.data.statistics)
        // Ensure students array is properly formatted
        const studentsData = Array.isArray(response.data.students) ? response.data.students : []
        // Ensure all numeric fields are numbers
        const formattedStudents = studentsData.map(student => ({
          ...student,
          lessons: {
            ...student.lessons,
            completed: Number(student.lessons?.completed || 0),
            total: Number(student.lessons?.total || 0),
            progress_percent: Number(student.lessons?.progress_percent || 0)
          },
          assignments: {
            ...student.assignments,
            submitted: Number(student.assignments?.submitted || 0),
            total: Number(student.assignments?.total || 0),
            progress_percent: Number(student.assignments?.progress_percent || 0)
          },
          average_grade: student.average_grade !== null && student.average_grade !== undefined 
            ? Number(student.average_grade) 
            : null,
          overall_progress: Number(student.overall_progress || 0)
        }))
        setStudents(formattedStudents)
      } else {
        toast.error(response?.message || 'Không thể tải dữ liệu hiệu suất')
        setStudents([])
      }
    } catch (error) {
      console.error('Error loading performance:', error)
      toast.error(error?.response?.data?.message || error?.message || 'Không thể tải dữ liệu hiệu suất')
      setStudents([])
    } finally {
      setLoading(false)
    }
  }

  const getProgressColor = (progress) => {
    if (progress >= 80) return 'text-green-600 bg-green-50'
    if (progress >= 50) return 'text-yellow-600 bg-yellow-50'
    return 'text-red-600 bg-red-50'
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

  const getGradeColor = (grade) => {
    if (grade === null || grade === undefined) return 'text-gray-500'
    if (grade >= 8) return 'text-green-600 font-semibold'
    if (grade >= 6.5) return 'text-yellow-600 font-semibold'
    return 'text-red-600 font-semibold'
  }

  return (
    <div className="bg-gray-50 -mx-6 -mb-6">
      <div className="p-6">
        {/* Header */}
        {!propCourseId && (
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
                {courseData && (
                  <p className="text-gray-600">{courseData.title}</p>
                )}
              </div>
            </div>
          </div>
        )}
        
        {propCourseId && (
          <div className="mb-6">
            <h2 className="text-2xl font-bold mb-2">Hiệu suất học tập</h2>
            {courseData && (
              <p className="text-gray-600">{courseData.title}</p>
            )}
          </div>
        )}

        {loading ? (
          <div className="text-center py-12">
            <div className="spinner mx-auto"></div>
          </div>
        ) : (
          <>
            {/* Statistics Cards */}
            {statistics && (
              <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <motion.div
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="card"
                >
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm text-gray-600 mb-1">Tổng học viên</p>
                      <p className="text-2xl font-bold">{statistics.total_students}</p>
                    </div>
                    <FiUser className="w-8 h-8 text-primary-600" />
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
                      <p className="text-sm text-gray-600 mb-1">Tổng bài học</p>
                      <p className="text-2xl font-bold">{statistics.total_lessons}</p>
                    </div>
                    <FiBook className="w-8 h-8 text-blue-600" />
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
                      <p className="text-sm text-gray-600 mb-1">Tổng bài tập</p>
                      <p className="text-2xl font-bold">{statistics.total_assignments}</p>
                    </div>
                    <FiFileText className="w-8 h-8 text-purple-600" />
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
                      <p className="text-sm text-gray-600 mb-1">Tiến độ TB</p>
                      <p className="text-2xl font-bold">
                        {students.length > 0
                          ? Math.round(
                              students.reduce((sum, s) => sum + s.overall_progress, 0) /
                                students.length
                            )
                          : 0}
                        %
                      </p>
                    </div>
                    <FiTrendingUp className="w-8 h-8 text-green-600" />
                  </div>
                </motion.div>
              </div>
            )}

            {/* Performance Table */}
            <div className="card overflow-x-auto">
              {students.length === 0 ? (
                <div className="text-center py-12">
                  <FiUser className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                  <p className="text-gray-600">Chưa có học viên nào trong khóa học này</p>
                </div>
              ) : (
                <table className="w-full">
                  <thead>
                    <tr className="border-b border-gray-200">
                      <th className="text-left p-4 font-semibold">Học viên</th>
                      <th className="text-left p-4 font-semibold">Bài học</th>
                      <th className="text-left p-4 font-semibold">Bài tập</th>
                      <th className="text-left p-4 font-semibold">Điểm TB</th>
                      <th className="text-left p-4 font-semibold">Tiến độ</th>
                      <th className="text-left p-4 font-semibold">Hoạt động gần nhất</th>
                    </tr>
                  </thead>
                  <tbody>
                    {students.map((student, index) => (
                      <motion.tr
                        key={student.student_id}
                        initial={{ opacity: 0, x: -20 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ delay: index * 0.05 }}
                        className="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                      >
                        <td className="p-4">
                          <div className="flex items-center space-x-3">
                            <div className="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center overflow-hidden">
                              {student.avatar && student.avatar !== 'default-avatar.png' ? (
                                <img
                                  src={`/uploads/avatars/${student.avatar}`}
                                  alt={student.full_name || 'Student'}
                                  className="w-full h-full object-cover"
                                />
                              ) : (
                                <FiUser className="w-6 h-6 text-primary-600" />
                              )}
                            </div>
                            <div>
                              <p className="font-semibold">
                                {student.full_name || 'Student'}
                              </p>
                              <p className="text-sm text-gray-500">{student.email}</p>
                            </div>
                          </div>
                        </td>
                        <td className="p-4">
                          <div className="flex items-center space-x-2">
                            <div className="flex-1">
                              <div className="flex items-center justify-between mb-1">
                                <span className="text-sm text-gray-600">
                                  {student.lessons.completed} / {student.lessons.total}
                                </span>
                                <span className="text-sm font-semibold text-blue-600">
                                  {(student.lessons?.progress_percent || 0).toFixed(1)}%
                                </span>
                              </div>
                              <div className="w-full bg-gray-200 rounded-full h-2">
                                <div
                                  className="bg-blue-600 h-2 rounded-full transition-all"
                                  style={{ width: `${student.lessons?.progress_percent || 0}%` }}
                                ></div>
                              </div>
                            </div>
                          </div>
                        </td>
                        <td className="p-4">
                          <div className="flex items-center space-x-2">
                            <div className="flex-1">
                              <div className="flex items-center justify-between mb-1">
                                <span className="text-sm text-gray-600">
                                  {student.assignments.submitted} / {student.assignments.total}
                                </span>
                                <span className="text-sm font-semibold text-purple-600">
                                  {(student.assignments?.progress_percent || 0).toFixed(1)}%
                                </span>
                              </div>
                              <div className="w-full bg-gray-200 rounded-full h-2">
                                <div
                                  className="bg-purple-600 h-2 rounded-full transition-all"
                                  style={{ width: `${student.assignments?.progress_percent || 0}%` }}
                                ></div>
                              </div>
                            </div>
                          </div>
                        </td>
                        <td className="p-4">
                          <div className="flex items-center space-x-2">
                            {student.average_grade !== null ? (
                              <>
                                <FiAward className={`w-5 h-5 ${getGradeColor(student.average_grade)}`} />
                                <span className={`${getGradeColor(student.average_grade)}`}>
                                  {Number(student.average_grade).toFixed(1)}
                                </span>
                              </>
                            ) : (
                              <span className="text-gray-400">Chưa có điểm</span>
                            )}
                          </div>
                        </td>
                        <td className="p-4">
                          <div className="flex items-center space-x-2">
                            <div className="flex-1">
                              <div className="flex items-center justify-between mb-1">
                                <span className={`text-sm font-semibold ${getProgressColor(student.overall_progress || 0)} px-2 py-1 rounded`}>
                                  {(student.overall_progress || 0).toFixed(1)}%
                                </span>
                              </div>
                              <div className="w-full bg-gray-200 rounded-full h-2">
                                <div
                                  className={`h-2 rounded-full transition-all ${
                                    student.overall_progress >= 80
                                      ? 'bg-green-600'
                                      : student.overall_progress >= 50
                                      ? 'bg-yellow-600'
                                      : 'bg-red-600'
                                  }`}
                                  style={{ width: `${student.overall_progress || 0}%` }}
                                ></div>
                              </div>
                            </div>
                          </div>
                        </td>
                        <td className="p-4">
                          <div className="flex items-center space-x-2 text-sm text-gray-600">
                            <FiClock className="w-4 h-4" />
                            <span>{formatDate(student.last_activity)}</span>
                          </div>
                        </td>
                      </motion.tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          </>
        )}
      </div>
    </div>
  )
}

export default CoursePerformance

