import { useState, useEffect } from 'react'
import { motion } from 'framer-motion'
import { FiSearch, FiTrash2, FiAlertCircle } from 'react-icons/fi'
import { paymentsAPI } from '../../services/api'
import toast from 'react-hot-toast'

const AdminPayments = () => {
  const [payments, setPayments] = useState([])
  const [loading, setLoading] = useState(true)
  const [searchQuery, setSearchQuery] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [deletingId, setDeletingId] = useState(null)
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(null)

  useEffect(() => {
    loadPayments()
  }, [statusFilter])

  const loadPayments = async () => {
    try {
      setLoading(true)
      const params = {}
      if (statusFilter) {
        params.status = statusFilter
      }
      
      const response = await paymentsAPI.getAll(params)
      
      if (response.success) {
        setPayments(response.data || [])
      } else {
        toast.error(response.message || 'Không thể tải danh sách thanh toán')
        setPayments([])
      }
    } catch (error) {
      console.error('Load payments error:', error)
      toast.error(error.message || 'Không thể tải danh sách thanh toán')
      setPayments([])
    } finally {
      setLoading(false)
    }
  }

  const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND',
    }).format(price)
  }

  const getStatusLabel = (status) => {
    const labels = {
      success: 'Thành công',
      pending: 'Đang chờ',
      failed: 'Thất bại',
      refunded: 'Đã hoàn tiền'
    }
    return labels[status] || status
  }

  const getStatusBadgeClass = (status) => {
    const classes = {
      success: 'badge-success',
      pending: 'badge-warning',
      failed: 'badge-danger',
      refunded: 'badge-info'
    }
    return classes[status] || 'badge-secondary'
  }

  // Filter payments by search query
  const filteredPayments = payments.filter(payment => {
    if (!searchQuery) return true
    const query = searchQuery.toLowerCase()
    return (
      (payment.student_name && payment.student_name.toLowerCase().includes(query)) ||
      (payment.course_name && payment.course_name.toLowerCase().includes(query)) ||
      (payment.transaction_id && payment.transaction_id.toLowerCase().includes(query))
    )
  })

  const handleDelete = async (paymentId) => {
    setDeletingId(paymentId)
    try {
      const result = await paymentsAPI.delete(paymentId)
      if (result.success) {
        toast.success('Xóa thanh toán thành công!')
        // Reload payments
        loadPayments()
      } else {
        toast.error(result.message || 'Xóa thanh toán thất bại')
      }
    } catch (error) {
      console.error('Delete payment error:', error)
      toast.error(error.message || 'Có lỗi xảy ra khi xóa thanh toán')
    } finally {
      setDeletingId(null)
      setShowDeleteConfirm(null)
    }
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold mb-2">Quản lý thanh toán</h1>
          <p className="text-gray-600">Theo dõi tất cả giao dịch thanh toán</p>
        </div>

        {/* Filters */}
        <div className="card mb-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {/* Search */}
            <div className="relative">
              <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Tìm kiếm giao dịch..."
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>
            
            {/* Status Filter */}
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <option value="">Tất cả trạng thái</option>
              <option value="pending">Đang chờ</option>
              <option value="success">Thành công</option>
              <option value="failed">Thất bại</option>
              <option value="refunded">Đã hoàn tiền</option>
            </select>
          </div>
        </div>

        {/* Payments Table */}
        <div className="card overflow-x-auto">
          {loading ? (
            <div className="text-center py-12">
              <div className="spinner mx-auto"></div>
            </div>
          ) : (
            <table className="w-full">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="text-left p-4 font-semibold">Học viên</th>
                  <th className="text-left p-4 font-semibold">Khóa học</th>
                  <th className="text-left p-4 font-semibold">Số tiền</th>
                  <th className="text-left p-4 font-semibold">Phương thức</th>
                  <th className="text-left p-4 font-semibold">Trạng thái</th>
                  <th className="text-left p-4 font-semibold">Ngày thanh toán</th>
                  <th className="text-left p-4 font-semibold">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                {filteredPayments.length === 0 ? (
                  <tr>
                    <td colSpan="7" className="p-8 text-center text-gray-500">
                      {loading ? 'Đang tải...' : 'Không có giao dịch nào'}
                    </td>
                  </tr>
                ) : (
                  filteredPayments.map((payment) => (
                    <motion.tr
                      key={payment.id}
                      initial={{ opacity: 0 }}
                      animate={{ opacity: 1 }}
                      className="border-b border-gray-100 hover:bg-gray-50"
                    >
                      <td className="p-4 font-medium">
                        {payment.student_name || `ID: ${payment.student_id}`}
                      </td>
                      <td className="p-4 text-gray-600">
                        {payment.course_name || payment.course_title || `ID: ${payment.course_id}`}
                      </td>
                      <td className="p-4 font-semibold text-primary-600">
                        {formatPrice(payment.amount)}
                      </td>
                      <td className="p-4 text-gray-600 capitalize">
                        {payment.payment_gateway === 'momo' ? 'MoMo' :
                         payment.payment_gateway === 'vnpay' ? 'VNPay' :
                         payment.payment_gateway === 'bank_transfer' ? 'Chuyển khoản' :
                         payment.payment_gateway}
                      </td>
                      <td className="p-4">
                        <span className={`badge ${getStatusBadgeClass(payment.status)}`}>
                          {getStatusLabel(payment.status)}
                        </span>
                      </td>
                      <td className="p-4 text-gray-600">
                        {payment.payment_date 
                          ? new Date(payment.payment_date).toLocaleDateString('vi-VN', {
                              year: 'numeric',
                              month: '2-digit',
                              day: '2-digit',
                              hour: '2-digit',
                              minute: '2-digit'
                            })
                          : '-'}
                      </td>
                      <td className="p-4">
                        <button
                          onClick={() => setShowDeleteConfirm(payment.id)}
                          disabled={deletingId === payment.id}
                          className="text-red-600 hover:text-red-700 hover:bg-red-50 p-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                          title="Xóa thanh toán"
                        >
                          <FiTrash2 className="w-5 h-5" />
                        </button>
                      </td>
                    </motion.tr>
                  ))
                )}
              </tbody>
            </table>
          )}
        </div>
      </div>

      {/* Delete Confirmation Modal */}
      {showDeleteConfirm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            className="bg-white rounded-xl shadow-xl max-w-md w-full p-6"
          >
            <div className="flex items-center mb-4">
              <div className="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                <FiAlertCircle className="w-6 h-6 text-red-600" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900">Xác nhận xóa</h3>
                <p className="text-gray-600 text-sm mt-1">Hành động này không thể hoàn tác</p>
              </div>
            </div>
            
            <p className="text-gray-700 mb-6">
              Bạn có chắc chắn muốn xóa thanh toán này? Thông tin thanh toán sẽ bị xóa vĩnh viễn.
            </p>
            
            <div className="flex gap-3">
              <button
                onClick={() => setShowDeleteConfirm(null)}
                disabled={deletingId === showDeleteConfirm}
                className="flex-1 btn btn-outline"
              >
                Hủy
              </button>
              <button
                onClick={() => handleDelete(showDeleteConfirm)}
                disabled={deletingId === showDeleteConfirm}
                className="flex-1 btn bg-red-600 hover:bg-red-700 text-white"
              >
                {deletingId === showDeleteConfirm ? (
                  <span className="flex items-center justify-center">
                    <div className="spinner mr-2"></div>
                    Đang xóa...
                  </span>
                ) : (
                  'Xóa'
                )}
              </button>
            </div>
          </motion.div>
        </div>
      )}
    </div>
  )
}

export default AdminPayments

