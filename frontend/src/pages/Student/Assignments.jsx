import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { FiFileText, FiClock, FiCheckCircle } from 'react-icons/fi'
import { assignmentsAPI, submissionsAPI } from '../../services/api'
import { useAuth } from '../../contexts/AuthContext'
import toast from 'react-hot-toast'

const StudentAssignments = () => {
  const { user } = useAuth()
  const [assignments, setAssignments] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (user?.id) {
      loadAssignments()
    }
  }, [user?.id])

  const loadAssignments = async () => {
    if (!user?.id) return
    
    try {
      setLoading(true)
      // Lấy tất cả assignments từ các khóa học đã đăng ký
      const response = await assignmentsAPI.getAll()
      if (response.success && response.data) {
        // Lọc assignments theo các khóa học đã enroll
        setAssignments(response.data)
      }
    } catch (error) {
      console.error('Load assignments error:', error)
      toast.error('Không thể tải danh sách bài tập')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold mb-2">Bài tập của tôi</h1>
          <p className="text-gray-600">Xem và nộp bài tập</p>
        </div>

        <div className="card">
          {loading ? (
            <div className="text-center py-12">
              <div className="spinner mx-auto"></div>
            </div>
          ) : assignments.length === 0 ? (
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
                  <div className="flex items-start justify-between mb-2">
                    <div className="flex-1">
                      <h3 className="font-semibold mb-1">{assignment.title}</h3>
                      <p className="text-sm text-gray-500">{assignment.course_name || assignment.course_title || ''}</p>
                    </div>
                    <span className={`badge ${assignment.type === 'quiz' ? 'badge-warning' : 'badge-info'}`}>
                      {assignment.type === 'quiz' ? 'Bài kiểm tra' : 'Bài tập về nhà'}
                    </span>
                  </div>
                  <p className="text-sm text-gray-600 mb-4">{assignment.description}</p>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-gray-600 flex items-center">
                      <FiClock className="mr-1" />
                      Hạn nộp: {new Date(assignment.deadline).toLocaleDateString('vi-VN', { 
                        day: '2-digit', 
                        month: '2-digit', 
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                      })}
                    </span>
                    <Link 
                      to={`/courses/${assignment.course_id}`}
                      className="btn btn-primary"
                    >
                      Xem chi tiết
                    </Link>
                  </div>
                </motion.div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default StudentAssignments

