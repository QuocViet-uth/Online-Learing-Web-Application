import { useState, useEffect } from 'react'
import { FiX, FiCheck, FiFile, FiUser, FiClock, FiAward } from 'react-icons/fi'
import { submissionsAPI } from '../../services/api'
import toast from 'react-hot-toast'

const GradeSubmissionModal = ({ isOpen, onClose, onSuccess, submission, assignment }) => {
  const [loading, setLoading] = useState(false)
  const [formData, setFormData] = useState({
    score: '',
    feedback: ''
  })

  useEffect(() => {
    if (isOpen && submission) {
      setFormData({
        score: submission.score !== null && submission.score !== undefined ? submission.score.toString() : '',
        feedback: submission.feedback || ''
      })
    }
  }, [isOpen, submission])

  const handleChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: name === 'score' ? value : value
    }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    
    if (!submission || !assignment) {
      toast.error('Thiếu thông tin bài nộp hoặc bài tập')
      return
    }

    // Validate
    const score = parseFloat(formData.score)
    if (isNaN(score) || score < 0) {
      toast.error('Điểm số không hợp lệ')
      return
    }

    if (score > assignment.max_score) {
      toast.error(`Điểm số không được vượt quá ${assignment.max_score}`)
      return
    }

    try {
      setLoading(true)
      const response = await submissionsAPI.grade(submission.id, {
        score: score,
        feedback: formData.feedback.trim()
      })
      
      if (response && response.success) {
        toast.success('Chấm điểm thành công!')
        onSuccess && onSuccess(response.data)
        onClose()
      } else {
        toast.error(response?.message || 'Không thể chấm điểm')
      }
    } catch (error) {
      console.error('Error grading submission:', error)
      const errorMessage = error.response?.data?.message || error.message || 'Có lỗi xảy ra khi chấm điểm'
      toast.error(errorMessage)
    } finally {
      setLoading(false)
    }
  }

  if (!isOpen || !submission || !assignment) return null

  const isGraded = submission.status === 'graded'

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
          <div>
            <h2 className="text-xl font-bold text-gray-900">
              {isGraded ? 'Cập nhật điểm' : 'Chấm điểm bài nộp'}
            </h2>
            <p className="text-sm text-gray-600 mt-1">{assignment.title}</p>
          </div>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
            disabled={loading}
          >
            <FiX className="w-6 h-6" />
          </button>
        </div>

        {/* Content */}
        <form onSubmit={handleSubmit} className="p-6 space-y-6">
          {/* Thông tin học sinh */}
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div className="flex items-center space-x-3 mb-3">
              <FiUser className="w-5 h-5 text-blue-600" />
              <div>
                <p className="font-semibold text-gray-900">{submission.student_name}</p>
                <p className="text-sm text-gray-600">{submission.student_email}</p>
              </div>
            </div>
            <div className="flex items-center space-x-4 text-sm text-gray-600">
              <div className="flex items-center space-x-1">
                <FiClock className="w-4 h-4" />
                <span>Nộp bài: {new Date(submission.submit_date).toLocaleString('vi-VN')}</span>
              </div>
              {submission.status === 'late' && (
                <span className="text-red-600 font-medium">Nộp muộn</span>
              )}
            </div>
          </div>

          {/* Nội dung bài nộp */}
          <div>
            <h3 className="text-lg font-semibold mb-3">Nội dung bài nộp</h3>
            <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
              {submission.content ? (
                <p className="text-gray-700 whitespace-pre-wrap">{submission.content}</p>
              ) : (
                <p className="text-gray-400 italic">Không có nội dung text</p>
              )}
            </div>
          </div>

          {/* File đính kèm */}
          {submission.attachment_file && (
            <div>
              <h3 className="text-lg font-semibold mb-3">File đính kèm</h3>
              <div className="flex items-center space-x-3">
                <FiFile className="w-5 h-5 text-gray-400" />
                <a
                  href={submission.attachment_file}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-primary-600 hover:underline"
                >
                  {submission.attachment_file.split('/').pop()}
                </a>
              </div>
            </div>
          )}

          {/* Form chấm điểm */}
          <div className="border-t border-gray-200 pt-6">
            <h3 className="text-lg font-semibold mb-4 flex items-center">
              <FiAward className="mr-2 text-yellow-600" />
              {isGraded ? 'Cập nhật điểm' : 'Chấm điểm'}
            </h3>
            
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Điểm số <span className="text-red-500">*</span>
                  <span className="text-gray-500 font-normal"> (Tối đa: {assignment.max_score})</span>
                </label>
                <input
                  type="number"
                  name="score"
                  value={formData.score}
                  onChange={handleChange}
                  min="0"
                  max={assignment.max_score}
                  step="0.1"
                  required
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                  placeholder="Nhập điểm số"
                  disabled={loading}
                />
                {formData.score && !isNaN(parseFloat(formData.score)) && (
                  <p className="text-xs text-gray-500 mt-1">
                    {((parseFloat(formData.score) / assignment.max_score) * 100).toFixed(1)}% điểm tối đa
                  </p>
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Nhận xét
                </label>
                <textarea
                  name="feedback"
                  value={formData.feedback}
                  onChange={handleChange}
                  rows="6"
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                  placeholder="Nhập nhận xét cho học sinh..."
                  disabled={loading}
                />
                <p className="text-xs text-gray-500 mt-1">
                  {formData.feedback.length} ký tự
                </p>
              </div>
            </div>
          </div>

          {/* Buttons */}
          <div className="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              onClick={onClose}
              className="px-6 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
              disabled={loading}
            >
              Hủy
            </button>
            <button
              type="submit"
              disabled={loading}
              className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              {loading ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                  Đang xử lý...
                </>
              ) : (
                <>
                  <FiCheck className="w-4 h-4" />
                  {isGraded ? 'Cập nhật điểm' : 'Chấm điểm'}
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default GradeSubmissionModal

