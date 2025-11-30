import { useState, useEffect } from 'react'
import { FiX, FiUpload, FiFile, FiClock, FiCheck } from 'react-icons/fi'
import { submissionsAPI } from '../../services/api'
import toast from 'react-hot-toast'
import axios from 'axios'

const SubmitAssignmentModal = ({ isOpen, onClose, onSuccess, assignment, studentId }) => {
  const [loading, setLoading] = useState(false)
  const [uploading, setUploading] = useState(false)
  const [selectedFile, setSelectedFile] = useState(null)
  const [mySubmission, setMySubmission] = useState(null)
  const [formData, setFormData] = useState({
    content: '',
    attachment_file: ''
  })

  useEffect(() => {
    if (isOpen && assignment && studentId) {
      loadMySubmission()
    }
  }, [isOpen, assignment, studentId])

  const loadMySubmission = async () => {
    try {
      const response = await submissionsAPI.getMySubmission(assignment.id, studentId)
      // API interceptor trả về response.data, nên response đã là { success, message, data }
      if (response && response.success) {
        if (response.data) {
          setMySubmission(response.data)
          setFormData({
            content: response.data.content || '',
            attachment_file: response.data.attachment_file || ''
          })
        } else {
          setMySubmission(null)
          setFormData({
            content: '',
            attachment_file: ''
          })
        }
      }
    } catch (error) {
      console.error('Error loading submission:', error)
    }
  }

  const handleChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: value
    }))
  }

  const handleFileChange = (e) => {
    const file = e.target.files[0]
    if (file) {
      // Kiểm tra kích thước file (50MB)
      if (file.size > 50 * 1024 * 1024) {
        toast.error('File quá lớn. Kích thước tối đa là 50MB')
        return
      }
      setSelectedFile(file)
    }
  }

  const handleFileUpload = async () => {
    if (!selectedFile) return null

    try {
      setUploading(true)
      const formDataUpload = new FormData()
      formDataUpload.append('file', selectedFile)
      formDataUpload.append('type', 'submission')

      const response = await axios.post('/api/upload-file.php', formDataUpload, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })

      if (response.data && response.data.success) {
        return response.data.data.file_url
      } else {
        throw new Error(response.data?.message || 'Upload file thất bại')
      }
    } catch (error) {
      console.error('Error uploading file:', error)
      toast.error(error.response?.data?.message || error.message || 'Không thể upload file')
      return null
    } finally {
      setUploading(false)
    }
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    
    if (!assignment || !studentId) {
      toast.error('Thiếu thông tin bài tập hoặc học viên')
      return
    }

    // Kiểm tra deadline và allow_late_submission
    const isPastDeadline = assignment.deadline && new Date(assignment.deadline) < new Date()
    const allowLateSubmission = assignment.allow_late_submission !== undefined ? assignment.allow_late_submission : true
    
    if (isPastDeadline && !allowLateSubmission) {
      toast.error('Đã quá hạn nộp bài. Bài tập này không cho phép nộp khi quá hạn.')
      return
    }

    // Validate - ít nhất phải có content hoặc file
    if (!formData.content.trim() && !selectedFile && !formData.attachment_file) {
      toast.error('Vui lòng nhập nội dung hoặc đính kèm file')
      return
    }

    try {
      setLoading(true)
      
      // Upload file nếu có
      let attachmentUrl = formData.attachment_file
      if (selectedFile) {
        attachmentUrl = await handleFileUpload()
        if (!attachmentUrl) {
          setLoading(false)
          return
        }
      }

      const submissionData = {
        assignment_id: assignment.id,
        student_id: studentId,
        content: formData.content.trim(),
        attachment_file: attachmentUrl || null
      }

      // Nếu đã nộp rồi thì update, chưa thì create
      const response = mySubmission 
        ? await submissionsAPI.update(submissionData)
        : await submissionsAPI.submit(submissionData)

      if (response && response.success) {
        toast.success(mySubmission ? 'Cập nhật bài nộp thành công' : 'Nộp bài thành công')
        if (onSuccess) {
          onSuccess(response.data)
        }
        onClose()
      } else {
        const errorMessage = response?.message || response?.errors?.join(', ') || 'Có lỗi xảy ra khi nộp bài'
        throw new Error(errorMessage)
      }
    } catch (error) {
      console.error('Error submitting assignment:', error)
      const errorMessage = error.response?.data?.message || 
                          error.response?.data?.errors?.join(', ') || 
                          error.message || 
                          'Không thể nộp bài. Vui lòng thử lại.'
      toast.error(errorMessage)
    } finally {
      setLoading(false)
    }
  }

  if (!isOpen) return null

  const isPastDeadline = assignment.deadline && new Date(assignment.deadline) < new Date()
  const allowLateSubmission = assignment.allow_late_submission !== undefined ? assignment.allow_late_submission : true
  const canSubmit = !isPastDeadline || allowLateSubmission
  const canEdit = !mySubmission || (mySubmission.status !== 'graded' && (!isPastDeadline || allowLateSubmission))

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
          <div>
            <h2 className="text-xl font-bold text-gray-900">
              {mySubmission ? 'Cập nhật bài nộp' : 'Nộp bài tập'}
            </h2>
            <p className="text-sm text-gray-600 mt-1">{assignment.title}</p>
          </div>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <FiX className="w-6 h-6" />
          </button>
        </div>

        {/* Content */}
        <form onSubmit={handleSubmit} className="p-6 space-y-6">
          {/* Thông tin assignment */}
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div className="flex items-start justify-between">
              <div>
                <h3 className="font-semibold text-gray-900">{assignment.title}</h3>
                {assignment.description && (
                  <p className="text-sm text-gray-600 mt-1">{assignment.description}</p>
                )}
              </div>
            </div>
            <div className="mt-3 flex flex-wrap gap-4 text-sm text-gray-600">
              <div className="flex items-center gap-1">
                <FiClock className="w-4 h-4" />
                <span>Hạn nộp: {new Date(assignment.deadline).toLocaleString('vi-VN')}</span>
              </div>
              {isPastDeadline && (
                <span className="text-red-600 font-medium">Đã quá hạn</span>
              )}
              {mySubmission && (
                <div className="flex items-center gap-1">
                  <FiCheck className="w-4 h-4 text-green-600" />
                  <span className="text-green-600">Đã nộp: {new Date(mySubmission.submit_date).toLocaleString('vi-VN')}</span>
                </div>
              )}
            </div>
            {mySubmission && mySubmission.status === 'graded' && (
              <div className="mt-3 p-3 bg-white rounded border border-gray-200">
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium text-gray-700">Điểm số:</span>
                  <span className="text-lg font-bold text-primary-600">
                    {mySubmission.score !== null ? `${mySubmission.score}/${assignment.max_score}` : 'Chưa chấm'}
                  </span>
                </div>
                {mySubmission.feedback && (
                  <div className="mt-2">
                    <span className="text-sm font-medium text-gray-700">Nhận xét:</span>
                    <p className="text-sm text-gray-600 mt-1">{mySubmission.feedback}</p>
                  </div>
                )}
              </div>
            )}
          </div>

          {!canEdit && mySubmission && mySubmission.status === 'graded' && (
            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <p className="text-sm text-yellow-800">
                Bài đã được chấm điểm. Không thể chỉnh sửa.
              </p>
            </div>
          )}

          {isPastDeadline && !mySubmission && (
            <div className={`border rounded-lg p-3 mb-4 ${allowLateSubmission ? 'bg-yellow-50 border-yellow-200' : 'bg-red-50 border-red-200'}`}>
              <p className={`text-sm ${allowLateSubmission ? 'text-yellow-800' : 'text-red-800'}`}>
                {allowLateSubmission 
                  ? 'Đã quá hạn nộp bài. Bạn vẫn có thể nộp nhưng sẽ bị đánh dấu là nộp muộn.'
                  : 'Đã quá hạn nộp bài. Bài tập này không cho phép nộp khi quá hạn.'}
              </p>
            </div>
          )}
          
          {!canSubmit && !mySubmission && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
              <p className="text-sm text-red-800 font-medium">
                Không thể nộp bài: Đã quá hạn và bài tập này không cho phép nộp khi quá hạn.
              </p>
            </div>
          )}

          {/* Nội dung bài nộp */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Nội dung bài nộp <span className="text-gray-500">(bắt buộc nếu không có file)</span>
            </label>
            <textarea
              name="content"
              value={formData.content}
              onChange={handleChange}
              rows={8}
              disabled={!canEdit || (!canSubmit && !mySubmission)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed resize-y"
              placeholder="Nhập nội dung bài nộp của bạn... (có thể nhập text, code, hoặc mô tả)"
            />
            <p className="text-xs text-gray-500 mt-1">
              {formData.content.length} ký tự
            </p>
          </div>

          {/* File đính kèm */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              File đính kèm <span className="text-gray-500">(bắt buộc nếu không có nội dung text)</span>
            </label>
            <div className="flex items-center space-x-4">
              <label className="flex-1 cursor-pointer">
                <input
                  type="file"
                  onChange={handleFileChange}
                  disabled={!canEdit || (!canSubmit && !mySubmission)}
                  className="hidden"
                  accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.mp4,.mp3,.jpg,.jpeg,.png,.gif"
                />
                <div className={`w-full px-4 py-2 border border-gray-300 rounded-lg transition-colors flex items-center justify-between ${
                  canEdit ? 'hover:bg-gray-50' : 'bg-gray-100 cursor-not-allowed'
                }`}>
                  <span className="text-gray-600">
                    {selectedFile ? selectedFile.name : (formData.attachment_file ? 'File đã tải lên' : 'Chọn file (PDF, DOC, ZIP, etc.)')}
                  </span>
                  <FiFile className="w-5 h-5 text-gray-400" />
                </div>
              </label>
              {selectedFile && canEdit && (
                <button
                  type="button"
                  onClick={() => setSelectedFile(null)}
                  className="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                >
                  Xóa
                </button>
              )}
            </div>
            {selectedFile && (
              <p className="text-xs text-gray-500 mt-1">
                File: {selectedFile.name} ({(selectedFile.size / 1024 / 1024).toFixed(2)} MB)
              </p>
            )}
            {formData.attachment_file && !selectedFile && (
              <div className="mt-2 flex items-center gap-2">
                <a
                  href={formData.attachment_file}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-xs text-primary-600 hover:underline"
                >
                  Xem file đã tải lên
                </a>
              </div>
            )}
          </div>

          {/* Buttons */}
          <div className="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              onClick={onClose}
              className="px-6 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
            >
              Hủy
            </button>
            {(canEdit || (!mySubmission && canSubmit)) && (
              <button
                type="submit"
                disabled={loading || uploading || (!canSubmit && !mySubmission)}
                className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                {loading || uploading ? (
                  <>
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    {uploading ? 'Đang upload file...' : (mySubmission ? 'Đang cập nhật...' : 'Đang nộp...')}
                  </>
                ) : (
                  <>
                    <FiUpload className="w-4 h-4" />
                    {mySubmission ? 'Cập nhật bài nộp' : 'Nộp bài'}
                  </>
                )}
              </button>
            )}
          </div>
        </form>
      </div>
    </div>
  )
}

export default SubmitAssignmentModal

