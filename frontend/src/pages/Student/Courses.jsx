import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { FiPlay, FiClock } from 'react-icons/fi'
import { enrollmentsAPI } from '../../services/api'
import { useAuth } from '../../contexts/AuthContext'
import toast from 'react-hot-toast'

const StudentCourses = () => {
  const { user } = useAuth()
  const [courses, setCourses] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (user?.id) {
      loadCourses()
    }
  }, [user?.id])

  const loadCourses = async () => {
    if (!user?.id) return
    
    try {
      setLoading(true)
      const response = await enrollmentsAPI.getMyCourses(user.id)
      if (response.success && response.data) {
        setCourses(response.data)
      }
    } catch (error) {
      toast.error('Không thể tải danh sách khóa học')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold mb-2">Khóa học của tôi</h1>
          <p className="text-gray-600">Tiếp tục học tập</p>
        </div>

        {loading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {[1, 2, 3].map((i) => (
              <div key={i} className="card animate-pulse">
                <div className="h-48 bg-gray-200 rounded-lg mb-4"></div>
                <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                <div className="h-4 bg-gray-200 rounded w-1/2"></div>
              </div>
            ))}
          </div>
        ) : courses.length === 0 ? (
          <div className="card text-center py-12">
            <p className="text-gray-600 mb-4">Bạn chưa đăng ký khóa học nào</p>
            <Link to="/courses" className="btn btn-primary">
              Khám phá khóa học
            </Link>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {courses.map((enrollment) => {
              const course = enrollment.course || {}
              const courseId = enrollment.course_id || course.id
              const courseName = course.course_name || course.title || 'Khóa học'
              const thumbnail = course.thumbnail
              
              return (
                <motion.div
                  key={enrollment.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="card card-hover"
                >
                  <Link to={`/courses/${courseId}`}>
                    <div className="relative h-48 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-lg mb-4 overflow-hidden">
                      {thumbnail ? (
                        <img
                          src={thumbnail}
                          alt={courseName}
                          className="w-full h-full object-cover"
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center text-white text-4xl font-bold">
                          {courseName.charAt(0).toUpperCase()}
                        </div>
                      )}
                      <div className="absolute inset-0 bg-black/20 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                        <div className="w-16 h-16 bg-white/90 rounded-full flex items-center justify-center">
                          <FiPlay className="w-8 h-8 text-primary-600 ml-1" />
                        </div>
                      </div>
                    </div>
                    <h3 className="text-xl font-semibold mb-2">{courseName}</h3>
                    <p className="text-gray-600 text-sm mb-4 line-clamp-2">{course.title || ''}</p>
                    <div className="flex items-center justify-between pt-4 border-t border-gray-200">
                      <span className="text-sm text-gray-600 flex items-center">
                        <FiClock className="mr-1" />
                        Tiếp tục học
                      </span>
                      <span className="badge badge-success">Đang học</span>
                    </div>
                  </Link>
                </motion.div>
              )
            })}
          </div>
        )}
      </div>
    </div>
  )
}

export default StudentCourses

