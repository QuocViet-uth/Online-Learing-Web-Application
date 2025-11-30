import { useState, useEffect } from 'react'
import { motion } from 'framer-motion'
import { FiSearch, FiEdit, FiTrash2, FiUserPlus } from 'react-icons/fi'
import { usersAPI } from '../../services/api'
import toast from 'react-hot-toast'
import CreateUserModal from '../../components/User/CreateUserModal'
import EditUserModal from '../../components/User/EditUserModal'

const AdminUsers = () => {
  const [users, setUsers] = useState([])
  const [loading, setLoading] = useState(true)
  const [searchQuery, setSearchQuery] = useState('')
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [showEditModal, setShowEditModal] = useState(false)
  const [selectedUser, setSelectedUser] = useState(null)

  useEffect(() => {
    loadUsers()
  }, [])

  const loadUsers = async () => {
    try {
      setLoading(true)
      const response = await usersAPI.getAll()
      if (response && response.success && response.data) {
        setUsers(response.data)
      } else {
        setUsers([])
      }
    } catch (error) {
      console.error('Error loading users:', error)
      toast.error('Không thể tải danh sách người dùng')
      setUsers([])
    } finally {
      setLoading(false)
    }
  }

  const handleCreateSuccess = () => {
    setShowCreateModal(false)
    loadUsers()
  }

  const handleEdit = (user) => {
    setSelectedUser(user)
    setShowEditModal(true)
  }

  const handleEditSuccess = () => {
    setShowEditModal(false)
    setSelectedUser(null)
    loadUsers()
  }

  const handleDelete = async (userId) => {
    if (!window.confirm('Bạn có chắc chắn muốn xóa người dùng này? Hành động này không thể hoàn tác.')) {
      return
    }

    try {
      const response = await usersAPI.delete(userId)
      if (response && response.success) {
        toast.success('Xóa người dùng thành công!')
        loadUsers()
      } else {
        toast.error(response?.message || 'Không thể xóa người dùng')
      }
    } catch (error) {
      console.error('Error deleting user:', error)
      const errorMessage = error.response?.data?.message || error.message || 'Có lỗi xảy ra khi xóa người dùng'
      toast.error(errorMessage)
    }
  }

  const filteredUsers = users.filter(
    (user) =>
      user.username?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      user.full_name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      user.email?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      user.phone?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      user.school?.toLowerCase().includes(searchQuery.toLowerCase())
  )

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold mb-2">Quản lý người dùng</h1>
            <p className="text-gray-600">Quản lý tất cả người dùng trong hệ thống</p>
          </div>
          <button 
            onClick={() => setShowCreateModal(true)}
            className="btn btn-primary flex items-center"
          >
            <FiUserPlus className="mr-2" />
            Thêm người dùng
          </button>
        </div>

        {/* Search */}
        <div className="card mb-6">
          <div className="relative">
            <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Tìm kiếm người dùng..."
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
        </div>

        {/* Users Table */}
        <div className="card overflow-x-auto -mx-3 sm:-mx-4 md:mx-0">
          {loading ? (
            <div className="text-center py-12">
              <div className="spinner mx-auto"></div>
            </div>
          ) : (
            <div className="table-container">
              <table className="w-full min-w-[800px]">
              <thead>
                <tr className="border-b border-gray-200">
                    <th className="text-left p-3 sm:p-4 font-semibold text-xs sm:text-sm">Họ và tên</th>
                    <th className="text-left p-3 sm:p-4 font-semibold text-xs sm:text-sm">Username</th>
                    <th className="text-left p-3 sm:p-4 font-semibold text-xs sm:text-sm hidden lg:table-cell">Email</th>
                    <th className="text-left p-3 sm:p-4 font-semibold text-xs sm:text-sm hidden xl:table-cell">Số điện thoại</th>
                    <th className="text-left p-3 sm:p-4 font-semibold text-xs sm:text-sm hidden xl:table-cell">Ngày sinh</th>
                    <th className="text-left p-3 sm:p-4 font-semibold text-xs sm:text-sm hidden xl:table-cell">Giới tính</th>
                    <th className="text-left p-3 sm:p-4 font-semibold text-xs sm:text-sm hidden xl:table-cell">Trường</th>
                    <th className="text-left p-3 sm:p-4 font-semibold text-xs sm:text-sm">Vai trò</th>
                    <th className="text-left p-3 sm:p-4 font-semibold text-xs sm:text-sm hidden lg:table-cell">Ngày tạo</th>
                    <th className="text-right p-3 sm:p-4 font-semibold text-xs sm:text-sm">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                {filteredUsers.map((user) => (
                  <motion.tr
                    key={user.id}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    className="border-b border-gray-100 hover:bg-gray-50"
                  >
                    <td className="p-3 sm:p-4">
                      <div className="flex items-center space-x-2 sm:space-x-3">
                        <img
                          src={user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.full_name || 'User')}`}
                          alt={user.full_name || 'User'}
                          className="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex-shrink-0"
                        />
                        <span className="font-medium text-xs sm:text-sm truncate">{user.full_name || 'User'}</span>
                      </div>
                    </td>
                    <td className="p-3 sm:p-4 text-gray-600">{user.username}</td>
                    <td className="p-3 sm:p-4 text-gray-600 hidden lg:table-cell">{user.email}</td>
                    <td className="p-3 sm:p-4 text-gray-600 hidden xl:table-cell">{user.phone || '-'}</td>
                    <td className="p-3 sm:p-4 text-gray-600 hidden xl:table-cell">
                      {user.date_of_birth
                        ? new Date(user.date_of_birth).toLocaleDateString('vi-VN')
                        : '-'}
                    </td>
                    <td className="p-3 sm:p-4 text-gray-600 hidden xl:table-cell">
                      {user.gender === 'male' ? 'Nam' : 
                       user.gender === 'female' ? 'Nữ' : 
                       user.gender === 'other' ? 'Khác' : '-'}
                    </td>
                    <td className="p-3 sm:p-4 text-gray-600 hidden xl:table-cell">{user.school || '-'}</td>
                    <td className="p-3 sm:p-4">
                      <span className={`badge text-xs ${
                        user.role === 'admin' ? 'badge-danger' :
                        user.role === 'teacher' ? 'badge-primary' :
                        'badge-success'
                      }`}>
                        {user.role === 'admin' ? 'Quản trị' :
                         user.role === 'teacher' ? 'Giảng viên' :
                         'Học viên'}
                      </span>
                    </td>
                    <td className="p-3 sm:p-4 text-gray-600 hidden lg:table-cell">
                      {user.created_at
                        ? new Date(user.created_at).toLocaleDateString('vi-VN')
                        : 'N/A'}
                    </td>
                    <td className="p-3 sm:p-4">
                      <div className="flex items-center justify-end space-x-2">
                        <button 
                          onClick={() => handleEdit(user)}
                          className="p-2 text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                          title="Chỉnh sửa"
                        >
                          <FiEdit className="w-5 h-5" />
                        </button>
                        <button 
                          onClick={() => handleDelete(user.id)}
                          className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                          title="Xóa"
                        >
                          <FiTrash2 className="w-5 h-5" />
                        </button>
                      </div>
                    </td>
                  </motion.tr>
                ))}
              </tbody>
            </table>
            </div>
          )}
        </div>

        {/* Create User Modal */}
        {showCreateModal && (
          <CreateUserModal
            isOpen={showCreateModal}
            onClose={() => setShowCreateModal(false)}
            onSuccess={handleCreateSuccess}
          />
        )}

        {/* Edit User Modal */}
        {showEditModal && selectedUser && (
          <EditUserModal
            isOpen={showEditModal}
            onClose={() => {
              setShowEditModal(false)
              setSelectedUser(null)
            }}
            onSuccess={handleEditSuccess}
            user={selectedUser}
          />
        )}
      </div>
    </div>
  )
}

export default AdminUsers

