import { useState, useEffect } from 'react'
import { FiX } from 'react-icons/fi'
import { lessonsAPI } from '../../services/api'
import toast from 'react-hot-toast'

const EditLessonModal = ({ isOpen, onClose, onSuccess, lesson }) => {
  const [loading, setLoading] = useState(false)
  const [formData, setFormData] = useState({
    title: '',
    content: '',
    video_url: '',
    attachment_file: '',
    order_number: 0,
    duration: 0
  })

  // Load lesson data khi modal mở
  useEffect(() => {
    if (isOpen && lesson) {
      setFormData({
        title: lesson.title || '',
        content: lesson.content || '',
        video_url: lesson.video_url || '',
        attachment_file: lesson.attachment_file || '',
        order_number: lesson.order_number || 0,
        duration: lesson.duration || 0
      })
    }
  }, [isOpen, lesson])

  const handleChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: name === 'order_number' || name === 'duration' ? (value === '' ? 0 : parseInt(value) || 0) : value
    }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    
    if (!lesson?.id) {
      toast.error('Không tìm thấy thông tin bài giảng')
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

    if (formData.order_number < 0) {
      toast.error('Thứ tự phải >= 0')
      return
    }

    try {
      setLoading(true)
      const updateData = {
        ...formData,
        attachment_file: formData.attachment_file || null
      }
      
      const response = await lessonsAPI.update(lesson.id, updateData)
      
      if (response && response.success) {
        toast.success('Cập nhật bài giảng thành công!')
        onSuccess && onSuccess(response.data)
        onClose()
      } else {
        toast.error(response?.message || 'Không thể cập nhật bài giảng')
      }
    } catch (error) {
      console.error('Error updating lesson:', error)
      const errorMessage = error.response?.data?.message || error.message || 'Có lỗi xảy ra khi cập nhật bài giảng'
      toast.error(errorMessage)
    } finally {
      setLoading(false)
    }
  }

  if (!isOpen || !lesson) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <h2 className="text-2xl font-bold">Chỉnh sửa bài giảng</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
            disabled={loading}
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
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="Nhập tiêu đề bài giảng"
              disabled={loading}
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
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="Nhập nội dung bài giảng"
              disabled={loading}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              URL Video
            </label>
            <input
              type="url"
              name="video_url"
              value={formData.video_url}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="https://youtube.com/watch?v=..."
              disabled={loading}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
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
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                disabled={loading}
              />
            </div>

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
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                disabled={loading}
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              File đính kèm (tên file)
            </label>
            <input
              type="text"
              name="attachment_file"
              value={formData.attachment_file}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="Tên file đính kèm (nếu có)"
              disabled={loading}
            />
            <p className="text-xs text-gray-500 mt-1">Nhập tên file nếu đã upload trước đó</p>
          </div>

          <div className="flex space-x-3 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
              disabled={loading}
            >
              Hủy
            </button>
            <button
              type="submit"
              className="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              disabled={loading}
            >
              {loading ? 'Đang cập nhật...' : 'Lưu thay đổi'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default EditLessonModal

