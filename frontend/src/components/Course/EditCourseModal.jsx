import { useState, useEffect } from 'react'
import { FiX, FiSave } from 'react-icons/fi'
import { coursesAPI } from '../../services/api'
import toast from 'react-hot-toast'

const EditCourseModal = ({ isOpen, onClose, onSuccess, course, teacherId }) => {
  const [loading, setLoading] = useState(false)
  const [formData, setFormData] = useState({
    course_name: '',
    title: '',
    description: '',
    price: 0,
    start_date: '',
    end_date: '',
    status: 'upcoming',
    thumbnail: '',
    online_link: ''
  })

  // Load course data khi modal mở
  useEffect(() => {
    if (isOpen && course) {
      setFormData({
        course_name: course.course_name || '',
        title: course.title || '',
        description: course.description || '',
        price: course.price || 0,
        start_date: course.start_date ? course.start_date.split('T')[0] : '',
        end_date: course.end_date ? course.end_date.split('T')[0] : '',
        status: course.status || 'upcoming',
        thumbnail: course.thumbnail || '',
        online_link: course.online_link || ''
      })
    }
  }, [isOpen, course])

  const handleChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: name === 'price' ? parseFloat(value) || 0 : value
    }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    
    if (!course?.id) {
      toast.error('Không tìm thấy thông tin khóa học')
      return
    }

    // Validate
    if (!formData.course_name.trim()) {
      toast.error('Vui lòng nhập mã khóa học')
      return
    }

    if (!formData.title.trim()) {
      toast.error('Vui lòng nhập tiêu đề khóa học')
      return
    }

    if (!formData.start_date || !formData.end_date) {
      toast.error('Vui lòng chọn ngày bắt đầu và kết thúc')
      return
    }

    if (new Date(formData.start_date) > new Date(formData.end_date)) {
      toast.error('Ngày bắt đầu phải trước ngày kết thúc')
      return
    }

    try {
      setLoading(true)
      const response = await coursesAPI.update(course.id, formData)

      if (response && response.success) {
        toast.success('Cập nhật khóa học thành công!')
        onSuccess && onSuccess(response.data)
        onClose()
      } else {
        toast.error(response?.message || 'Không thể cập nhật khóa học')
      }
    } catch (error) {
      console.error('Error updating course:', error)
      const errorMessage = error.response?.data?.message || error.message || 'Có lỗi xảy ra khi cập nhật khóa học'
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

  if (!isOpen || !course) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4">
        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
          <h2 className="text-2xl font-bold">Chỉnh sửa khóa học</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <FiX className="w-6 h-6" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Mã khóa học <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                name="course_name"
                value={formData.course_name}
                onChange={handleChange}
                required
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder="VD: PHP-101"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Giá (VNĐ) <span className="text-red-500">*</span>
              </label>
              <input
                type="number"
                name="price"
                value={formData.price}
                onChange={handleChange}
                min="0"
                step="1000"
                required
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder="500000"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Tiêu đề khóa học <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              name="title"
              value={formData.title}
              onChange={handleChange}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="VD: Lập trình PHP cơ bản"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Mô tả
            </label>
            <textarea
              name="description"
              value={formData.description}
              onChange={handleChange}
              rows="4"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="Mô tả chi tiết về khóa học..."
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Ngày bắt đầu <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                name="start_date"
                value={formData.start_date}
                onChange={handleChange}
                required
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Ngày kết thúc <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                name="end_date"
                value={formData.end_date}
                onChange={handleChange}
                required
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Trạng thái
              </label>
              <select
                name="status"
                value={formData.status}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              >
                <option value="upcoming">Sắp diễn ra</option>
                <option value="active">Đang diễn ra</option>
                <option value="closed">Đã kết thúc</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                URL ảnh đại diện
              </label>
              <input
                type="url"
                name="thumbnail"
                value={formData.thumbnail}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder="https://example.com/image.jpg"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Link học online (tùy chọn)
            </label>
            <input
              type="url"
              name="online_link"
              value={formData.online_link}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="https://zoom.us/j/... hoặc https://meet.google.com/..."
            />
            <p className="text-xs text-gray-500 mt-1">Link Zoom, Google Meet, hoặc nền tảng học online khác</p>
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
              {loading ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                  Đang cập nhật...
                </>
              ) : (
                <>
                  <FiSave className="mr-2" />
                  Lưu thay đổi
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default EditCourseModal


