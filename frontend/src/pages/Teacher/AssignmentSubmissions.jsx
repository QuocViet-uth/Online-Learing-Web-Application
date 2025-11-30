import { useState, useEffect } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import { 
  FiArrowLeft, 
  FiFileText, 
  FiClock, 
  FiUser, 
  FiAward, 
  FiCheckCircle,
  FiXCircle,
  FiAlertCircle,
  FiFile
} from 'react-icons/fi'
import { submissionsAPI } from '../../services/api'
import GradeSubmissionModal from '../../components/Assignment/GradeSubmissionModal'
import toast from 'react-hot-toast'

const AssignmentSubmissions = () => {
  const { assignmentId } = useParams()
  const navigate = useNavigate()
  const [loading, setLoading] = useState(true)
  const [assignment, setAssignment] = useState(null)
  const [submissions, setSubmissions] = useState([])
  const [statistics, setStatistics] = useState(null)
  const [showGradeModal, setShowGradeModal] = useState(false)
  const [selectedSubmission, setSelectedSubmission] = useState(null)

  useEffect(() => {
    if (assignmentId) {
      loadSubmissions()
    }
  }, [assignmentId])

  const loadSubmissions = async () => {
    try {
      setLoading(true)
      const response = await submissionsAPI.getByAssignment(parseInt(assignmentId))
      
      if (response && response.success && response.data) {
        setAssignment(response.data.assignment)
        setSubmissions(response.data.submissions || [])
        setStatistics(response.data.statistics)
      } else {
        toast.error(response?.message || 'Không thể tải danh sách bài nộp')
        setSubmissions([])
      }
    } catch (error) {
      console.error('Error loading submissions:', error)
      toast.error('Không thể tải danh sách bài nộp')
      setSubmissions([])
    } finally {
      setLoading(false)
    }
  }

  const handleGrade = (submission) => {
    setSelectedSubmission(submission)
    setShowGradeModal(true)
  }

  const handleGradeSuccess = () => {
    loadSubmissions()
  }

  const getStatusBadge = (status) => {
    switch (status) {
      case 'graded':
        return (
          <span className="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 flex items-center gap-1">
            <FiCheckCircle className="w-3 h-3" />
            Đã chấm
          </span>
        )
      case 'late':
        return (
          <span className="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 flex items-center gap-1">
            <FiAlertCircle className="w-3 h-3" />
            Nộp muộn
          </span>
        )
      case 'submitted':
        return (
          <span className="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 flex items-center gap-1">
            <FiFileText className="w-3 h-3" />
            Đã nộp
          </span>
        )
      default:
        return (
          <span className="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
            {status}
          </span>
        )
    }
  }

  const getScoreColor = (score, maxScore) => {
    if (score === null) return 'text-gray-500'
    const percentage = (score / maxScore) * 100
    if (percentage >= 80) return 'text-green-600 font-semibold'
    if (percentage >= 60) return 'text-yellow-600 font-semibold'
    return 'text-red-600 font-semibold'
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        {/* Header */}
        <div className="flex items-center justify-between mb-6">
          <div className="flex items-center space-x-4">
            <button
              onClick={() => navigate(-1)}
              className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <FiArrowLeft className="w-6 h-6" />
            </button>
            <div>
              <h1 className="text-3xl font-bold mb-2">Bài nộp</h1>
              {assignment && (
                <p className="text-gray-600">{assignment.title}</p>
              )}
            </div>
          </div>
        </div>

        {loading ? (
          <div className="text-center py-12">
            <div className="spinner mx-auto"></div>
          </div>
        ) : (
          <>
            {/* Statistics */}
            {statistics && (
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <motion.div
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="card"
                >
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm text-gray-600 mb-1">Tổng bài nộp</p>
                      <p className="text-2xl font-bold">{statistics.total_submissions}</p>
                    </div>
                    <FiFileText className="w-8 h-8 text-blue-600" />
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
                      <p className="text-sm text-gray-600 mb-1">Đã chấm</p>
                      <p className="text-2xl font-bold text-green-600">{statistics.graded_count}</p>
                    </div>
                    <FiCheckCircle className="w-8 h-8 text-green-600" />
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
                      <p className="text-sm text-gray-600 mb-1">Chưa chấm</p>
                      <p className="text-2xl font-bold text-yellow-600">{statistics.ungraded_count}</p>
                    </div>
                    <FiAlertCircle className="w-8 h-8 text-yellow-600" />
                  </div>
                </motion.div>
              </div>
            )}

            {/* Submissions List */}
            <div className="card">
              {submissions.length === 0 ? (
                <div className="text-center py-12">
                  <FiFileText className="w-16 h-16 mx-auto mb-4 text-gray-300" />
                  <p className="text-gray-600">Chưa có bài nộp nào</p>
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="border-b border-gray-200">
                        <th className="text-left p-4 font-semibold">Học viên</th>
                        <th className="text-left p-4 font-semibold">Ngày nộp</th>
                        <th className="text-left p-4 font-semibold">Trạng thái</th>
                        <th className="text-left p-4 font-semibold">Điểm số</th>
                        <th className="text-left p-4 font-semibold">Thao tác</th>
                      </tr>
                    </thead>
                    <tbody>
                      {submissions.map((submission, index) => (
                        <motion.tr
                          key={submission.id}
                          initial={{ opacity: 0, x: -20 }}
                          animate={{ opacity: 1, x: 0 }}
                          transition={{ delay: index * 0.05 }}
                          className="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                        >
                          <td className="p-4">
                            <div className="flex items-center space-x-3">
                              <div className="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                <FiUser className="w-6 h-6 text-primary-600" />
                              </div>
                              <div>
                                <p className="font-semibold">{submission.student_name}</p>
                                <p className="text-sm text-gray-500">{submission.student_email}</p>
                              </div>
                            </div>
                          </td>
                          <td className="p-4">
                            <div className="flex items-center space-x-2 text-sm text-gray-600">
                              <FiClock className="w-4 h-4" />
                              <span>{new Date(submission.submit_date).toLocaleString('vi-VN')}</span>
                            </div>
                          </td>
                          <td className="p-4">
                            {getStatusBadge(submission.status)}
                          </td>
                          <td className="p-4">
                            {submission.score !== null ? (
                              <div className="flex items-center space-x-2">
                                <FiAward className={`w-5 h-5 ${getScoreColor(submission.score, submission.max_score)}`} />
                                <span className={getScoreColor(submission.score, submission.max_score)}>
                                  {submission.score.toFixed(1)} / {submission.max_score}
                                </span>
                              </div>
                            ) : (
                              <span className="text-gray-400">Chưa chấm</span>
                            )}
                          </td>
                          <td className="p-4">
                            <button
                              onClick={() => handleGrade(submission)}
                              className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm flex items-center gap-2"
                            >
                              <FiAward className="w-4 h-4" />
                              {submission.status === 'graded' ? 'Xem/Sửa điểm' : 'Chấm điểm'}
                            </button>
                          </td>
                        </motion.tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </>
        )}

        {/* Grade Modal */}
        {assignment && selectedSubmission && (
          <GradeSubmissionModal
            isOpen={showGradeModal}
            onClose={() => {
              setShowGradeModal(false)
              setSelectedSubmission(null)
            }}
            onSuccess={handleGradeSuccess}
            submission={selectedSubmission}
            assignment={assignment}
          />
        )}
      </div>
    </div>
  )
}

export default AssignmentSubmissions

