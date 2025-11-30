import { useState, useEffect } from 'react'
import { motion } from 'framer-motion'
import { FiSearch, FiEdit, FiTrash2, FiTag } from 'react-icons/fi'
import { couponsAPI } from '../../services/api'
import toast from 'react-hot-toast'
import CreateCouponModal from '../../components/Coupon/CreateCouponModal'
import EditCouponModal from '../../components/Coupon/EditCouponModal'

const AdminCoupons = () => {
  const [coupons, setCoupons] = useState([])
  const [loading, setLoading] = useState(true)
  const [searchQuery, setSearchQuery] = useState('')
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [showEditModal, setShowEditModal] = useState(false)
  const [selectedCoupon, setSelectedCoupon] = useState(null)

  useEffect(() => {
    loadCoupons()
  }, [])

  const loadCoupons = async () => {
    try {
      setLoading(true)
      const response = await couponsAPI.getAll()
      
      if (response && response.success && response.data) {
        setCoupons(Array.isArray(response.data) ? response.data : [])
      } else {
        setCoupons([])
      }
    } catch (error) {
      console.error('Error loading coupons:', error)
      toast.error('Không thể tải danh sách mã giảm giá')
      setCoupons([])
    } finally {
      setLoading(false)
    }
  }

  const handleEdit = (coupon) => {
    setSelectedCoupon(coupon)
    setShowEditModal(true)
  }

  const handleDelete = async (couponId) => {
    if (!window.confirm('Bạn có chắc chắn muốn xóa mã giảm giá này? Hành động này không thể hoàn tác.')) {
      return
    }

    try {
      const response = await couponsAPI.delete(couponId)
      if (response && response.success) {
        toast.success('Xóa mã giảm giá thành công!')
        loadCoupons()
      } else {
        toast.error(response?.message || 'Không thể xóa mã giảm giá')
      }
    } catch (error) {
      console.error('Error deleting coupon:', error)
      const errorMessage = error.response?.data?.message || error.message || 'Có lỗi xảy ra khi xóa mã giảm giá'
      toast.error(errorMessage)
    }
  }

  const filteredCoupons = coupons.filter(
    (coupon) =>
      coupon.code?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      coupon.description?.toLowerCase().includes(searchQuery.toLowerCase())
  )

  const getStatusBadge = (status) => {
    switch (status) {
      case 'active':
        return 'badge-success'
      case 'inactive':
        return 'badge-warning'
      case 'expired':
        return 'badge-danger'
      default:
        return 'badge-secondary'
    }
  }

  const getStatusText = (status) => {
    switch (status) {
      case 'active':
        return 'Kích hoạt'
      case 'inactive':
        return 'Tạm ngưng'
      case 'expired':
        return 'Hết hạn'
      default:
        return status
    }
  }

  const isExpired = (validUntil) => {
    return new Date(validUntil) < new Date()
  }

  const isActive = (coupon) => {
    if (coupon.status !== 'active') return false
    if (isExpired(coupon.valid_until)) return false
    if (coupon.max_uses && coupon.used_count >= coupon.max_uses) return false
    return true
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold mb-2">Quản lý mã giảm giá</h1>
            <p className="text-gray-600">Tạo và quản lý các mã giảm giá cho khóa học</p>
          </div>
          <button 
            onClick={() => setShowCreateModal(true)}
            className="btn btn-primary flex items-center"
          >
            <FiTag className="mr-2" />
            Tạo mã giảm giá
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
              placeholder="Tìm kiếm mã giảm giá..."
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
        </div>

        {/* Coupons Table */}
        <div className="card overflow-x-auto">
          {loading ? (
            <div className="text-center py-12">
              <div className="spinner mx-auto"></div>
            </div>
          ) : filteredCoupons.length === 0 ? (
            <div className="text-center py-12">
              <FiTag className="w-16 h-16 text-gray-300 mx-auto mb-4" />
              <p className="text-gray-600">Không tìm thấy mã giảm giá nào</p>
            </div>
          ) : (
            <table className="w-full">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="text-left p-4 font-semibold">Mã giảm giá</th>
                  <th className="text-left p-4 font-semibold">Giảm giá</th>
                  <th className="text-left p-4 font-semibold">Mô tả</th>
                  <th className="text-left p-4 font-semibold">Hiệu lực</th>
                  <th className="text-left p-4 font-semibold">Sử dụng</th>
                  <th className="text-left p-4 font-semibold">Trạng thái</th>
                  <th className="text-right p-4 font-semibold">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                {filteredCoupons.map((coupon) => (
                  <motion.tr
                    key={coupon.id}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    className="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                  >
                    <td className="p-4">
                      <span className="font-mono font-semibold text-primary-600">{coupon.code}</span>
                    </td>
                    <td className="p-4">
                      <span className="font-semibold text-green-600">{coupon.discount_percent}%</span>
                    </td>
                    <td className="p-4 text-gray-600">
                      {coupon.description || '-'}
                    </td>
                    <td className="p-4 text-sm text-gray-600">
                      <div>
                        <div>Từ: {new Date(coupon.valid_from).toLocaleDateString('vi-VN')}</div>
                        <div>Đến: {new Date(coupon.valid_until).toLocaleDateString('vi-VN')}</div>
                      </div>
                    </td>
                    <td className="p-4 text-sm text-gray-600">
                      {coupon.used_count || 0}
                      {coupon.max_uses && ` / ${coupon.max_uses}`}
                      {!coupon.max_uses && ' / ∞'}
                    </td>
                    <td className="p-4">
                      <span className={`badge ${getStatusBadge(coupon.status)}`}>
                        {getStatusText(coupon.status)}
                      </span>
                      {isExpired(coupon.valid_until) && (
                        <span className="badge badge-danger ml-2">Hết hạn</span>
                      )}
                      {coupon.max_uses && coupon.used_count >= coupon.max_uses && (
                        <span className="badge badge-warning ml-2">Đã hết</span>
                      )}
                    </td>
                    <td className="p-4">
                      <div className="flex items-center justify-end space-x-2">
                        <button 
                          onClick={() => handleEdit(coupon)}
                          className="p-2 text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                          title="Chỉnh sửa"
                        >
                          <FiEdit className="w-5 h-5" />
                        </button>
                        <button 
                          onClick={() => handleDelete(coupon.id)}
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
          )}
        </div>

        <CreateCouponModal
          isOpen={showCreateModal}
          onClose={() => setShowCreateModal(false)}
          onSuccess={loadCoupons}
        />

        <EditCouponModal
          isOpen={showEditModal}
          onClose={() => {
            setShowEditModal(false)
            setSelectedCoupon(null)
          }}
          onSuccess={loadCoupons}
          coupon={selectedCoupon}
        />
      </div>
    </div>
  )
}

export default AdminCoupons

