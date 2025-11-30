import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { FiBook, FiFileText, FiTrendingUp, FiArrowRight } from 'react-icons/fi'
import { enrollmentsAPI, statsAPI } from '../../services/api'
import { useAuth } from '../../contexts/AuthContext'
import toast from 'react-hot-toast'

const StudentDashboard = () => {
  const { user } = useAuth()
  const [myCourses, setMyCourses] = useState([])
  const [stats, setStats] = useState({
    enrolledCourses: 0,
    completedLessons: 0,
    averageScore: 0,
  })
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (user?.id) {
      loadData()
    }
  }, [user?.id])

  const loadData = async () => {
    if (!user?.id) return
    
    try {
      setLoading(true)
      
      // Load courses
      const coursesRes = await enrollmentsAPI.getMyCourses(user.id)
      if (coursesRes.success && coursesRes.data) {
        const coursesList = Array.isArray(coursesRes.data) ? coursesRes.data : []
        setMyCourses(coursesList)
      }
      
      // Load stats
      const statsRes = await statsAPI.getStudentDashboard(user.id)
      if (statsRes.success && statsRes.data) {
        setStats({
          enrolledCourses: statsRes.data.enrolled_courses || 0,
          completedLessons: statsRes.data.completed_lessons || 0,
          averageScore: statsRes.data.average_score || 0,
        })
      } else {
        // Fallback: use courses count
        const coursesList = coursesRes.success && coursesRes.data ? (Array.isArray(coursesRes.data) ? coursesRes.data : []) : []
        setStats({
          enrolledCourses: coursesList.length,
          completedLessons: 0,
          averageScore: 0,
        })
      }
    } catch (error) {
      console.error('Error loading dashboard data:', error)
      toast.error('Không thể tải dữ liệu')
      // Set default values
      setStats({
        enrolledCourses: 0,
        completedLessons: 0,
        averageScore: 0,
      })
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-6 sm:py-8 px-3 sm:px-4">
        <div className="mb-6 sm:mb-8">
          <h1 className="text-2xl sm:text-3xl font-bold mb-1 sm:mb-2">Dashboard Học viên</h1>
          <p className="text-sm sm:text-base text-gray-600">Chào mừng trở lại, {user?.full_name || 'User'}!</p>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
          {[
            { title: 'Khóa học đã đăng ký', value: stats.enrolledCourses, icon: FiBook, color: 'primary' },
            { title: 'Bài học đã hoàn thành', value: stats.completedLessons, icon: FiFileText, color: 'secondary' },
            { title: 'Điểm trung bình', value: stats.averageScore.toFixed(1), icon: FiTrendingUp, color: 'green' },
          ].map((stat, index) => (
            <motion.div
              key={stat.title}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: index * 0.1 }}
              whileHover={{ y: -4, transition: { duration: 0.2 } }}
              className="card"
            >
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-gray-600 text-sm mb-1">{stat.title}</p>
                  <motion.p 
                    className="text-2xl font-bold"
                    initial={{ scale: 0 }}
                    animate={{ scale: 1 }}
                    transition={{ delay: index * 0.1 + 0.2, type: "spring", stiffness: 200 }}
                  >
                    {stat.value}
                  </motion.p>
                </div>
                <motion.div 
                  className={`w-12 h-12 rounded-lg bg-${stat.color}-100 flex items-center justify-center`}
                  whileHover={{ rotate: 360, scale: 1.1 }}
                  transition={{ duration: 0.5 }}
                >
                  <stat.icon className={`w-6 h-6 text-${stat.color}-600`} />
                </motion.div>
              </div>
            </motion.div>
          ))}
        </div>

        {/* My Courses */}
        <div className="card">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-xl font-semibold">Khóa học của tôi</h2>
            <Link to="/student/courses" className="text-primary-600 hover:text-primary-700 flex items-center">
              Xem tất cả <FiArrowRight className="ml-2" />
            </Link>
          </div>
          {loading ? (
            <div className="text-center py-12">
              <div className="spinner mx-auto"></div>
            </div>
          ) : myCourses.length === 0 ? (
            <div className="text-center py-12">
              <div className="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <FiBook className="w-10 h-10 text-primary-600" />
              </div>
              <p className="text-gray-600 mb-2 text-lg font-medium">Bạn chưa đăng ký khóa học nào</p>
              <p className="text-gray-500 text-sm mb-6">Bắt đầu hành trình học tập của bạn ngay hôm nay!</p>
              <Link to="/courses" className="btn btn-primary inline-flex items-center">
                <FiBook className="mr-2" />
                Khám phá khóa học
              </Link>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {myCourses.slice(0, 3).map((course) => (
                <Link
                  key={course.id}
                  to={`/courses/${course.id}/learn`}
                  className="card card-hover"
                >
                  <h3 className="font-semibold mb-2">{course.course?.title || course.course_name || 'Khóa học'}</h3>
                  <p className="text-sm text-gray-600 line-clamp-2">{course.course?.description || course.description || ''}</p>
                </Link>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default StudentDashboard

