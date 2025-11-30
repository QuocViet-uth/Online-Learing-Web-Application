import { useState } from 'react'
import { FiX } from 'react-icons/fi'
import { couponsAPI } from '../../services/api'
import toast from 'react-hot-toast'

const CreateCouponModal = ({ isOpen, onClose, onSuccess }) => {
  const [loading, setLoading] = useState(false)
  const [formData, setFormData] = useState({
    code: '',
    discount_percent: 10,
    description: '',
    valid_from: '',
    valid_until: '',
    max_uses: '',
    status: 'active'
  })

  const handleChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: name === 'discount_percent' || name === 'max_uses' ? (value === '' ? '' : parseFloat(value) || 0) : value
    }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    
    // Validate
    if (!formData.code.trim()) {
      toast.error('Vui lòng nhập mã giảm giá')
      return
    }

    if (formData.discount_percent <= 0 || formData.discount_percent > 100) {
      toast.error('Phần trăm giảm giá phải từ 1 đến 100')
      return
    }

    if (!formData.valid_from || !formData.valid_until) {
      toast.error('Vui lòng chọn ngày bắt đầu và ngày hết hạn')
      return
    }

    if (new Date(formData.valid_from) > new Date(formData.valid_until)) {
      toast.error('Ngày bắt đầu phải trước ngày hết hạn')
      return
    }

    try {
      setLoading(true)
      const requestData = {
        ...formData,
        max_uses: formData.max_uses === '' ? null : formData.max_uses
      }
      
      const response = await couponsAPI.create(requestData)
      
      if (response && response.success) {
        toast.success('Tạo mã giảm giá thành công!')
        // Reset form
        setFormData({
          code: '',
          discount_percent: 10,
          description: '',
          valid_from: '',
          valid_until: '',
          max_uses: '',
          status: 'active'
        })
        onSuccess && onSuccess(response.data)
        onClose()
      } else {
        toast.error(response?.message || 'Không thể tạo mã giảm giá')
      }
    } catch (error) {
      console.error('Error creating coupon:', error)
      const errorMessage = error.response?.data?.message || error.message || 'Có lỗi xảy ra khi tạo mã giảm giá'
      toast.error(errorMessage)
    } finally {
      setLoading(false)
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <h2 className="text-2xl font-bold">Tạo mã giảm giá mới</h2>
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
              Mã giảm giá <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              name="code"
              value={formData.code}
              onChange={handleChange}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="VD: SALE2024"
              disabled={loading}
              style={{ textTransform: 'uppercase' }}
            />
            <p className="text-xs text-gray-500 mt-1">Mã sẽ được chuyển thành chữ hoa</p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Phần trăm giảm giá (%) <span className="text-red-500">*</span>
            </label>
            <input
              type="number"
              name="discount_percent"
              value={formData.discount_percent}
              onChange={handleChange}
              required
              min="1"
              max="100"
              step="0.01"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="10"
              disabled={loading}
            />
            <p className="text-xs text-gray-500 mt-1">Từ 1% đến 100%</p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Mô tả
            </label>
            <textarea
              name="description"
              value={formData.description}
              onChange={handleChange}
              rows="3"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="Mô tả về mã giảm giá (tùy chọn)"
              disabled={loading}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Ngày bắt đầu <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                name="valid_from"
                value={formData.valid_from}
                onChange={handleChange}
                required
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                disabled={loading}
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Ngày hết hạn <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                name="valid_until"
                value={formData.valid_until}
                onChange={handleChange}
                required
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                disabled={loading}
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Số lần sử dụng tối đa
            </label>
            <input
              type="number"
              name="max_uses"
              value={formData.max_uses}
              onChange={handleChange}
              min="1"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="Để trống = không giới hạn"
              disabled={loading}
            />
            <p className="text-xs text-gray-500 mt-1">Để trống nếu không muốn giới hạn số lần sử dụng</p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Trạng thái
            </label>
            <select
              name="status"
              value={formData.status}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              disabled={loading}
            >
              <option value="active">Kích hoạt</option>
              <option value="inactive">Tạm ngưng</option>
            </select>
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
              {loading ? 'Đang tạo...' : 'Tạo mã giảm giá'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default CreateCouponModal

