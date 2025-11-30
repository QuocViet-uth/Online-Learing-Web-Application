import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { FiBook, FiUsers, FiFileText, FiTrendingUp, FiArrowRight } from 'react-icons/fi'
import { coursesAPI, statsAPI } from '../../services/api'
import { useAuth } from '../../contexts/AuthContext'
import toast from 'react-hot-toast'

const TeacherDashboard = () => {
  const { user } = useAuth()
  const [stats, setStats] = useState({
    totalCourses: 0,
    totalStudents: 0,
    totalAssignments: 0,
  })
  const [courses, setCourses] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (user?.id) {
      loadData()
    }
  }, [user?.id])

  const loadData = async () => {
    if (!user?.id) {
      setLoading(false)
      return
    }
    
    try {
      setLoading(true)
      
      // Load courses
      const coursesResponse = await coursesAPI.getAll({ teacher_id: user.id })
      if (coursesResponse && coursesResponse.success !== false) {
        const coursesData = coursesResponse.data || []
        setCourses(Array.isArray(coursesData) ? coursesData : [])
      }
      
      // Load stats
      const statsResponse = await statsAPI.getTeacherDashboard(user.id)
      if (statsResponse.success && statsResponse.data) {
        setStats({
          totalCourses: statsResponse.data.total_courses || 0,
          totalStudents: statsResponse.data.total_students || 0,
          totalAssignments: statsResponse.data.total_assignments || 0,
        })
      } else {
        // Fallback: use courses count
        const coursesData = coursesResponse && coursesResponse.data ? (Array.isArray(coursesResponse.data) ? coursesResponse.data : []) : []
        setStats({
          totalCourses: coursesData.length,
          totalStudents: 0,
          totalAssignments: 0,
        })
      }
    } catch (error) {
      console.error('Error loading data:', error)
      toast.error('Không thể tải dữ liệu')
      setCourses([])
      setStats({
        totalCourses: 0,
        totalStudents: 0,
        totalAssignments: 0,
      })
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold mb-2">Dashboard Giảng viên</h1>
          <p className="text-gray-600">Chào mừng trở lại, {user?.full_name || 'User'}!</p>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          {[
            { title: 'Khóa học của tôi', value: stats.totalCourses, icon: FiBook, color: 'primary' },
            { title: 'Học viên', value: stats.totalStudents, icon: FiUsers, color: 'secondary' },
            { title: 'Bài tập', value: stats.totalAssignments, icon: FiFileText, color: 'green' },
          ].map((stat, index) => (
            <motion.div
              key={stat.title}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: index * 0.1 }}
              className="card"
            >
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-gray-600 text-sm mb-1">{stat.title}</p>
                  <p className="text-2xl font-bold">{stat.value}</p>
                </div>
                <div className={`w-12 h-12 rounded-lg bg-${stat.color}-100 flex items-center justify-center`}>
                  <stat.icon className={`w-6 h-6 text-${stat.color}-600`} />
                </div>
              </div>
            </motion.div>
          ))}
        </div>

        {/* My Courses */}
        <div className="card">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-xl font-semibold">Khóa học của tôi</h2>
            <Link to="/teacher/courses" className="text-primary-600 hover:text-primary-700 flex items-center">
              Xem tất cả <FiArrowRight className="ml-2" />
            </Link>
          </div>
          {loading ? (
            <div className="text-center py-12">
              <div className="spinner mx-auto"></div>
            </div>
          ) : courses.length === 0 ? (
            <div className="text-center py-12">
              <div className="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <FiBook className="w-10 h-10 text-primary-600" />
              </div>
              <p className="text-gray-600 mb-2 text-lg font-medium">Bạn chưa có khóa học nào</p>
              <p className="text-gray-500 text-sm mb-6">Tạo khóa học đầu tiên của bạn và bắt đầu chia sẻ kiến thức!</p>
              <Link to="/teacher/courses" className="btn btn-primary inline-flex items-center">
                <FiBook className="mr-2" />
                Tạo khóa học mới
              </Link>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {courses.slice(0, 3).map((course) => (
                <Link
                  key={course.id}
                  to={`/teacher/courses/${course.id}/manage`}
                  className="card card-hover"
                >
                  <h3 className="font-semibold mb-2">{course.title}</h3>
                  <p className="text-sm text-gray-600 line-clamp-2">{course.description}</p>
                </Link>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default TeacherDashboard

