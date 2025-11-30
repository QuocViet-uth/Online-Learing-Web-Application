import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { FiPlus, FiEdit, FiTrash2 } from 'react-icons/fi'
import { coursesAPI } from '../../services/api'
import { useAuth } from '../../contexts/AuthContext'
import toast from 'react-hot-toast'
import CreateCourseModal from '../../components/Course/CreateCourseModal'
import EditCourseModal from '../../components/Course/EditCourseModal'

const TeacherCourses = () => {
  const { user } = useAuth()
  const [courses, setCourses] = useState([])
  const [loading, setLoading] = useState(true)
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [showEditModal, setShowEditModal] = useState(false)
  const [selectedCourse, setSelectedCourse] = useState(null)
  const [deleteConfirm, setDeleteConfirm] = useState(null)
  
  useEffect(() => {
    if (user?.id) {
      loadCourses()
    }
  }, [user?.id])

  const loadCourses = async () => {
    if (!user?.id) {
      setLoading(false)
      return
    }
    
    try {
      setLoading(true)
      const response = await coursesAPI.getAll({ teacher_id: user.id })
      
      if (response && response.success !== false) {
        const coursesData = response.data || []
        setCourses(Array.isArray(coursesData) ? coursesData : [])
      } else {
        console.warn('API returned unsuccessful response:', response)
        setCourses([])
        if (response && response.message) {
          toast.error(response.message)
        }
      }
    } catch (error) {
      console.error('Error loading courses:', error)
      console.error('Error details:', error.response || error.message)
      toast.error('Không thể tải danh sách khóa học: ' + (error.message || 'Unknown error'))
      setCourses([])
    } finally {
      setLoading(false)
    }
  }

  const handleEdit = (course) => {
    setSelectedCourse(course)
    setShowEditModal(true)
  }

  const handleDelete = async (courseId) => {
    if (!window.confirm('Bạn có chắc chắn muốn xóa khóa học này? Hành động này không thể hoàn tác.')) {
      return
    }

    try {
      const response = await coursesAPI.delete(courseId)
      if (response && response.success) {
        toast.success('Xóa khóa học thành công!')
        // Reload danh sách
        loadCourses()
      } else {
        toast.error(response?.message || 'Không thể xóa khóa học')
      }
    } catch (error) {
      console.error('Error deleting course:', error)
      const errorMessage = error.response?.data?.message || error.message || 'Có lỗi xảy ra khi xóa khóa học'
      toast.error(errorMessage)
    }
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold mb-2">Khóa học của tôi</h1>
            <p className="text-gray-600">Quản lý các khóa học bạn đã tạo</p>
          </div>
          <button 
            onClick={() => setShowCreateModal(true)}
            className="btn btn-primary flex items-center"
          >
            <FiPlus className="mr-2" />
            Tạo khóa học mới
          </button>
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
            <p className="text-gray-600 mb-4">Bạn chưa có khóa học nào</p>
            <button 
              onClick={() => setShowCreateModal(true)}
              className="btn btn-primary"
            >
              <FiPlus className="mr-2" />
              Tạo khóa học đầu tiên
            </button>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {courses.map((course) => (
              <motion.div
                key={course.id}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="card"
              >
                <Link to={`/teacher/courses/${course.id}/manage`}>
                  <div className="relative h-48 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-lg mb-4 overflow-hidden">
                    {course.thumbnail ? (
                      <img
                        src={course.thumbnail}
                        alt={course.course_name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-white text-4xl font-bold">
                        {course.course_name.charAt(0)}
                      </div>
                    )}
                  </div>
                </Link>
                <h3 className="text-xl font-semibold mb-2">{course.title}</h3>
                <p className="text-gray-600 text-sm mb-4 line-clamp-2">{course.description}</p>
                <div className="flex items-center justify-between pt-4 border-t border-gray-200">
                  <span className={`badge ${
                    course.status === 'active' ? 'badge-success' :
                    course.status === 'upcoming' ? 'badge-warning' :
                    'badge-danger'
                  }`}>
                    {course.status}
                  </span>
                  <div className="flex space-x-2">
                    <button
                      onClick={(e) => {
                        e.preventDefault()
                        handleEdit(course)
                      }}
                      className="p-2 text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                      title="Chỉnh sửa"
                    >
                      <FiEdit className="w-5 h-5" />
                    </button>
                    <button
                      onClick={(e) => {
                        e.preventDefault()
                        handleDelete(course.id)
                      }}
                      className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                      title="Xóa"
                    >
                      <FiTrash2 className="w-5 h-5" />
                    </button>
                  </div>
                </div>
              </motion.div>
            ))}
          </div>
        )}

        <CreateCourseModal
          isOpen={showCreateModal}
          onClose={() => setShowCreateModal(false)}
          onSuccess={(newCourse) => {
            // Reload danh sách courses
            loadCourses()
          }}
          teacherId={user?.id}
        />

        <EditCourseModal
          isOpen={showEditModal}
          onClose={() => {
            setShowEditModal(false)
            setSelectedCourse(null)
          }}
          onSuccess={(updatedCourse) => {
            // Reload danh sách courses
            loadCourses()
          }}
          course={selectedCourse}
          teacherId={user?.id}
        />
      </div>
    </div>
  )
}

export default TeacherCourses

