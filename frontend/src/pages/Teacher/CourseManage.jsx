import { useState, useEffect } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import { FiPlus, FiEdit, FiTrash2, FiBook, FiFileText, FiTrendingUp, FiUsers, FiAlertCircle, FiAward } from 'react-icons/fi'
import { coursesAPI, lessonsAPI, assignmentsAPI, enrollmentsAPI } from '../../services/api'
import toast from 'react-hot-toast'
import CreateLessonModal from '../../components/Lesson/CreateLessonModal'
import EditLessonModal from '../../components/Lesson/EditLessonModal'
import CreateAssignmentModal from '../../components/Assignment/CreateAssignmentModal'
import EditAssignmentModal from '../../components/Assignment/EditAssignmentModal'

const TeacherCourseManage = () => {
  const { id } = useParams()
  const navigate = useNavigate()
  const [course, setCourse] = useState(null)
  const [lessons, setLessons] = useState([])
  const [assignments, setAssignments] = useState([])
  const [students, setStudents] = useState([])
  const [activeTab, setActiveTab] = useState('lessons')
  const [loading, setLoading] = useState(true)
  const [showCreateLessonModal, setShowCreateLessonModal] = useState(false)
  const [showEditLessonModal, setShowEditLessonModal] = useState(false)
  const [selectedLesson, setSelectedLesson] = useState(null)
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
      toast.error('Không thể tải dữ liệu: ' + (error.message || 'Unknown error'))
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

  if (loading) {
    return (
      <div className="pt-16 md:pt-20 min-h-screen flex items-center justify-center">
        <div className="spinner"></div>
      </div>
    )
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="mb-8">
          <Link to="/teacher/courses" className="text-primary-600 hover:text-primary-700 mb-4 inline-block">
            ← Quay lại danh sách
          </Link>
          <h1 className="text-3xl font-bold mb-2">{course?.title || 'Quản lý khóa học'}</h1>
        </div>

        {/* Tabs */}
        <div className="card mb-6">
          <div className="flex border-b border-gray-200">
            <button
              onClick={() => setActiveTab('lessons')}
              className={`px-6 py-4 font-medium transition-colors ${
                activeTab === 'lessons'
                  ? 'text-primary-600 border-b-2 border-primary-600'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              <FiBook className="inline mr-2" />
              Bài giảng
            </button>
            <button
              onClick={() => setActiveTab('assignments')}
              className={`px-6 py-4 font-medium transition-colors ${
                activeTab === 'assignments'
                  ? 'text-primary-600 border-b-2 border-primary-600'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              <FiFileText className="inline mr-2" />
              Bài tập
            </button>
            <button
              onClick={() => setActiveTab('students')}
              className={`px-6 py-4 font-medium transition-colors ${
                activeTab === 'students'
                  ? 'text-primary-600 border-b-2 border-primary-600'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              <FiUsers className="inline mr-2" />
              Học viên ({students.length})
            </button>
            <button
              onClick={() => setActiveTab('performance')}
              className={`px-6 py-4 font-medium transition-colors ${
                activeTab === 'performance'
                  ? 'text-primary-600 border-b-2 border-primary-600'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              <FiTrendingUp className="inline mr-2" />
              Hiệu suất
            </button>
          </div>

          <div className="p-6">
            {activeTab === 'lessons' && (
              <div>
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-xl font-semibold">Danh sách bài giảng</h3>
                  <button 
                    onClick={() => setShowCreateLessonModal(true)}
                    className="btn btn-primary flex items-center"
                  >
                    <FiPlus className="mr-2" />
                    Thêm bài giảng
                  </button>
                </div>
                <div className="space-y-2">
                  {lessons.length === 0 ? (
                    <p className="text-gray-600">Chưa có bài giảng nào</p>
                  ) : (
                    lessons.map((lesson) => (
                      <div
                        key={lesson.id}
                        className="flex items-center justify-between p-4 border border-gray-200 rounded-lg"
                      >
                        <div className="flex-1">
                          <h4 className="font-medium">{lesson.title}</h4>
                          <div className="text-sm text-gray-600 space-y-1">
                            <p>Thời lượng: {lesson.duration || 0} phút</p>
                            {lesson.order_number && (
                              <p>Thứ tự: {lesson.order_number}</p>
                            )}
                          </div>
                        </div>
                        <div className="flex space-x-2">
                          <button 
                            onClick={() => {
                              setSelectedLesson(lesson)
                              setShowEditLessonModal(true)
                            }}
                            className="p-2 text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                            title="Chỉnh sửa"
                          >
                            <FiEdit className="w-5 h-5" />
                          </button>
                          <button 
                            onClick={async () => {
                              if (window.confirm('Bạn có chắc chắn muốn xóa bài giảng này?')) {
                                try {
                                  await lessonsAPI.delete(lesson.id)
                                  toast.success('Xóa bài giảng thành công')
                                  loadData()
                                } catch (error) {
                                  console.error('Error deleting lesson:', error)
                                  toast.error(error.response?.data?.message || 'Không thể xóa bài giảng')
                                }
                              }
                            }}
                            className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            title="Xóa"
                          >
                            <FiTrash2 className="w-5 h-5" />
                          </button>
                        </div>
                      </div>
                    ))
                  )}
                </div>
              </div>
            )}

            {activeTab === 'students' && (
              <div>
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-xl font-semibold">Danh sách học viên</h3>
                </div>
                <div className="overflow-x-auto">
                  {students.length === 0 ? (
                    <div className="text-center py-12 text-gray-600">
                      <FiUsers className="w-16 h-16 mx-auto mb-4 text-gray-300" />
                      <p>Chưa có học viên nào đăng ký</p>
                    </div>
                  ) : (
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
                  )}
                </div>
              </div>
            )}

            {activeTab === 'assignments' && (
              <div>
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-xl font-semibold">Danh sách bài tập</h3>
                  <button 
                    onClick={() => setShowCreateAssignmentModal(true)}
                    className="btn btn-primary flex items-center"
                  >
                    <FiPlus className="mr-2" />
                    Thêm bài tập
                  </button>
                </div>
                <div className="space-y-2">
                  {assignments.length === 0 ? (
                    <p className="text-gray-600">Chưa có bài tập nào</p>
                  ) : (
                    assignments.map((assignment) => (
                      <div
                        key={assignment.id}
                        className="flex items-center justify-between p-4 border border-gray-200 rounded-lg"
                      >
                        <div className="flex-1">
                          <h4 className="font-medium">{assignment.title}</h4>
                          <div className="text-sm text-gray-600 space-y-1">
                            {assignment.start_date && (
                              <p>Bắt đầu: {new Date(assignment.start_date).toLocaleString('vi-VN')}</p>
                            )}
                            <p>Hạn nộp: {new Date(assignment.deadline).toLocaleString('vi-VN')}</p>
                            <p>Điểm tối đa: {assignment.max_score}</p>
                          </div>
                        </div>
                        <div className="flex space-x-2">
                          <button 
                            onClick={() => navigate(`/teacher/assignments/${assignment.id}/submissions`)}
                            className="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                            title="Xem bài nộp và chấm điểm"
                          >
                            <FiAward className="w-5 h-5" />
                          </button>
                          <button 
                            onClick={() => {
                              setSelectedAssignment(assignment)
                              setShowEditAssignmentModal(true)
                            }}
                            className="p-2 text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                            title="Chỉnh sửa"
                          >
                            <FiEdit className="w-5 h-5" />
                          </button>
                          <button 
                            onClick={async () => {
                              if (window.confirm('Bạn có chắc chắn muốn xóa bài tập này?')) {
                                try {
                                  await assignmentsAPI.delete(assignment.id)
                                  toast.success('Xóa bài tập thành công')
                                  loadData()
                                } catch (error) {
                                  console.error('Error deleting assignment:', error)
                                  toast.error(error.response?.data?.message || 'Không thể xóa bài tập')
                                }
                              }
                            }}
                            className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            title="Xóa"
                          >
                            <FiTrash2 className="w-5 h-5" />
                          </button>
                        </div>
                      </div>
                    ))
                  )}
                </div>
              </div>
            )}

          </div>
        </div>

        <CreateLessonModal
          isOpen={showCreateLessonModal}
          onClose={() => setShowCreateLessonModal(false)}
          onSuccess={(newLesson) => {
            // Reload danh sách lessons
            loadData()
          }}
          courseId={id}
        />

        <EditLessonModal
          isOpen={showEditLessonModal}
          onClose={() => {
            setShowEditLessonModal(false)
            setSelectedLesson(null)
          }}
          onSuccess={(updatedLesson) => {
            // Reload danh sách lessons
            loadData()
          }}
          lesson={selectedLesson}
        />

        <CreateAssignmentModal
          isOpen={showCreateAssignmentModal}
          onClose={() => setShowCreateAssignmentModal(false)}
          onSuccess={(newAssignment) => {
            // Reload danh sách assignments
            loadData()
          }}
          courseId={id}
        />

        <EditAssignmentModal
          isOpen={showEditAssignmentModal}
          onClose={() => {
            setShowEditAssignmentModal(false)
            setSelectedAssignment(null)
          }}
          onSuccess={(updatedAssignment) => {
            // Reload danh sách assignments
            loadData()
          }}
          assignment={selectedAssignment}
        />

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

export default TeacherCourseManage

