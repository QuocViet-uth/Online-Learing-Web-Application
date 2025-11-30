import { useState, useEffect } from 'react'
import { useParams, Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { FiPlus, FiEdit, FiTrash2, FiBook, FiFileText, FiArrowLeft, FiUsers, FiAlertCircle } from 'react-icons/fi'
import { coursesAPI, lessonsAPI, assignmentsAPI, enrollmentsAPI } from '../../services/api'
import toast from 'react-hot-toast'
import CreateLessonModal from '../../components/Lesson/CreateLessonModal'
import CreateAssignmentModal from '../../components/Assignment/CreateAssignmentModal'
import EditAssignmentModal from '../../components/Assignment/EditAssignmentModal'

const AdminCourseManage = () => {
  const { id } = useParams()
  const [course, setCourse] = useState(null)
  const [lessons, setLessons] = useState([])
  const [assignments, setAssignments] = useState([])
  const [students, setStudents] = useState([])
  const [activeTab, setActiveTab] = useState('lessons')
  const [loading, setLoading] = useState(true)
  const [showCreateLessonModal, setShowCreateLessonModal] = useState(false)
  const [showCreateAssignmentModal, setShowCreateAssignmentModal] = useState(false)
  const [showEditAssignmentModal, setShowEditAssignmentModal] = useState(false)
  const [selectedAssignment, setSelectedAssignment] = useState(null)
  const [deletingStudentId, setDeletingStudentId] = useState(null)
  const [showDeleteStudentConfirm, setShowDeleteStudentConfirm] = useState(null)

  useEffect(() => {
    loadData()
  }, [id])

  const loadData = async () => {
    try {
      setLoading(true)
      const [courseRes, lessonsRes, assignmentsRes, studentsRes] = await Promise.all([
        coursesAPI.getById(id),
        lessonsAPI.getByCourse(id),
        assignmentsAPI.getByCourse(id),
        enrollmentsAPI.getByCourse(id),
      ])

      if (courseRes && courseRes.success) {
        setCourse(courseRes.data)
      }
      
      if (lessonsRes && lessonsRes.success) {
        const lessonsData = lessonsRes.data || []
        setLessons(Array.isArray(lessonsData) ? lessonsData : [])
      } else {
        setLessons([])
      }
      
      if (assignmentsRes && assignmentsRes.success) {
        const assignmentsData = assignmentsRes.data || []
        setAssignments(Array.isArray(assignmentsData) ? assignmentsData : [])
      } else {
        setAssignments([])
      }

      if (studentsRes && studentsRes.success) {
        const studentsData = studentsRes.data || []
        setStudents(Array.isArray(studentsData) ? studentsData : [])
      } else {
        setStudents([])
      }
    } catch (error) {
      console.error('Error loading data:', error)
      toast.error('Không thể tải dữ liệu khóa học')
    } finally {
      setLoading(false)
    }
  }

  const handleDeleteStudent = async (studentId) => {
    setDeletingStudentId(studentId)
    try {
      const result = await enrollmentsAPI.removeStudent(id, studentId)
      if (result.success) {
        toast.success('Xóa học viên khỏi khóa học thành công!')
        loadData()
      } else {
        toast.error(result.message || 'Xóa học viên thất bại')
      }
    } catch (error) {
      console.error('Delete student error:', error)
      toast.error(error.message || 'Có lỗi xảy ra khi xóa học viên')
    } finally {
      setDeletingStudentId(null)
      setShowDeleteStudentConfirm(null)
    }
  }

  const handleDeleteLesson = async (lessonId) => {
    if (!window.confirm('Bạn có chắc chắn muốn xóa bài học này?')) {
      return
    }

    try {
      const response = await lessonsAPI.delete(lessonId)
      if (response && response.success) {
        toast.success('Xóa bài học thành công!')
        loadData()
      } else {
        toast.error(response?.message || 'Không thể xóa bài học')
      }
    } catch (error) {
      console.error('Error deleting lesson:', error)
      toast.error('Có lỗi xảy ra khi xóa bài học')
    }
  }

  const handleDeleteAssignment = async (assignmentId) => {
    if (!window.confirm('Bạn có chắc chắn muốn xóa bài tập này?')) {
      return
    }

    try {
      const response = await assignmentsAPI.delete(assignmentId)
      if (response && response.success) {
        toast.success('Xóa bài tập thành công!')
        loadData()
      } else {
        toast.error(response?.message || 'Không thể xóa bài tập')
      }
    } catch (error) {
      console.error('Error deleting assignment:', error)
      toast.error('Có lỗi xảy ra khi xóa bài tập')
    }
  }

  const handleEditAssignment = (assignment) => {
    setSelectedAssignment(assignment)
    setShowEditAssignmentModal(true)
  }

  if (loading) {
    return (
      <div className="pt-16 md:pt-20 min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="spinner"></div>
      </div>
    )
  }

  if (!course) {
    return (
      <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
        <div className="container-custom py-8">
          <div className="card text-center py-12">
            <p className="text-gray-600 mb-4">Không tìm thấy khóa học</p>
            <Link to="/admin/courses" className="btn btn-primary">
              Quay lại danh sách
            </Link>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        {/* Header */}
        <div className="mb-6">
          <Link
            to="/admin/courses"
            className="inline-flex items-center text-primary-600 hover:text-primary-700 mb-4"
          >
            <FiArrowLeft className="mr-2" />
            Quay lại danh sách khóa học
          </Link>
          <h1 className="text-3xl font-bold mb-2">{course.title || course.course_name}</h1>
          <p className="text-gray-600">{course.description || 'Không có mô tả'}</p>
        </div>

        {/* Tabs */}
        <div className="card mb-6">
          <div className="flex border-b border-gray-200">
            <button
              onClick={() => setActiveTab('lessons')}
              className={`px-6 py-3 font-medium transition-colors ${
                activeTab === 'lessons'
                  ? 'text-primary-600 border-b-2 border-primary-600'
                  : 'text-gray-600 hover:text-primary-600'
              }`}
            >
              <FiBook className="inline mr-2" />
              Bài học ({lessons.length})
            </button>
            <button
              onClick={() => setActiveTab('assignments')}
              className={`px-6 py-3 font-medium transition-colors ${
                activeTab === 'assignments'
                  ? 'text-primary-600 border-b-2 border-primary-600'
                  : 'text-gray-600 hover:text-primary-600'
              }`}
            >
              <FiFileText className="inline mr-2" />
              Bài tập ({assignments.length})
            </button>
            <button
              onClick={() => setActiveTab('students')}
              className={`px-6 py-3 font-medium transition-colors ${
                activeTab === 'students'
                  ? 'text-primary-600 border-b-2 border-primary-600'
                  : 'text-gray-600 hover:text-primary-600'
              }`}
            >
              <FiUsers className="inline mr-2" />
              Học viên ({students.length})
            </button>
          </div>
        </div>

        {/* Lessons Tab */}
        {activeTab === 'lessons' && (
          <div className="card">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold">Danh sách bài học</h2>
              <button
                onClick={() => setShowCreateLessonModal(true)}
                className="btn btn-primary flex items-center"
              >
                <FiPlus className="mr-2" />
                Thêm bài học
              </button>
            </div>

            {lessons.length === 0 ? (
              <div className="text-center py-12 text-gray-600">
                <FiBook className="w-16 h-16 mx-auto mb-4 text-gray-300" />
                <p>Chưa có bài học nào</p>
              </div>
            ) : (
              <div className="space-y-4">
                {lessons.map((lesson, index) => (
                  <motion.div
                    key={lesson.id}
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
                  >
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center space-x-3 mb-2">
                          <span className="text-primary-600 font-semibold">Bài {index + 1}</span>
                          <h3 className="text-lg font-semibold">{lesson.title}</h3>
                        </div>
                        <p className="text-gray-600 text-sm mb-2 line-clamp-2">{lesson.content}</p>
                        {lesson.video_url && (
                          <a
                            href={lesson.video_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-primary-600 text-sm hover:underline"
                          >
                            Xem video
                          </a>
                        )}
                        {lesson.duration && (
                          <p className="text-xs text-gray-500 mt-1">Thời lượng: {lesson.duration} phút</p>
                        )}
                      </div>
                      <button
                        onClick={() => handleDeleteLesson(lesson.id)}
                        className="p-2 text-red-600 hover:bg-red-50 rounded-lg ml-4"
                        title="Xóa bài học"
                      >
                        <FiTrash2 className="w-5 h-5" />
                      </button>
                    </div>
                  </motion.div>
                ))}
              </div>
            )}
          </div>
        )}

        {/* Students Tab */}
        {activeTab === 'students' && (
          <div className="card">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold">Danh sách học viên</h2>
            </div>

            {students.length === 0 ? (
              <div className="text-center py-12 text-gray-600">
                <FiUsers className="w-16 h-16 mx-auto mb-4 text-gray-300" />
                <p>Chưa có học viên nào đăng ký</p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="border-b border-gray-200">
                      <th className="text-left p-4 font-semibold">Tên học viên</th>
                      <th className="text-left p-4 font-semibold">Email</th>
                      <th className="text-left p-4 font-semibold">Ngày đăng ký</th>
                      <th className="text-left p-4 font-semibold">Trạng thái</th>
                      <th className="text-left p-4 font-semibold">Thao tác</th>
                    </tr>
                  </thead>
                  <tbody>
                    {students.map((student) => (
                      <motion.tr
                        key={student.id}
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        className="border-b border-gray-100 hover:bg-gray-50"
                      >
                        <td className="p-4 font-medium">
                          {student.student_name || `ID: ${student.student_id}`}
                        </td>
                        <td className="p-4 text-gray-600">
                          {student.student_email || '-'}
                        </td>
                        <td className="p-4 text-gray-600">
                          {student.enrollment_date 
                            ? new Date(student.enrollment_date).toLocaleDateString('vi-VN', {
                                year: 'numeric',
                                month: '2-digit',
                                day: '2-digit'
                              })
                            : '-'}
                        </td>
                        <td className="p-4">
                          <span className={`badge ${
                            student.status === 'active' ? 'badge-success' :
                            student.status === 'cancelled' ? 'badge-danger' :
                            student.status === 'completed' ? 'badge-info' :
                            'badge-secondary'
                          }`}>
                            {student.status === 'active' ? 'Đang học' :
                             student.status === 'cancelled' ? 'Đã hủy' :
                             student.status === 'completed' ? 'Hoàn thành' :
                             student.status}
                          </span>
                        </td>
                        <td className="p-4">
                          <button
                            onClick={() => setShowDeleteStudentConfirm(student.student_id)}
                            disabled={deletingStudentId === student.student_id}
                            className="text-red-600 hover:text-red-700 hover:bg-red-50 p-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Xóa học viên khỏi khóa học"
                          >
                            <FiTrash2 className="w-5 h-5" />
                          </button>
                        </td>
                      </motion.tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        )}

        {/* Assignments Tab */}
        {activeTab === 'assignments' && (
          <div className="card">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold">Danh sách bài tập</h2>
              <button
                onClick={() => setShowCreateAssignmentModal(true)}
                className="btn btn-primary flex items-center"
              >
                <FiPlus className="mr-2" />
                Thêm bài tập
              </button>
            </div>

            {assignments.length === 0 ? (
              <div className="text-center py-12 text-gray-600">
                <FiFileText className="w-16 h-16 mx-auto mb-4 text-gray-300" />
                <p>Chưa có bài tập nào</p>
              </div>
            ) : (
              <div className="space-y-4">
                {assignments.map((assignment) => (
                  <motion.div
                    key={assignment.id}
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
                  >
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <h3 className="text-lg font-semibold mb-2">{assignment.title}</h3>
                        <p className="text-gray-600 text-sm mb-3 line-clamp-2">{assignment.description}</p>
                        <div className="flex flex-wrap gap-4 text-sm text-gray-600">
                          {assignment.start_date && (
                            <span>Bắt đầu: {new Date(assignment.start_date).toLocaleDateString('vi-VN')}</span>
                          )}
                          {assignment.deadline && (
                            <span>Hạn nộp: {new Date(assignment.deadline).toLocaleDateString('vi-VN')}</span>
                          )}
                          {assignment.allow_late_submission !== undefined && (
                            <span className={`badge ${
                              assignment.allow_late_submission ? 'badge-warning' : 'badge-danger'
                            }`}>
                              {assignment.allow_late_submission ? 'Cho phép nộp muộn' : 'Không cho phép nộp muộn'}
                            </span>
                          )}
                        </div>
                      </div>
                      <div className="flex space-x-2 ml-4">
                        <button
                          onClick={() => handleEditAssignment(assignment)}
                          className="p-2 text-primary-600 hover:bg-primary-50 rounded-lg"
                          title="Chỉnh sửa"
                        >
                          <FiEdit className="w-5 h-5" />
                        </button>
                        <button
                          onClick={() => handleDeleteAssignment(assignment.id)}
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
          </div>
        )}

        {/* Modals */}
        {showCreateLessonModal && (
          <CreateLessonModal
            isOpen={showCreateLessonModal}
            onClose={() => setShowCreateLessonModal(false)}
            onSuccess={() => {
              setShowCreateLessonModal(false)
              loadData()
              toast.success('Tạo bài học thành công!')
            }}
            courseId={id}
          />
        )}

        {showCreateAssignmentModal && (
          <CreateAssignmentModal
            isOpen={showCreateAssignmentModal}
            onClose={() => setShowCreateAssignmentModal(false)}
            onSuccess={() => {
              setShowCreateAssignmentModal(false)
              loadData()
              toast.success('Tạo bài tập thành công!')
            }}
            courseId={id}
          />
        )}

        {showEditAssignmentModal && selectedAssignment && (
          <EditAssignmentModal
            isOpen={showEditAssignmentModal}
            onClose={() => {
              setShowEditAssignmentModal(false)
              setSelectedAssignment(null)
            }}
            onSuccess={() => {
              setShowEditAssignmentModal(false)
              setSelectedAssignment(null)
              loadData()
              toast.success('Cập nhật bài tập thành công!')
            }}
            assignment={selectedAssignment}
          />
        )}

        {/* Delete Student Confirmation Modal */}
        {showDeleteStudentConfirm && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <motion.div
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              className="bg-white rounded-xl shadow-xl max-w-md w-full p-6"
            >
              <div className="flex items-center mb-4">
                <div className="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                  <FiAlertCircle className="w-6 h-6 text-red-600" />
                </div>
                <div>
                  <h3 className="text-xl font-bold text-gray-900">Xác nhận xóa</h3>
                  <p className="text-gray-600 text-sm mt-1">Hành động này không thể hoàn tác</p>
                </div>
              </div>
              
              <p className="text-gray-700 mb-6">
                Bạn có chắc chắn muốn xóa học viên này khỏi khóa học? Học viên sẽ mất quyền truy cập vào khóa học.
              </p>
              
              <div className="flex gap-3">
                <button
                  onClick={() => setShowDeleteStudentConfirm(null)}
                  disabled={deletingStudentId === showDeleteStudentConfirm}
                  className="flex-1 btn btn-outline"
                >
                  Hủy
                </button>
                <button
                  onClick={() => handleDeleteStudent(showDeleteStudentConfirm)}
                  disabled={deletingStudentId === showDeleteStudentConfirm}
                  className="flex-1 btn bg-red-600 hover:bg-red-700 text-white"
                >
                  {deletingStudentId === showDeleteStudentConfirm ? (
                    <span className="flex items-center justify-center">
                      <div className="spinner mr-2"></div>
                      Đang xóa...
                    </span>
                  ) : (
                    'Xóa'
                  )}
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </div>
    </div>
  )
}

export default AdminCourseManage

