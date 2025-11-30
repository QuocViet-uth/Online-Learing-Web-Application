import { useState, useEffect } from 'react'
import { motion } from 'framer-motion'
import { FiBook, FiCheckCircle, FiClock } from 'react-icons/fi'
import { progressAPI, enrollmentsAPI } from '../../services/api'
import { useAuth } from '../../contexts/AuthContext'
import toast from 'react-hot-toast'

const StudentProgress = () => {
  const { user } = useAuth()
  const [courses, setCourses] = useState([])
  const [progressData, setProgressData] = useState({})
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (user?.id) {
      loadProgress()
    }
  }, [user?.id])

  const loadProgress = async () => {
    if (!user?.id) return
    
    try {
      setLoading(true)
      // Lấy danh sách khóa học
      const response = await enrollmentsAPI.getMyCourses(user.id)
      if (response.success && response.data) {
        setCourses(response.data)
        
        // Lấy performance data cho từng khóa học
        const progressPromises = response.data.map(async (enrollment) => {
          const courseId = enrollment.course_id || enrollment.course?.id
          if (!courseId) return null
          
          try {
            const perfResponse = await progressAPI.getStudentCoursePerformance(user.id, courseId)
            if (perfResponse.success && perfResponse.data) {
              return { courseId, data: perfResponse.data }
            }
          } catch (error) {
            console.error(`Error loading progress for course ${courseId}:`, error)
          }
          return null
        })
        
        const progressResults = await Promise.all(progressPromises)
        const progressMap = {}
        progressResults.forEach((result) => {
          if (result) {
            progressMap[result.courseId] = result.data
          }
        })
        setProgressData(progressMap)
      }
    } catch (error) {
      toast.error('Không thể tải tiến độ học tập')
    } finally {
      setLoading(false)
    }
  }

  // Tính tổng thời gian học từ các lessons đã hoàn thành
  const calculateTotalDuration = (lessons) => {
    if (!lessons || !Array.isArray(lessons)) return 0
    return lessons
      .filter(lesson => lesson.is_completed)
      .reduce((total, lesson) => total + (lesson.duration || 0), 0)
  }

  // Format thời gian (phút -> giờ phút)
  const formatDuration = (minutes) => {
    if (!minutes || minutes === 0) return '0 phút'
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    if (hours > 0) {
      return mins > 0 ? `${hours} giờ ${mins} phút` : `${hours} giờ`
    }
    return `${mins} phút`
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold mb-2">Tiến độ học tập</h1>
          <p className="text-gray-600">Theo dõi tiến độ học tập của bạn</p>
        </div>

        {loading ? (
          <div className="card text-center py-12">
            <div className="spinner mx-auto"></div>
          </div>
        ) : courses.length === 0 ? (
          <div className="card text-center py-12 text-gray-600">
            <FiBook className="w-16 h-16 mx-auto mb-4 text-gray-300" />
            <p>Chưa có khóa học nào</p>
          </div>
        ) : (
          <div className="space-y-6">
            {courses.map((enrollment) => {
              const course = enrollment.course || {}
              const courseId = enrollment.course_id || course.id
              const courseName = course.course_name || course.title || 'Khóa học'
              const progress = progressData[courseId]
              
              // Lấy dữ liệu tiến độ
              const overallProgress = progress?.statistics?.overall_progress || 0
              const completedLessons = progress?.statistics?.lessons?.completed || 0
              const totalLessons = progress?.statistics?.lessons?.total || 0
              const lessons = progress?.lessons || []
              const totalDuration = calculateTotalDuration(lessons)
              
              return (
                <motion.div
                  key={enrollment.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="card"
                >
                  <div className="flex items-center justify-between mb-4">
                    <h3 className="text-xl font-semibold">{courseName}</h3>
                    <span className="badge badge-primary">Đang học</span>
                  </div>
                  <div className="mb-4">
                    <div className="flex items-center justify-between text-sm text-gray-600 mb-2">
                      <span>Tiến độ</span>
                      <span>{overallProgress.toFixed(0)}%</span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2">
                      <div
                        className="bg-primary-600 h-2 rounded-full transition-all duration-300"
                        style={{ width: `${overallProgress}%` }}
                      ></div>
                    </div>
                  </div>
                  <div className="flex items-center space-x-6 text-sm text-gray-600">
                    <span className="flex items-center">
                      <FiCheckCircle className="mr-2 text-green-600" />
                      {completedLessons}/{totalLessons} bài học
                    </span>
                    {totalDuration > 0 && (
                      <span className="flex items-center">
                        <FiClock className="mr-2" />
                        {formatDuration(totalDuration)}
                      </span>
                    )}
                  </div>
                </motion.div>
              )
            })}
          </div>
        )}
      </div>
    </div>
  )
}

export default StudentProgress

