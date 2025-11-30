import { useState, useEffect } from 'react'
import { FiX, FiUpload } from 'react-icons/fi'
import { usersAPI } from '../../services/api'
import { useAuth } from '../../contexts/AuthContext'
import toast from 'react-hot-toast'

const EditUserModal = ({ isOpen, onClose, onSuccess, user }) => {
  const { updateUser } = useAuth()
  const [loading, setLoading] = useState(false)
  const [avatarPreview, setAvatarPreview] = useState(null)
  const [uploadingAvatar, setUploadingAvatar] = useState(false)
  const [formData, setFormData] = useState({
    username: '',
    full_name: '',
    date_of_birth: '',
    gender: '',
    school: '',
    email: '',
    phone: '',
    role: 'student',
    avatar: '',
    password: '' // Optional - chỉ đổi nếu nhập
  })

  // Load user data khi modal mở
  useEffect(() => {
    if (isOpen && user) {
      setFormData({
        username: user.username || '',
        full_name: user.full_name || '',
        date_of_birth: user.date_of_birth ? user.date_of_birth.split('T')[0] : '',
        gender: user.gender || '',
        school: user.school || '',
        email: user.email || '',
        phone: user.phone || '',
        role: user.role || 'student',
        avatar: user.avatar || '',
        password: '' // Không load password
      })
      setAvatarPreview(null) // Reset preview khi mở modal
    }
  }, [isOpen, user])

  const handleAvatarChange = async (e) => {
    const file = e.target.files[0]
    if (!file) return

    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']
    if (!allowedTypes.includes(file.type)) {
      toast.error('Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)')
      return
    }

    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
      toast.error('Kích thước file tối đa là 5MB')
      return
    }

    // Show preview
    const reader = new FileReader()
    reader.onloadend = () => {
      setAvatarPreview(reader.result)
    }
    reader.readAsDataURL(file)

    // Upload avatar
    setUploadingAvatar(true)
    try {
      const formDataUpload = new FormData()
      formDataUpload.append('avatar', file)
      formDataUpload.append('user_id', user.id)

      const API_BASE_URL = import.meta.env.VITE_API_URL 
        ? `${import.meta.env.VITE_API_URL}/api`
        : '/api'

      const response = await fetch(`${API_BASE_URL}/upload-avatar.php`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: formDataUpload
      })

      const result = await response.json()

      if (result.success && result.data) {
        const newAvatarUrl = result.data.avatar_url
        setFormData({
          ...formData,
          avatar: newAvatarUrl
        })
        toast.success('Upload avatar thành công!')
        setAvatarPreview(null) // Clear preview sau khi upload thành công
      } else {
        toast.error(result.message || 'Không thể upload avatar')
        setAvatarPreview(null)
      }
    } catch (error) {
      console.error('Error uploading avatar:', error)
      toast.error('Có lỗi xảy ra khi upload avatar')
      setAvatarPreview(null)
    } finally {
      setUploadingAvatar(false)
      // Reset file input
      e.target.value = ''
    }
  }

  const handleChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: value
    }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    
    if (!user?.id) {
      toast.error('Không tìm thấy thông tin user')
      return
    }

    // Validate
    if (!formData.username.trim()) {
      toast.error('Vui lòng nhập username')
      return
    }

    if (!formData.email.trim()) {
      toast.error('Vui lòng nhập email')
      return
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      toast.error('Email không hợp lệ')
      return
    }

    // Nếu có password mới, validate
    if (formData.password && formData.password.length < 4) {
      toast.error('Password phải có ít nhất 4 ký tự')
      return
    }

    try {
      setLoading(true)
      
      // Chỉ gửi password nếu có thay đổi
      const updateData = {
        username: formData.username,
        full_name: formData.full_name,
        date_of_birth: formData.date_of_birth || null,
        gender: formData.gender || null,
        school: formData.school,
        email: formData.email,
        phone: formData.phone,
        role: formData.role,
        avatar: formData.avatar
      }
      
      if (formData.password) {
        updateData.password = formData.password
      }
      
      const response = await usersAPI.update(user.id, updateData)
      
      if (response && response.success) {
        toast.success('Cập nhật user thành công!')
        onSuccess && onSuccess(response.data)
      } else {
        toast.error(response?.message || 'Không thể cập nhật user')
      }
    } catch (error) {
      console.error('Error updating user:', error)
      const errorMessage = error.response?.data?.message || error.message || 'Có lỗi xảy ra khi cập nhật user'
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
          <h2 className="text-2xl font-bold">Chỉnh sửa người dùng</h2>
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
              Username <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              name="username"
              value={formData.username}
              onChange={handleChange}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="Nhập username"
              disabled={loading}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Họ tên
            </label>
            <input
              type="text"
              name="full_name"
              value={formData.full_name}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="Nhập họ tên đầy đủ (tùy chọn)"
              disabled={loading}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Ngày sinh
            </label>
            <input
              type="date"
              name="date_of_birth"
              value={formData.date_of_birth}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              disabled={loading}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Giới tính
            </label>
            <select
              name="gender"
              value={formData.gender}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              disabled={loading}
            >
              <option value="">Chọn giới tính</option>
              <option value="male">Nam</option>
              <option value="female">Nữ</option>
              <option value="other">Khác</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Trường đang theo học
            </label>
            <input
              type="text"
              name="school"
              value={formData.school}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="Nhập tên trường (tùy chọn)"
              disabled={loading}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Email <span className="text-red-500">*</span>
            </label>
            <input
              type="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="Nhập email"
              disabled={loading}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Số điện thoại
            </label>
            <input
              type="tel"
              name="phone"
              value={formData.phone}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="Nhập số điện thoại (tùy chọn)"
              disabled={loading}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Vai trò <span className="text-red-500">*</span>
            </label>
            <select
              name="role"
              value={formData.role}
              onChange={handleChange}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              disabled={loading}
            >
              <option value="student">Học viên</option>
              <option value="teacher">Giảng viên</option>
              <option value="admin">Quản trị</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Avatar
            </label>
            <div className="space-y-3">
              {/* Avatar Preview */}
              {(avatarPreview || formData.avatar) && (
                <div className="flex items-center space-x-4">
                  <img
                    src={avatarPreview || formData.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(formData.full_name || formData.username || 'User')}`}
                    alt="Avatar preview"
                    className="w-20 h-20 rounded-full border-2 border-gray-200 object-cover"
                    onError={(e) => {
                      e.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(formData.full_name || formData.username || 'User')}`
                    }}
                  />
                  <div className="flex-1">
                    <p className="text-sm text-gray-600">Xem trước avatar</p>
                  </div>
                </div>
              )}
              
              {/* Upload Button */}
              <div className="flex items-center space-x-2">
                <label className="flex-1 cursor-pointer">
                  <input
                    type="file"
                    accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                    onChange={handleAvatarChange}
                    disabled={loading || uploadingAvatar}
                    className="hidden"
                  />
                  <div className={`flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors ${(loading || uploadingAvatar) ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}`}>
                    <FiUpload className="mr-2" />
                    <span>{uploadingAvatar ? 'Đang upload...' : 'Tải lên ảnh'}</span>
                  </div>
                </label>
              </div>
              
              {/* URL Input (fallback) */}
              <div>
                <input
                  type="text"
                  name="avatar"
                  value={formData.avatar}
                  onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                  placeholder="Hoặc nhập URL avatar"
                  disabled={loading}
                />
                <p className="text-xs text-gray-500 mt-1">Có thể nhập URL trực tiếp hoặc upload ảnh</p>
              </div>
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Password mới
            </label>
            <input
              type="password"
              name="password"
              value={formData.password}
              onChange={handleChange}
              minLength={4}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="Nhập password mới (để trống nếu không đổi)"
              disabled={loading}
            />
            <p className="text-xs text-gray-500 mt-1">Để trống nếu không muốn đổi password</p>
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
              {loading ? 'Đang cập nhật...' : 'Cập nhật'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default EditUserModal

