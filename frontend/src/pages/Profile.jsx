import { useState, useEffect } from 'react'
import { useAuth } from '../contexts/AuthContext'
import { FiUser, FiMail, FiPhone, FiSave, FiEdit2, FiCalendar, FiUpload, FiX, FiUsers } from 'react-icons/fi'
import { usersAPI } from '../services/api'
import toast from 'react-hot-toast'
import { motion } from 'framer-motion'

const Profile = () => {
  const { user, updateUser } = useAuth()
  const [formData, setFormData] = useState({
    username: '',
    full_name: '',
    date_of_birth: '',
    gender: '',
    email: '',
    phone: '',
    avatar: '',
  })
  const [editing, setEditing] = useState(false)
  const [loading, setLoading] = useState(false)
  const [avatarPreview, setAvatarPreview] = useState(null)
  const [uploadingAvatar, setUploadingAvatar] = useState(false)

  useEffect(() => {
    if (user) {
      setFormData({
        username: user.username || '',
        full_name: user.full_name || '',
        date_of_birth: user.date_of_birth ? user.date_of_birth.split('T')[0] : '',
        gender: user.gender || '',
        email: user.email || '',
        phone: user.phone || '',
        avatar: user.avatar || '',
      })
    }
  }, [user])

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    })
  }

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
        // Cập nhật user context ngay lập tức
        updateUser({ ...user, avatar: newAvatarUrl })
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

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)

    try {
      // Update user via API
      const updateData = {
        ...formData,
        date_of_birth: formData.date_of_birth || null,
        gender: formData.gender || null
      }
      const response = await usersAPI.update(user.id, updateData)
      
      if (response && response.success) {
        // Reload user data từ API để đảm bảo có đầy đủ thông tin
        try {
          const userRes = await usersAPI.getById(user.id)
          if (userRes && userRes.success && userRes.data) {
            updateUser(userRes.data)
          } else {
            // Fallback: update với dữ liệu từ response
            updateUser({ ...user, ...formData, ...response.data })
          }
        } catch (error) {
          // Fallback: update với dữ liệu từ response
          updateUser({ ...user, ...formData, ...response.data })
        }
        toast.success('Cập nhật thông tin thành công!')
        setEditing(false)
      } else {
        toast.error(response?.message || 'Có lỗi xảy ra khi cập nhật')
      }
    } catch (error) {
      console.error('Error updating profile:', error)
      toast.error(error.message || 'Có lỗi xảy ra khi cập nhật')
    } finally {
      setLoading(false)
    }
  }

  if (!user) {
    return null
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-6 sm:py-8 px-3 sm:px-4">
        <div className="max-w-4xl mx-auto">
          <h1 className="text-2xl sm:text-3xl font-bold mb-6 sm:mb-8">Hồ sơ của tôi</h1>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
            {/* Profile Card */}
            <div className="lg:col-span-1">
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="card text-center"
              >
                <div className="mb-6">
                  <img
                    src={user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.full_name || 'User')}`}
                    alt={user.full_name || 'User'}
                    className="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-primary-200"
                    onError={(e) => {
                      e.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.full_name || 'User')}`
                    }}
                  />
                  <h2 className="text-2xl font-bold">{user.full_name || 'User'}</h2>
                  <p className="text-gray-600 capitalize mt-1">{user.role}</p>
                </div>
                <div className="space-y-2 text-sm text-gray-600">
                  {user.full_name && (
                    <div className="flex items-center justify-center">
                      <FiUser className="mr-2" />
                      <span>{user.full_name}</span>
                    </div>
                  )}
                  {user.date_of_birth && (
                    <div className="flex items-center justify-center">
                      <FiCalendar className="mr-2" />
                      <span>{new Date(user.date_of_birth).toLocaleDateString('vi-VN')}</span>
                    </div>
                  )}
                  {user.gender && (
                    <div className="flex items-center justify-center">
                      <FiUsers className="mr-2" />
                      <span>
                        {user.gender === 'male' ? 'Nam' : 
                         user.gender === 'female' ? 'Nữ' : 
                         'Khác'}
                      </span>
                    </div>
                  )}
                  <div className="flex items-center justify-center">
                    <FiMail className="mr-2" />
                    <span>{user.email}</span>
                  </div>
                  {user.phone && (
                    <div className="flex items-center justify-center">
                      <FiPhone className="mr-2" />
                      <span>{user.phone}</span>
                    </div>
                  )}
                </div>
              </motion.div>
            </div>

            {/* Edit Form */}
            <div className="lg:col-span-2">
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.1 }}
                className="card"
              >
                <div className="flex items-center justify-between mb-6">
                  <h3 className="text-xl font-semibold">Thông tin cá nhân</h3>
                  {!editing && (
                    <button
                      onClick={() => setEditing(true)}
                      className="btn btn-ghost flex items-center"
                    >
                      <FiEdit2 className="mr-2" />
                      Chỉnh sửa
                    </button>
                  )}
                </div>

                <form onSubmit={handleSubmit}>
                  <div className="space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Tên đăng nhập
                      </label>
                      <input
                        type="text"
                        name="username"
                        value={formData.username}
                        onChange={handleChange}
                        disabled={!editing}
                        className="input disabled:bg-gray-100"
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Họ và tên <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="text"
                        name="full_name"
                        value={formData.full_name}
                        onChange={handleChange}
                        disabled={!editing}
                        className="input disabled:bg-gray-100"
                        placeholder="Nhập họ và tên đầy đủ"
                        required
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Ngày tháng năm sinh
                      </label>
                      <input
                        type="date"
                        name="date_of_birth"
                        value={formData.date_of_birth}
                        onChange={handleChange}
                        disabled={!editing}
                        className="input disabled:bg-gray-100"
                        max={new Date().toISOString().split('T')[0]}
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Giới tính
                      </label>
                      <select
                        name="gender"
                        value={formData.gender}
                        onChange={handleChange}
                        disabled={!editing}
                        className="input disabled:bg-gray-100"
                      >
                        <option value="">Chọn giới tính</option>
                        <option value="male">Nam</option>
                        <option value="female">Nữ</option>
                        <option value="other">Khác</option>
                      </select>
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Email
                      </label>
                      <input
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        disabled={!editing}
                        className="input disabled:bg-gray-100"
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Số điện thoại
                      </label>
                      <input
                        type="tel"
                        name="phone"
                        value={formData.phone}
                        onChange={handleChange}
                        disabled={!editing}
                        className="input disabled:bg-gray-100"
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Avatar
                      </label>
                      <div className="space-y-3">
                        {/* Preview */}
                        {(avatarPreview || formData.avatar) && (
                          <div className="flex items-center space-x-4">
                            <img
                              src={avatarPreview || formData.avatar}
                              alt="Avatar preview"
                              className="w-20 h-20 rounded-full object-cover border-2 border-gray-200"
                            />
                            {editing && (
                              <button
                                type="button"
                                onClick={() => {
                                  setAvatarPreview(null)
                                  setFormData({ ...formData, avatar: '' })
                                }}
                                className="text-sm text-red-600 hover:text-red-700 flex items-center"
                              >
                                <FiX className="mr-1" />
                                Xóa ảnh
                              </button>
                            )}
                          </div>
                        )}
                        
                        {/* Upload button */}
                        {editing && (
                          <div>
                            <label className="cursor-pointer inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                              {uploadingAvatar ? (
                                <>
                                  <span className="spinner mr-2"></span>
                                  Đang tải lên...
                                </>
                              ) : (
                                <>
                                  <FiUpload className="mr-2" />
                                  {avatarPreview || formData.avatar ? 'Thay đổi ảnh' : 'Tải ảnh lên'}
                                </>
                              )}
                              <input
                                type="file"
                                accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                onChange={handleAvatarChange}
                                disabled={uploadingAvatar || !editing}
                                className="hidden"
                              />
                            </label>
                            <p className="text-xs text-gray-500 mt-2">
                              Chỉ chấp nhận JPG, PNG, GIF, WEBP. Kích thước tối đa: 5MB
                            </p>
                          </div>
                        )}
                      </div>
                    </div>

                    {editing && (
                      <div className="flex space-x-4 pt-4">
                        <button
                          type="submit"
                          disabled={loading}
                          className="btn btn-primary flex items-center"
                        >
                          <FiSave className="mr-2" />
                          {loading ? 'Đang lưu...' : 'Lưu thay đổi'}
                        </button>
                        <button
                          type="button"
                        onClick={() => {
                          setEditing(false)
                          setFormData({
                            username: user.username || '',
                            full_name: user.full_name || '',
                            date_of_birth: user.date_of_birth ? user.date_of_birth.split('T')[0] : '',
                            gender: user.gender || '',
                            email: user.email || '',
                            phone: user.phone || '',
                            avatar: user.avatar || '',
                          })
                        }}
                          className="btn btn-ghost"
                        >
                          Hủy
                        </button>
                      </div>
                    )}
                  </div>
                </form>
              </motion.div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default Profile

