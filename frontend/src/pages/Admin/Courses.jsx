import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { FiSearch, FiPlus, FiEdit, FiTrash2, FiUsers, FiFileText, FiBook } from 'react-icons/fi'
import { coursesAPI } from '../../services/api'
import toast from 'react-hot-toast'
import CreateCourseModal from '../../components/Course/CreateCourseModal'
import EditCourseModal from '../../components/Course/EditCourseModal'

const AdminCourses = () => {
  const [courses, setCourses] = useState([])
  const [loading, setLoading] = useState(true)
  const [searchQuery, setSearchQuery] = useState('')
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [showEditModal, setShowEditModal] = useState(false)
  const [selectedCourse, setSelectedCourse] = useState(null)

  useEffect(() => {
    loadCourses()
  }, [])

  const loadCourses = async () => {
    try {
      setLoading(true)
      const response = await coursesAPI.getAll()
      if (response && response.success !== false) {
        const coursesData = response.data || []
        setCourses(Array.isArray(coursesData) ? coursesData : [])
      }
    } catch (error) {
      console.error('Error loading courses:', error)
      toast.error('Không thể tải danh sách khóa học')
      setCourses([])
    } finally {
      setLoading(false)
    }
  }

  const handleCreateSuccess = () => {
    setShowCreateModal(false)
    loadCourses()
    toast.success('Tạo khóa học thành công!')
  }

  const handleEdit = (course) => {
    setSelectedCourse(course)
    setShowEditModal(true)
  }

  const handleEditSuccess = () => {
    setShowEditModal(false)
    setSelectedCourse(null)
    loadCourses()
    toast.success('Cập nhật khóa học thành công!')
  }

  const handleDelete = async (courseId) => {
    if (!window.confirm('Bạn có chắc chắn muốn xóa khóa học này? Hành động này không thể hoàn tác.')) {
      return
    }

    try {
      const response = await coursesAPI.delete(courseId)
      if (response && response.success) {
        toast.success('Xóa khóa học thành công!')
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

  const filteredCourses = courses.filter(
    (course) =>
      course.course_name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      course.title?.toLowerCase().includes(searchQuery.toLowerCase())
  )

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold mb-2">Quản lý khóa học</h1>
            <p className="text-gray-600">Quản lý tất cả khóa học trong hệ thống</p>
          </div>
          <button 
            onClick={() => setShowCreateModal(true)}
            className="btn btn-primary flex items-center"
          >
            <FiPlus className="mr-2" />
            Thêm khóa học
          </button>
        </div>

        {/* Search */}
        <div className="card mb-6">
          <div className="relative">
            <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Tìm kiếm khóa học..."
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
        </div>

        {/* Courses Grid */}
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
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredCourses.map((course) => (
              <motion.div
                key={course.id}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="card"
              >
                <Link to={`/admin/courses/${course.id}/manage`}>
                  <div className="relative h-48 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-lg mb-4 overflow-hidden">
                    {course.thumbnail ? (
                      <img
                        src={course.thumbnail}
                        alt={course.course_name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-white text-4xl font-bold">
                        {course.course_name?.charAt(0) || 'C'}
                      </div>
                    )}
                  </div>
                </Link>
                <h3 className="text-xl font-semibold mb-2">{course.title || course.course_name}</h3>
                <p className="text-gray-600 text-sm mb-4 line-clamp-2">{course.description || 'Không có mô tả'}</p>
                
                {/* Thông tin chi tiết */}
                <div className="space-y-2 mb-4 text-sm">
                  {course.price !== undefined && course.price !== null && (
                    <div className="flex items-center justify-between">
                      <span className="text-gray-600">Giá:</span>
                      <span className="font-semibold text-primary-600">
                        {new Intl.NumberFormat('vi-VN', {
                          style: 'currency',
                          currency: 'VND'
                        }).format(course.price)}
                      </span>
                    </div>
                  )}
                  <div className="grid grid-cols-2 gap-2 pt-2 border-t border-gray-100">
                    {course.lessons && (
                      <div className="flex items-center space-x-1">
                        <FiBook className="w-4 h-4 text-gray-400" />
                        <span className="text-gray-600 text-xs">Bài học:</span>
                        <span className="font-semibold text-xs">{course.lessons.total || 0}</span>
                      </div>
                    )}
                    {course.assignments_count !== undefined && (
                      <div className="flex items-center space-x-1">
                        <FiFileText className="w-4 h-4 text-gray-400" />
                        <span className="text-gray-600 text-xs">Bài tập:</span>
                        <span className="font-semibold text-xs">{course.assignments_count || 0}</span>
                      </div>
                    )}
                    {course.enrollments_count !== undefined && (
                      <div className="flex items-center space-x-1">
                        <FiUsers className="w-4 h-4 text-gray-400" />
                        <span className="text-gray-600 text-xs">Học viên:</span>
                        <span className="font-semibold text-xs">{course.enrollments_count || 0}</span>
                      </div>
                    )}
                    {course.lessons && course.lessons.total_duration > 0 && (
                      <div className="flex items-center space-x-1">
                        <span className="text-gray-600 text-xs">Thời lượng:</span>
                        <span className="font-semibold text-xs">{course.lessons.total_duration} phút</span>
                      </div>
                    )}
                  </div>
                  {(course.start_date || course.end_date) && (
                    <div className="pt-2 border-t border-gray-100 space-y-1">
                      {course.start_date && (
                        <div className="flex items-center justify-between text-xs">
                          <span className="text-gray-600">Bắt đầu:</span>
                          <span className="font-semibold">
                            {new Date(course.start_date).toLocaleDateString('vi-VN')}
                          </span>
                        </div>
                      )}
                      {course.end_date && (
                        <div className="flex items-center justify-between text-xs">
                          <span className="text-gray-600">Kết thúc:</span>
                          <span className="font-semibold">
                            {new Date(course.end_date).toLocaleDateString('vi-VN')}
                          </span>
                        </div>
                      )}
                    </div>
                  )}
                </div>

                <div className="flex items-center justify-between pt-4 border-t border-gray-200">
                  <div className="flex flex-col space-y-1">
                    <span className={`badge ${
                      course.status === 'active' ? 'badge-success' :
                      course.status === 'upcoming' ? 'badge-warning' :
                      'badge-danger'
                    }`}>
                      {course.status === 'active' ? 'Đang hoạt động' :
                       course.status === 'upcoming' ? 'Sắp mở' :
                       'Đã đóng'}
                    </span>
                    {course.teacher_name && (
                      <span className="text-xs text-gray-500">GV: {course.teacher_name}</span>
                    )}
                  </div>
                  <div className="flex space-x-2">
                    <button 
                      onClick={(e) => {
                        e.preventDefault()
                        handleEdit(course)
                      }}
                      className="p-2 text-primary-600 hover:bg-primary-50 rounded-lg"
                      title="Chỉnh sửa"
                    >
                      <FiEdit className="w-5 h-5" />
                    </button>
                    <button 
                      onClick={(e) => {
                        e.preventDefault()
                        handleDelete(course.id)
                      }}
                      className="p-2 text-red-600 hover:bg-red-50 rounded-lg"
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

        {/* Create Course Modal */}
        {showCreateModal && (
          <CreateCourseModal
            isOpen={showCreateModal}
            onClose={() => setShowCreateModal(false)}
            onSuccess={handleCreateSuccess}
          />
        )}

        {/* Edit Course Modal */}
        {showEditModal && selectedCourse && (
          <EditCourseModal
            isOpen={showEditModal}
            onClose={() => {
              setShowEditModal(false)
              setSelectedCourse(null)
            }}
            onSuccess={handleEditSuccess}
            course={selectedCourse}
          />
        )}
      </div>
    </div>
  )
}

export default AdminCourses

