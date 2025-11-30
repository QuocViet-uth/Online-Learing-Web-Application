import { useState } from 'react'
import { FiX, FiUpload, FiFile } from 'react-icons/fi'
import { lessonsAPI } from '../../services/api'
import toast from 'react-hot-toast'
import axios from 'axios'

const CreateLessonModal = ({ isOpen, onClose, onSuccess, courseId }) => {
  const [loading, setLoading] = useState(false)
  const [uploading, setUploading] = useState(false)
  const [selectedFile, setSelectedFile] = useState(null)
  const [formData, setFormData] = useState({
    title: '',
    content: '',
    video_url: '',
    attachment_file: '',
    order_number: 0,
    duration: 0
  })

  const handleChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: name === 'order_number' || name === 'duration' ? parseInt(value) || 0 : value
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
      const formData = new FormData()
      formData.append('file', selectedFile)
      formData.append('type', 'lesson')

      const response = await axios.post('/api/upload-file.php', formData, {
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
    
    if (!courseId) {
      toast.error('Không tìm thấy thông tin khóa học')
      return
    }

    // Validate
    if (!formData.title.trim()) {
      toast.error('Vui lòng nhập tiêu đề bài giảng')
      return
    }

    if (formData.duration < 0) {
      toast.error('Thời lượng phải >= 0')
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

      const response = await lessonsAPI.create({
        ...formData,
        attachment_file: attachmentUrl,
        course_id: courseId
      })

      console.log('Create lesson response:', response)
      // API interceptor trả về response.data, nên response đã là { success, message, data }
      if (response && response.success) {
        toast.success('Tạo bài giảng thành công!')
        // Reset form
        setFormData({
          title: '',
          content: '',
          video_url: '',
          attachment_file: '',
          order_number: 0,
          duration: 0
        })
        setSelectedFile(null)
        // Gọi onSuccess với data từ response
        onSuccess && onSuccess(response.data || response)
        onClose()
      } else {
        toast.error(response?.message || 'Không thể tạo bài giảng')
      }
    } catch (error) {
      console.error('Error creating lesson:', error)
      const errorMessage = error.response?.data?.message || error.message || 'Có lỗi xảy ra khi tạo bài giảng'
      toast.error(errorMessage)
      
      // Hiển thị lỗi chi tiết nếu có
      if (error.response?.data?.errors) {
        error.response.data.errors.forEach(err => {
          toast.error(err)
        })
      }
    } finally {
      setLoading(false)
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4">
        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
          <h2 className="text-2xl font-bold">Tạo bài giảng mới</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <FiX className="w-6 h-6" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Tiêu đề bài giảng <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              name="title"
              value={formData.title}
              onChange={handleChange}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="VD: Giới thiệu về PHP"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Nội dung
            </label>
            <textarea
              name="content"
              value={formData.content}
              onChange={handleChange}
              rows="6"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="Nội dung chi tiết của bài giảng..."
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                URL Video
              </label>
              <input
                type="url"
                name="video_url"
                value={formData.video_url}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder="https://youtube.com/watch?v=..."
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              File đính kèm
            </label>
            <div className="flex items-center space-x-4">
              <label className="flex-1 cursor-pointer">
                <input
                  type="file"
                  onChange={handleFileChange}
                  className="hidden"
                  accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.mp4,.mp3,.jpg,.jpeg,.png,.gif"
                />
                <div className="w-full px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-between">
                  <span className="text-gray-600">
                    {selectedFile ? selectedFile.name : 'Chọn file (PDF, DOC, ZIP, MP4, etc.)'}
                  </span>
                  <FiFile className="w-5 h-5 text-gray-400" />
                </div>
              </label>
              {selectedFile && (
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
              <p className="text-xs text-primary-600 mt-1">
                File hiện tại: {formData.attachment_file}
              </p>
            )}

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Thời lượng (phút)
              </label>
              <input
                type="number"
                name="duration"
                value={formData.duration}
                onChange={handleChange}
                min="0"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder="60"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Thứ tự
            </label>
            <input
              type="number"
              name="order_number"
              value={formData.order_number}
              onChange={handleChange}
              min="0"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="Tự động nếu để 0"
            />
            <p className="text-xs text-gray-500 mt-1">Để 0 để tự động đặt thứ tự</p>
          </div>

          <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              onClick={onClose}
              className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
              disabled={loading}
            >
              Hủy
            </button>
            <button
              type="submit"
              className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
              disabled={loading}
            >
              {loading || uploading ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                  {uploading ? 'Đang upload file...' : 'Đang tạo...'}
                </>
              ) : (
                <>
                  <FiUpload className="mr-2" />
                  Tạo bài giảng
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default CreateLessonModal

